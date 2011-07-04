<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');

	require_once(EXTENSIONS . '/edui/lib/class.sorting.php');
	require_once(EXTENSIONS . '/edui/lib/class.filtering.php');
	require_once(EXTENSIONS . '/edui/lib/class.pagemanager.php');

	class contentExtensionEduiDatasources extends AdministrationPage {
		public $_errors;
		
		/**
		 *
		 * Flag to detect is site is multilingual
		 * Must have the and extension installed and enabled
		 * @var boolean
		 */
		private $isMultiLangual = false;
		
		/**
		 * 
		 * Private var to hold the current language
		 * @var string
		 */
		private $lg = '';

		public function __construct(&$parent){
			parent::__construct($parent);
			
			// detect if multilangual field AND language redirect is enabled
			$this->isMultiLangual =
					(Symphony::ExtensionManager()->fetchStatus('page_lhandles') == EXTENSION_ENABLED &&
					 Symphony::ExtensionManager()->fetchStatus('language_redirect') == EXTENSION_ENABLED);
					 
			// try to detect language
			if ($this->isMultiLangual) {
				
				// add a ref to the Language redirect
				if ($this->isMultiLangual) {
					require_once (EXTENSIONS . '/language_redirect/lib/class.languageredirect.php');
				}
				
				// current language
				$this->lg = LanguageRedirect::instance()->getLanguageCode();
				
			}

			// if not set, get it from the Symphony Backend
			if (strlen($this->lg) < 0) {
				$this->lg = Lang::get();
			}
		}

		public function __viewIndex(){
			$this->setPageType('table');

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Data Sources'))));

			$this->appendSubheading(
				__('Data Sources'),
				Widget::Anchor(__('Create New'), URL . '/symphony/blueprints/datasources/new/', __('Create a new data source'), 'create button')
			);

			$this->addStylesheetToHead(URL . '/extensions/edui/assets/content.filters.css', 'screen', 80);
			$this->addScriptToHead(URL . '/extensions/edui/assets/content.filters.js', 80);

			$datasourceManager = new DatasourceManager(Administration::instance());
			$sectionManager = new SectionManager(Administration::instance());

			$datasources = $datasourceManager->listAll();

			/* Filtering */

			$filtering = new DatasourcesFiltering();
			$this->Form->appendChild($filtering->displayFiltersPanel($datasources));

			/* Sorting */

			$sorting = new Sorting($datasources, $sort, $order);
			
			/* Pinning */
			$pinSettting = extension_edui::getConfigVal(extension_edui::SETTING_PINNED_DS);
			$pinSettting = explode(',', $pinSettting);
			
			if (count($pinSettting) > 0) {
				
				// reverse the array to get them in order on the page
				$pinSettting = array_reverse($pinSettting, true);
				
				// for all pinned DS
				foreach ($pinSettting as $pinDS) {+
				
					// get the data source key
					$key = str_replace(' ', '_', strtolower(trim($pinDS, ' ') ) );
					
					// does it exists ?
					$res = array_key_exists($key , $datasources);
					if ($res) {
						// cache the current DS
						$d = $datasources[$key];
						// set as pinned
						$d ['pinned'] = true;
						// unset it
						unset($datasources[$key]);
						// prepend it to the begening of the list
						array_unshift($datasources, $d);
					}	
				}
						
			}

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
						$section = Widget::TableData(__('None'), 'inactive');
					}

					$pages = $filtering->getLinkedPages($d['handle']);
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

					$author = $d['author']['name'];

					if (isset($d['author']['website'])) {
						$author = Widget::Anchor($d['author']['name'], General::validateURL($d['author']['website']));
					}
					else if(isset($d['author']['email'])) {
						$author = Widget::Anchor($d['author']['name'], 'mailto:' . $d['author']['email']);
					}

					$author = Widget::TableData($author);
					$author->appendChild(Widget::Input('items[' . $d['handle'] . ']', null, 'checkbox'));

					if (isset($d['pinned']) && $d['pinned']) {
						$name->appendChild(new XMLElement('span', __(' <em>Pinned</em>')));
					}
					
					$aTableBody[] = Widget::TableRow(array($name, $section, $pagelinks, $author), null);

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

			// get all pages in alpha order
			$pages = $this->getHierarchy();

			$group_link = array('label' => __('Link Page'), 'options' => array());
			$group_unlink = array('label' => __('Unlink Page'), 'options' => array());

			$group_link['options'][] = array('link-all-pages', false, __('All'));
			$group_unlink['options'][] = array('unlink-all-pages', false, __('All'));

			foreach($pages as $p) {
				$group_link['options'][] = array('link-page-' . $p['handle'], false, $p['title']);
				$group_unlink['options'][] = array('unlink-page-' . $p['handle'], false, $p['title']);
			}

			$options[] = $group_link;
			$options[] = $group_unlink;

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __actionIndex(){
			if (isset($_POST['action']) && is_array($_POST['action'])) {
				$filtering = new DatasourcesFiltering();

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

								foreach($checked as $handle) {
									if (!General::deleteFile(DATASOURCES . '/data.' . $handle . '.php')) {
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
										$pageManager->unlinkDatasource($handle, $page);
									}
								}
								else {
									$page = str_replace('link-page-', '', $_POST['with-selected']);

									foreach($checked as $handle) {
										$pageManager->linkDatasource($handle, $page);
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
											$pageManager->unlinkDatasource($handle, $page['handle']);
										}
									}
								}
								else {
									foreach($checked as $handle) {
										foreach($pages as $page) {
											$pageManager->linkDatasource($handle, $page['handle']);
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

		
		/**
		 *
		 * Generate a "flat" view of the current page and ancestors
		 * return array of all pages, starting with the current page
		 */
		private function getHierarchy() {
			$flat = array();

			$cols = "id, parent, title, handle";

			if ($this->isMultiLangual && strlen($this->lg) > 0) {
				// modify SQL query
				$cols = "id, parent, page_lhandles_t_$lg as title, page_lhandles_h_$lg as handle";
			}
			
			$pages = array();
			
			// get all pages
			if (Symphony::Database()->query("SELECT $cols FROM `tbl_pages`")) {
				$pages = Symphony::Database()->fetch();
			}
			
			foreach ($pages as $page) {
				
				$pageTree = array();
				
				array_push($pageTree, $page);
			
				// try get all parents
				$cid = (int) $page->parent;
				
				// while we still find parents
				while ($cid > 0) {
					
					$pid = $cid;
					
					// search for prent in array
					for ($i = 0; $i < count($pages) && $pid > 0; $i++) {
						if ($pages[$i]->id == $cid) {
							
							array_push($pageTree, $pages[$i]);
							
							$cid = $subPage->parent;
							$pid = -1;
						}
					}
				}
				
				$this->buildPathAndTitle($pageTree);
				
				// add page in array
				array_push($flat, get_object_vars($pageTree[0]));

			}
			
			usort($flat, array( $this, 'compareTitles' ) );

			return $flat;
		}
		
		protected function compareTitles($a, $b) {
			return strcasecmp ($a['title'], $b['title']);
		}

		/**
		 *
		 * Appends a new field, 'path' in each array in $flat and appends the parents page title to the current one
		 * @param array $flat
		 */
		private function buildPathAndTitle(&$flat) {
			$count = count($flat);

			for ($i = 0; $i < $count; $i++) { // for each element

				// pointer to the path to be build
				$path = '';
				$title = '';

				for ($j = $i; $j < $count; $j++) { // iterate foward in order to build the path

					// handle to be prepend
					$handle = $flat[$j]->handle;

					// if handle if not empty
					if (strlen($handle) > 0) {
						$path = $handle . '/' . $path;
					}
					
					// new title
					$title = $flat[$j]->title . ' - ' . $title;
				}


				if ($this->isMultiLangual && // then path starts with language Code
					strlen($path) > 1 &&
					strlen($this->lg) > 0) {

					// prepand $lg
					$path  = "$this->lg/" . $path;

				}

				// save path in array
				$flat[$i]->path = trim($path, '/');
				$flat[$i]->title = trim($title, ' - ');
			}
		}

	}
	
?>
