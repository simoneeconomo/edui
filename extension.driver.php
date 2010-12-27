<?php

	Class extension_edui extends Extension {

		public function about() {
			return array(
				'name'			=> 'Events, Datasources & Utilities Indexes',
				'version'		=> '0.4.5',
				'release-date'	=> '2010-12-27',
				'author' => array('name' => 'Simone Economo',
					'website' => 'http://www.lineheight.net',
					'email' => 'my.ekoes@gmail.com'),
				'description'	=> 'Dinstinct index pages for events, datasources and utilities.'
			);
		}

		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('Blueprints'),
					'name'	=> __('Events'),
					'link'	=> '/events/'
				),
				array(
					'location'	=> __('Blueprints'),
					'name'	=> __('Data Sources'),
					'link'	=> '/datasources/'
				),
				array(
					'location'	=> __('Blueprints'),
					'name'	=> __('Utilities'),
					'link'	=> '/utilities/'
				),
			);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'NavigationPreRender',
					'callback' => 'deleteComponentsItem'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'setRedirects'
				),
			);
		}

		public function deleteComponentsItem($context) {
			foreach ($context['navigation'] as &$menu) {
				if ($menu['name'] == __('Blueprints')) {
					for ($i = 0; $i < count($menu['children']); ++$i) {
						if ($menu['children'][$i]['name'] == __('Components')) {
							array_splice($menu['children'], $i, 1);
						}
					}
				}
			}

		}

		public function setRedirects($context) {
			$links = array(
				'blueprintsdatasources' => 'extension/edui/datasources/',
				'blueprintsevents' => 'extension/edui/events/',
				'blueprintsutilities' => 'extension/edui/utilities/',
			);

			$callback = $this->_Parent->getPageCallback();

			if ($callback['driver'] == 'blueprintscomponents') {
				foreach ($links as $key => $value) {
					if (file_exists(TMP . '/' . $key . '.tmp')) {
						unlink(TMP . '/' . $key . '.tmp');
						redirect(URL . '/symphony/' . $value);
					}
				}
			}

			else if (in_array($callback['driver'], array_keys($links))) {

				$c = $this->_Parent->Page->Form->getChildren();

				if ($callback['context'][2] && (in_array($callback['context'][2], array('saved', 'created')))) {
					$c[0]->setValue(str_replace('blueprints/components/', $links[$callback['driver']], $c[0]->getValue()));
				}

				if ($_POST['action'] && array_key_exists('delete_custom', $_POST['action'])) {
					touch(TMP . '/' . $callback['driver'] . '.tmp');

					$_POST['action']['delete'] = $_POST['action']['delete_custom'];

					if (method_exists($this->_Parent->Page, '__actionEdit'))
						$this->_Parent->Page->__actionEdit();
					else
						$this->_Parent->Page->action();
				}

				for($i = count($c) - 1; $i > 0 ; --$i) {
					$child = &$c[$i];
					$attr = $child->getAttributes();

					if ($child->getName() == 'div' && $attr['class'] && $attr['class'] == 'actions') {

						$actions = $child->getChildren();

						foreach($actions as &$a) {
							if ($a->getValue() == __('Delete')) {
								$a->setAttribute('name', 'action[delete_custom]');
							}
						}

					}
				}

			}

		}

	}
	
?>
