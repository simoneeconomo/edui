<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	class contentExtensionEduiEvents extends AdministrationPage {
		public $_errors;
		
		public function __construct(&$parent){
			parent::__construct($parent);
		}
		
		public function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Events'))));
			$this->appendSubheading(__('Events'), Widget::Anchor(__('Create New'), URL . '/symphony/blueprints/events/new/', __('Create a new event'), 'create button'));

			$eventsManager = new EventManager($this->_Parent);
			$events = $eventsManager->listAll();

			$sectionManager = new SectionManager($this->_Parent);

			$aTableHead = array(

				array(__('Name'), 'col'),
				array(__('Source'), 'col'),
				array(__('Pages'), 'col'),
				array(__('Authors'), 'col')
			);

			$aTableBody = array();

			if(!is_array($events) || empty($events)){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				
				$bOdd = true;

				foreach($events as $e){

					if ($e['can_parse']) {
						$name = Widget::TableData(
							Widget::Anchor(
								$e['name'],
								URL . '/symphony/blueprints/events/edit/' . $e['handle'] . '/',
								$e['handle']
							)
						);

						$sectionData = $sectionManager->fetch($e['source']);

						$section = Widget::TableData(
							Widget::Anchor(
								$sectionData->_data['name'],
								URL . '/symphony/blueprints/sections/edit/' . $sectionData->_data['id'] . '/',
								$sectionData->_data['handle']
							)
						);
					} else {
						$name = Widget::TableData($e['name']);

						$section = Widget::TableData(__('None'));
					}

					$query = 'SELECT `id`, `title`, `events` FROM tbl_pages WHERE `events` REGEXP "' . $e['handle'] . '"';
					$pages = $this->_Parent->Database->fetch($query);
					$pagelinks = array();

					foreach($pages as $key => $page) {
						$events = explode(',', $page['events']);
						// Avoid false positives. Ideally should be done in the REGEXP above?
						if (in_array($e['handle'], $events)) {
							$pagelinks[] = Widget::Anchor($page['title'], URL . '/symphony/blueprints/pages/edit/' . $page['id'])->generate() . (count($pages) > ($key + 1) ? ((($key + 1) % 6) == 0 ? '<br />' : ', ') : '');
						}
					}
					$pagelinks = Widget::TableData(implode('', $pagelinks));

					$author = $e['author']['name'];

					if (isset($e['author']['website'])) {
						$author = Widget::Anchor($e['author']['name'], General::validateURL($e['author']['website']));

					} else if(isset($e['author']['email'])) {
						$author = Widget::Anchor($e['author']['name'], 'mailto:' . $e['author']['email']);
					}

					$author = Widget::TableData($author);

					$author->appendChild(Widget::Input('items[' . $e['handle'] . ']', null, 'checkbox'));

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
							if (!General::deleteFile(EVENTS . '/event.' . $name . '.php')) {
								$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($name)),Alert::ERROR);
								$canProceed = false;
							}
						}

						if ($canProceed) redirect($this->_Parent->getCurrentPageURL());
						break;
				}
			}

		}

	}
	
?>
