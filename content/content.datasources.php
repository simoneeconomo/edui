<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	class contentExtensionEduiDatasources extends AdministrationPage {
		public $_errors;
		
		public function __construct(&$parent){
			parent::__construct($parent);
		}
		
		public function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Data Sources'))));
			$this->appendSubheading(__('Data Sources'), Widget::Anchor(__('Create New'), URL . '/symphony/blueprints/datasources/new/', __('Create a new data source'), 'create button'));

			$datasourcesManager = new DatasourceManager($this->_Parent);
			$datasources = $datasourcesManager->listAll();

			$sectionManager = new SectionManager($this->_Parent);

			$aTableHead = array(

				array(__('Name'), 'col'),
				array(__('Source'), 'col'),
				array(__('Pages'), 'col'),
				array(__('Author'), 'col')

			);

			$aTableBody = array();

			if(!is_array($datasources) || empty($datasources)){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				
				$bOdd = true;

				foreach($datasources as $d){

					if ($d['can_parse']) {
						$name = Widget::TableData(
							Widget::Anchor(
								$d['name'],
								URL . '/symphony/blueprints/datasources/edit/' . $d['handle'] . '/',
								$d['handle']
							)
						);

						if ($d['type'] > 0) {
							$sectionData = $sectionManager->fetch($d['type']);

							$section = Widget::TableData(
								Widget::Anchor(
									$sectionData->_data['name'],
									URL . '/symphony/blueprints/sections/edit/' . $sectionData->_data['id'] . '/',
									$sectionData->_data['handle']
								)
							);
							
						} else {
							$section = Widget::TableData(ucwords($d['type']));
						}
						
					} else {
						$name = Widget::TableData($d['name']);

						$section = Widget::TableData(__('Unknown'));
					}

					$query = 'SELECT `id`, `title`, `data_sources` FROM tbl_pages WHERE `data_sources` REGEXP "' . $d['handle'] . '"';
					$pages = $this->_Parent->Database->fetch($query);
					$pagelinks = array();

					foreach($pages as $key => $page) {
						$datasources = explode(',', $page['data_sources']);
						// Avoid false positives. Ideally should be done in the REGEXP above?
						if (in_array($d['handle'], $datasources)) {
							$pagelinks[] = Widget::Anchor($page['title'], URL . '/symphony/blueprints/pages/edit/' . $page['id'])->generate() . (count($pages) > ($key + 1) ? ((($key + 1) % 6) == 0 ? '<br />' : ', ') : '');
						}
					}
					$pagelinks = Widget::TableData(implode('', $pagelinks));

					$author = $d['author']['name'];

					if (isset($d['author']['website'])) {
						$author = Widget::Anchor($d['author']['name'], General::validateURL($d['author']['website']));

					} else if(isset($d['author']['email'])) {
						$author = Widget::Anchor($d['author']['name'], 'mailto:' . $d['author']['email']);
					}

					$author = Widget::TableData($author);

					$author->appendChild(Widget::Input('items[' . $d['handle'] . ']', null, 'checkbox'));

					$aTableBody[] = Widget::TableRow(array($name, $section, $pagelinks, $author), null);

				}
			}

			$table = Widget::Table(
								Widget::TableHead($aTableHead), 
								NULL, 
								Widget::TableBody($aTableBody)
						);

			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm'),
				//array('duplicate', false, 'Duplicate'),
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':
						$canProceed = true;
						foreach($checked as $name) {
							if (!General::deleteFile(DATASOURCES . '/data.' . $name . '.php')) {
								$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($name)),Alert::ERROR);
								$canProceed = false;
							}
						}

						if ($canProceed) redirect($this->_Parent->getCurrentPageURL());
						break;
						
					//case 'duplicate':
						//foreach($checked as $name) $this->_Parent->ExtensionManager->uninstall($name);
						//break;
				}
			}

		}

	}
	
?>
