<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(EXTENSIONS . '/edui/lib/class.eventmanageradvanced.php');

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
			$this->addScriptToHead(URL . '/extensions/edui/assets/jquery.sb.min.js', 80);
			$this->addScriptToHead(URL . '/extensions/edui/assets/content.filters.js', 80);

			$eventManager = new EventManagerAdvanced($this->_Parent);
			$sectionManager = new SectionManager($this->_Parent);

			$events = $eventManager->listAll();

			/* Filtering */

			$filters_panel = new XMLElement('div', null, array('class' => 'filters'));
			$filters_panel->appendChild(new XMLElement('h3', __('Filters')));

			$filters_count = 0;

			if(isset($_REQUEST['filter'])){
				$filters = explode(';', $_REQUEST['filter']);

				foreach($filters as $f) {
					if ($f == '') continue;

					list($key, $value) = explode(':', $f);

					$mode = ($key{strlen($key)-1} == "*")
						? EventManagerAdvanced::FILTER_CONTAINS
						: EventManagerAdvanced::FILTER_IS;

					$key = ($key{strlen($key)-1} == "*") ? substr($key, 0, strlen($key)-1) : $key;
					$value = rawurldecode($value);

					$filter_box = new XMLElement('div', null, array('class' => 'filter'));

					$filter_keys = array(
						array('name', false, __('Name')),
						array('source', false, __('Source')),
						array('pages', false, __('Pages')),
						array('author', false, __('Author')),
					);

					$filter_modes = array(
						array('0', ($mode == EventManagerAdvanced::FILTER_IS),       __('is')),
						array('1', ($mode == EventManagerAdvanced::FILTER_CONTAINS), __('contains')),
					);

					switch($key) {
						case 'name':
							$events = $eventManager->filterByName($value, $mode, $events);

							$filter_keys[0][1] = true;
							break;
						case 'source':
							$events = $eventManager->filterBySource($value, $mode, $events);

							$filter_keys[1][1] = true;
							break;
						case 'pages':
							$events = $eventManager->filterByPages($value, $mode, $events);

							$filter_keys[2][1] = true;
							break;
						case 'author':
							$events = $eventManager->filterByAuthor($value, $mode, $events);

							$filter_keys[3][1] = true;
							break;
					}

					$filter_box->appendChild(Widget::Select('filter-key-' . $filters_count, $filter_keys));
					$filter_box->appendChild(Widget::Select('filter-mode-' . $filters_count, $filter_modes));
					$filter_box->appendChild(Widget::Input('filter-value-' . $filters_count, $value));
					$filter_box->appendChild(Widget::Input('action[filter-skip-' . $filters_count .']', __('Remove filter'), 'submit', array('class' => 'button delete')));

					$filters_panel->appendChild($filter_box);
					++$filters_count;
				}

			}

			$filter_box = new XMLElement('div', null, array('class' => 'filter default'));

			$filter_keys = array(
				array('name', false, __('Name')),
				array('source', false, __('Source')),
				array('pages', false, __('Pages')),
				array('author', false, __('Author')),
			);

			$filter_box->appendChild(Widget::Select('filter-key-' . $filters_count, $filter_keys));

			$filter_modes = array(
				array('0', false, __('is')),
				array('1', false, __('contains')),
			);

			$filter_box->appendChild(Widget::Select('filter-mode-' . $filters_count, $filter_modes));

			$filter_box->appendChild(Widget::Input('filter-value-' . $filters_count));

			$filters_panel->appendChild($filter_box);
			$filters_panel->appendChild(Widget::Input('action[process-filters]', __('Apply'), 'submit', array('class' => 'button apply')));;

			$this->Form->appendChild($filters_panel);

			/* Sorting */

			if (isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])) {
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');
			}
			else {
				$sort = 0;
				$order = 'desc';
			}

			if ($sort == 1)
				$events = $eventManager->sortBySource($order, $events);
			else if ($sort == 3)
				$events = $eventManager->sortByAuthor($order, $events);
			else
				$events = $eventManager->sortByName($order, $events);

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

			if (isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])) {
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');
			}
			else {
				$sort = 0;
				$order = 'desc';
			}

			if ($sort == 1) {
				$events = $eventManager->sortBySource($order, $events);
			}
			else if ($sort == 3) {
				$events = $eventManager->sortByAuthor($order, $events);
			}
			else {
				$events = $eventManager->sortByName($order, $events);
			}

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

						$section = Widget::TableData(
							Widget::Anchor(
								$sectionData->_data['name'],
								URL . '/symphony/blueprints/sections/edit/' . $sectionData->_data['id'] . '/',
								$sectionData->_data['handle']
							)
						);
					}
					else {
						$name = Widget::TableData($e['name']);
						$section = Widget::TableData(__('None'));
					}

					$pages = $eventManager->getLinkedPages($e['handle']);
					$pagelinks = array();

					$i = 0;
					foreach($pages as $key => $value) {
						++$i;
						$pagelinks[] = Widget::Anchor(
							$value,
							URL . '/symphony/blueprints/pages/edit/' . $key
						)->generate() . (count($pages) > $i ? (($i % 6) == 0 ? '<br />' : ', ') : '');
					}

					$pagelinks = Widget::TableData(implode('', $pagelinks));
					$author = $e['author']['name'];

					if (isset($e['author']['website'])) {
						$author = Widget::Anchor($e['author']['name'], General::validateURL($e['author']['website']));
					}
					else if(isset($e['author']['email'])) {
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

				foreach ($_POST['action'] as $key => $action) {
					if ($key == 'process-filters') {
						$string = "?filter=";

						for ($i = 0; isset($_POST['filter-key-' . $i]); ++$i) {
							if ($_POST['filter-value-' . $i] == '') continue;

							$key = $_POST['filter-key-' . $i];
							$mode = (intval($_POST['filter-mode-' . $i]) == EventManagerAdvanced::FILTER_IS) ? ':' : '*:';
							$value = rawurlencode($_POST['filter-value-' . $i]);
							$string .= $key . $mode . $value .";";
						}

						redirect($this->_Parent->getCurrentPageURL() . $string);
					}
					else if (strpos($key, 'filter-skip-') !== false) {
						$filter_to_skip = str_replace('filter-skip-', '', $key);
						$string = "?filter=";

						for ($i = 0; isset($_POST['filter-key-' . $i]); ++$i) {
							if ($i == $filter_to_skip || $_POST['filter-value-' . $i] == '') continue;

							$key = $_POST['filter-key-' . $i];
							$mode = (intval($_POST['filter-mode-' . $i]) == EventManagerAdvanced::FILTER_IS) ? ':' : '*:';
							$value = rawurlencode($_POST['filter-value-' . $i]);
							$string .= $key . $mode . $value .";";
						}

						redirect($this->_Parent->getCurrentPageURL() . $string);
					}
					else {
						$checked = @array_keys($_POST['items']);

						if (is_array($checked) && !empty($checked)) {

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

			}
		}

	}
	
?>
