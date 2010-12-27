<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');

	require_once(EXTENSIONS . '/edui/lib/class.sorting.php');
	require_once(EXTENSIONS . '/edui/lib/class.filtering.php');

	class contentExtensionEduiDatasources extends AdministrationPage {
		public $_errors;

		public function __construct(&$parent){
			parent::__construct($parent);
		}

		public function __viewIndex(){
			$this->setPageType('table');

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Data Sources'))));

			$this->appendSubheading(
				__('Data Sources'),
				Widget::Anchor(__('Create New'), URL . '/symphony/blueprints/datasources/new/', __('Create a new data source'), 'create button')
			);

			$this->addStylesheetToHead(URL . '/extensions/edui/assets/content.filters.css', 'screen', 80);
			$this->addScriptToHead(URL . '/extensions/edui/assets/jquery.sb.min.js', 80);
			$this->addScriptToHead(URL . '/extensions/edui/assets/content.filters.js', 80);

			$datasourceManager = new DatasourceManager($this->_Parent);
			$sectionManager = new SectionManager($this->_Parent);

			$datasources = $datasourceManager->listAll();

			/* Filtering */

			$filtering = new Filtering($this->_Parent);
			$this->Form->appendChild($filtering->displayFiltersPanel($datasources));

			/* Sorting */

			$sorting = new Sorting($datasources, $sort, $order);

			/* Columns */

			$columns = array(
				array(
					'label' => __('Name'),
					'sortable' => true
				),
				array(
					'label' => __('Source'),
					'sortable' => true
				),
				array(
					'label' => __('Pages'),
					'sortable' => false
				),
				array(
					'label' => __('Author'),
					'sortable' => true
				)
			);

			$aTableHead = array();

			foreach($columns as $i => $c) {
				if ($c['sortable']) {

					if ($i == $sort) {
						$link = '?sort='.$i.'&amp;order='. ($order == 'desc' ? 'asc' : 'desc') . (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : '');
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(($order == 'desc' ? __('ascending') : __('descending')), strtolower($c['label']))),
							'active'
						);
					}
					else {
						$link = '?sort='.$i.'&amp;order=asc' . (isset($_REQUEST['filter']) ? '&amp;filter=' . $_REQUEST['filter'] : '');
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(__('ascending'), strtolower($c['label'])))
						);
					}

				}
				else {
					$label = $c['label'];
				}

				$aTableHead[] = array($label, 'col');
			}

			/* Body */

			$aTableBody = array();

			if (!is_array($datasources) || empty($datasources)) {
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}
			else {
				$bOdd = true;

				foreach($datasources as $d) {
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
						}
						else {
							$section = Widget::TableData($d['type']);
						}
					}
					else {
						$name = Widget::TableData(
							Widget::Anchor(
								$d['name'],
								URL . '/symphony/blueprints/datasources/info/' . $d['handle'] . '/',
								$d['handle']
							)
						);
						$section = Widget::TableData(__('None'));
					}

					$pages = $filtering->getDatasourceLinkedPages($d['handle']);
					$pagelinks = array();

					$i = 0;
					foreach($pages as $key => $value) {
						++$i;
						$pagelinks[] = Widget::Anchor(
							$value,
							URL . '/symphony/blueprints/pages/edit/' . $key
						)->generate() . (count($pages) > $i ? (($i % 6) == 0 ? '<br />' : ', ') : '');
					}

					$pages = implode('', $pagelinks);

					$pagelinks = Widget::TableData($pages == "" ? __('None') : $pages);
					$author = $d['author']['name'];

					if (isset($d['author']['website'])) {
						$author = Widget::Anchor($d['author']['name'], General::validateURL($d['author']['website']));
					}
					else if(isset($d['author']['email'])) {
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

			/* Actions */

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
			if (isset($_POST['action']) && is_array($_POST['action'])) {
				$filtering = new Filtering($this->_Parent);

				foreach ($_POST['action'] as $key => $action) {
					if ($key == 'process-filters') {
						$string = $filtering->buildFiltersString();

						redirect($this->_Parent->getCurrentPageURL() . $string);
					}
					else if (strpos($key, 'filter-skip-') !== false) {
						$filter_to_skip = str_replace('filter-skip-', '', $key);
						$string = $filtering->buildFiltersString($filter_to_skip);

						redirect($this->_Parent->getCurrentPageURL() . $string);
					}
					else {
						$checked = @array_keys($_POST['items']);

						if (is_array($checked) && !empty($checked)) {

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

							}

						}
					}
				}

			}
		}

	}
	
?>
