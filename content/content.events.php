<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');

	require_once(EXTENSIONS . '/edui/lib/class.sorting.php');
	require_once(EXTENSIONS . '/edui/lib/class.filtering.php');
	require_once(EXTENSIONS . '/edui/lib/class.pagemanager.php');

	class contentExtensionEduiEvents extends AdministrationPage {
		public $_errors;

		public function __construct(&$parent){
			parent::__construct($parent);
		}

		public function __viewIndex(){
			$this->setPageType('table');

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Events'))));

			$this->appendSubheading(
				__('Events'),
				Widget::Anchor(__('Create New'), URL . '/symphony/blueprints/events/new/', __('Create a new event'), 'create button')
			);

			$this->addStylesheetToHead(URL . '/extensions/edui/assets/content.filters.css', 'screen', 80);
			$this->addScriptToHead(URL . '/extensions/edui/assets/content.filters.js', 80);

			$eventManager = new EventManager($this->_Parent);
			$sectionManager = new SectionManager($this->_Parent);

			$events = $eventManager->listAll();

			/* Filtering */

			$filtering = new EventsFiltering();
			$this->Form->appendChild($filtering->displayFiltersPanel($events));

			/* Sorting */

			$sorting = new Sorting($events, $sort, $order);

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
					'label' => __('Release Date'),
					'sortable' => true
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
						$link = '?sort='.$i.'&amp;order='. ($order == 'desc' ? 'asc' : 'desc');
						$label = Widget::Anchor(
							$c['label'], $link,
							__('Sort by %1$s %2$s', array(($order == 'desc' ? __('ascending') : __('descending')), strtolower($c['label']))),
							'active'
						);
					}
					else {
						$link = '?sort='.$i.'&amp;order=asc';
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

			if (!is_array($events) || empty($events)) {
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}
			else{
				$bOdd = true;

				foreach($events as $e) {

					if ($e['can_parse']) {
						$name = Widget::TableData(
							Widget::Anchor(
								$e['name'],
								URL . '/symphony/blueprints/events/edit/' . $e['handle'] . '/',
								$e['handle']
							)
						);

						$sectionData = $sectionManager->fetch($e['source']);

						if ( $sectionData !== false ) {
							$section = Widget::TableData(
								Widget::Anchor(
									$sectionData->get('name'),
									URL . '/symphony/blueprints/sections/edit/' . $sectionData->get('id') . '/',
									$sectionData->get('handle')
								)
							);
						}
						else {
							$section = Widget::TableData(__('Not found'), 'inactive');
						}
					}
					else {
						$name = Widget::TableData(
							Widget::Anchor(
								$e['name'],
								URL . '/symphony/blueprints/events/info/' . $e['handle'] . '/',
								$e['handle']
							)
						);
						$section = Widget::TableData(__('None'), 'inactive');
					}

					$pages = $filtering->getLinkedPages($e['handle']);
					$pagelinks = array();

					$i = 0;
					foreach($pages as $key => $value) {
						++$i;
						$pagelinks[] = Widget::Anchor(
							$value,
							URL . '/symphony/blueprints/pages/edit/' . $key
						)->generate() . (count($pages) > $i ? (($i % 10) == 0 ? '<br />' : ', ') : '');
					}

					$pages = implode('', $pagelinks);

					if ($pages == "")
						$pagelinks = Widget::TableData(__('None'), 'inactive');
					else
						$pagelinks = Widget::TableData($pages);

					$datetimeobj = new DateTimeObj();
					$releasedate = Widget::TableData($datetimeobj->get(
						__SYM_DATETIME_FORMAT__,
						strtotime($d['release-date']))
					);

					$author = $e['author']['name'];

					if (isset($e['author']['website'])) {
						$author = Widget::Anchor($e['author']['name'], General::validateURL($e['author']['website']));
					}
					else if(isset($e['author']['email'])) {
						$author = Widget::Anchor($e['author']['name'], 'mailto:' . $e['author']['email']);
					}

					$author = Widget::TableData($author);
					$author->appendChild(Widget::Input('items[' . $e['handle'] . ']', null, 'checkbox'));

					$aTableBody[] = Widget::TableRow(array($name, $section, $pagelinks, $releasedate, $author), null);

				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead), 
				NULL, 
				Widget::TableBody($aTableBody),
				'selectable'
			);

			$this->Form->appendChild($table);

			/* Actions */

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('delete', false, __('Delete'), 'confirm'),
			);

			$pageManager = new PageManager($this->_Parent);
			$pages = $pageManager->listAll();

			$group_link = array('label' => __('Link Page'), 'options' => array());
			$group_unlink = array('label' => __('Unlink Page'), 'options' => array());

			$group_link['options'][] = array('link-all-pages', false, __('All'));
			$group_unlink['options'][] = array('unlink-all-pages', false, __('All'));

			foreach($pages as $p) {
				$group_link['options'][] = array('link-page-' . $p['id'], false, $p['title']);
				$group_unlink['options'][] = array('unlink-page-' . $p['id'], false, $p['title']);
			}

			$options[] = $group_link;
			$options[] = $group_unlink;

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __actionIndex(){
			if (isset($_POST['action']) && is_array($_POST['action'])) {
				$filtering = new EventsFiltering();

				foreach ($_POST['action'] as $key => $action) {
					if ($key == 'process-filters') {
						$string = $filtering->buildFiltersString();

						redirect(Administration::instance()->getCurrentPageURL() . $string);
					}
					else if (strpos($key, 'filter-skip-') !== false) {
						$filter_to_skip = str_replace('filter-skip-', '', $key);
						$string = $filtering->buildFiltersString($filter_to_skip);

						redirect(Administration::instance()->getCurrentPageURL() . $string);
					}
					else {
						$checked = ($_POST['items']) ? @array_keys($_POST['items']) : NULL;

						if (is_array($checked) && !empty($checked)) {

							if ($_POST['with-selected'] == 'delete') {
								$canProceed = true;

								foreach($checked as $name) {
									if (!General::deleteFile(EVENTS . '/event.' . $name . '.php')) {
										$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($name)),Alert::ERROR);
										$canProceed = false;
									}
								}

								if ($canProceed) redirect(Administration::instance()->getCurrentPageURL());
							}
							else if(preg_match('/^(?:un)?link-page-/', $_POST['with-selected'])) {
								$pageManager = new PageManager();

								if (substr($_POST['with-selected'], 0, 2) == 'un') {
									$page = str_replace('unlink-page-', '', $_POST['with-selected']);

									foreach($checked as $handle) {
										$pageManager->unlinkEvent($handle, $page);
									}
								}
								else {
									$page = str_replace('link-page-', '', $_POST['with-selected']);

									foreach($checked as $handle) {
										$pageManager->linkEvent($handle, $page);
									}
								}

								redirect(Administration::instance()->getCurrentPageURL());
							}
							else if(preg_match('/^(?:un)?link-all-pages$/', $_POST['with-selected'])) {
								$pageManager = new PageManager();
								$pages = $pageManager->listAll();

								if (substr($_POST['with-selected'], 0, 2) == 'un') {
									foreach($checked as $handle) {
										foreach($pages as $page) {
											$pageManager->unlinkEvent($handle, $page['handle']);
										}
									}
								}
								else {
									foreach($checked as $handle) {
										foreach($pages as $page) {
											$pageManager->linkEvent($handle, $page['handle']);
										}
									}
								}

								redirect(Administration::instance()->getCurrentPageURL());
							}

						}
					}
				}

			}
		}

	}
	
?>
