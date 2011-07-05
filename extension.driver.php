<?php

	Class extension_edui extends Extension {
		
		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Events, Datasources & Utilities Indexes';
		
		/**
		 * Key of the pinned data source setting
		 * @var string
		 */
		const SETTING_PINNED_DS = 'pinned-datasource';

		/**
		 * Key of the group of setting
		 * @var string
		 */
		const SETTING_GROUP = 'edui';
		
		/**
		 * private variable for holding the errors encountered when saving
		 * @var array
		 */
		protected $errors = array();

		public function about() {
			return array(
				'name'			=> self::EXT_NAME,
				'version'		=> '0.6.1',
				'release-date'	=> '2011-07-04',
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
				)
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
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'addCustomPreferenceFieldsets'
				),
				array(
					'page'      => '/system/preferences/',
					'delegate'  => 'Save',
					'callback'  => 'save'
				)
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

				$c = $this->_Parent->Page->Header->getChildren();

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

		
		/**
		 * Delegate handle that adds Custom Preference Fieldsets
		 * @param string $page
		 * @param array $context
		 */
		public function addCustomPreferenceFieldsets($context) {
			// creates the field set
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', self::EXT_NAME));

			// create a paragraph for short intructions
			$p = new XMLElement('p', __('Define here options for EDUI extension'), array('class' => 'help'));

			// append intro paragraph
			$fieldset->appendChild($p);

			// create a wrapper
			$wrapper = new XMLElement('div');
			//$wrapper->setAttribute('class', 'group');

			// error wrapper
			$err_wrapper = new XMLElement('div');

			// append labels to field set
			$wrapper->appendChild($this->generateField(self::SETTING_PINNED_DS, 'Pinned DS <em>seperated by ,</em>'));

			// append field before errors
			$err_wrapper->appendChild($wrapper);

			// error management
			if (count($this->errors) > 0) {
				// set css and anchor
				$err_wrapper->setAttribute('class', 'invalid');
				$err_wrapper->setAttribute('id', 'error');

				foreach ($this->errors as $error) {
					// adds error message
					$err = new XMLElement('p', $error);

					// append to $wrapper
					$err_wrapper->appendChild($err);
				}
			}

			// wrapper into fieldset
			$fieldset->appendChild($err_wrapper);

			// adds the field set to the wrapper
			$context['wrapper']->appendChild($fieldset);
		}
		
		/**
		 * Quick utility function to make a input field+label
		 * @param string $settingName
		 * @param string $textKey
		 */
		public function generateField($settingName, $textKey) {
			// create the label and the input field
			$label = Widget::Label();
			$input = Widget::Input(
						'settings[' . self::SETTING_GROUP . '][' . $settingName .']',
						self::getConfigVal($settingName),
						'text'
					);

			// set the input into the label
			$label->setValue(__($textKey). ' ' . $input->generate());

			return $label;
		}
		
		/**
		 *
		 * Utility function that returns settings from this extensions settings group
		 * @param string $key
		 */
		public static function getConfigVal($key) {
			return Symphony::Configuration()->get($key, self::SETTING_GROUP);
		}
		
		/**
		 * Delegate handle that saves the preferences
		 * Saves settings and cleans the database acconding to the new settings
		 * @param array $context
		 */
		public function save($context){
			self::saveOne($context, self::SETTING_PINNED_DS, true);
		}
		
		/**
		 *
		 * Save one parameter
		 * @param array $context
		 * @param string $key
		 * @param string $autoSave @optional
		 */
		public static function saveOne($context, $key, $autoSave=true){
			// get the input
			$input = $context['settings'][self::SETTING_GROUP][$key];

			// set config                    (name, value, group)
			Symphony::Configuration()->set($key, $input, self::SETTING_GROUP);

			// save it
			if ($autoSave) {
				Administration::instance()->saveConfig();
			}
			
		}
	}
	
?>
