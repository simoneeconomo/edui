<?php
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	/*
	Copyight: Solutions Nitriques 2011
	License: MIT, see the LICENCE file
	*/
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');

	require_once(EXTENSIONS . '/edui/lib/class.sorting.php');
	require_once(EXTENSIONS . '/edui/lib/class.filtering.php');
	require_once(EXTENSIONS . '/edui/lib/class.pagemanager.php');
	
	/**
	 * 
	 * Abstract class that encapsulate the basic need for a EDUI page
	 * @author Nicolas
	 *
	 */
	abstract class EDUIPage extends AdministrationPage {
		
		/**
		 * Public property that holds errors informations
		 * 
		 * @var Array
		 * @property
		 */
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
		
		protected function registerClientRessources() {
			$this->addStylesheetToHead(URL . '/extensions/edui/assets/content.filters.css', 'screen', 80);
			$this->addScriptToHead(URL . '/extensions/edui/assets/content.filters.js', 80);
			$this->registerPinClientRessource();
		}
		
		protected function registerPinClientRessource() {
			$this->addScriptToHead(URL . '/extensions/edui/assets/content.pin.js', 80);
		}
		
		/**
		 * 
		 * Method that moves pinned element at the beginning of the array
		 * @param string $settingKey
		 * @param array $elements
		 */
		protected function pinElements($settingKey, array &$elements) {
			$pinSettting = extension_edui::getConfigVal($settingKey);
			$pinSettting = explode(',', $pinSettting);
			
			if (count($pinSettting) > 0) {
				
				// reverse the array to get them in order on the page
				$pinSettting = array_reverse($pinSettting, true);
				
				// for all pinned elements
				foreach ($pinSettting as $pinned) {
				
					// get the key
					$key = str_replace(' ', '_', strtolower(trim($pinned)));
					
					if (strlen($key) > 0) {
						// does it exists ?
						if (array_key_exists($key, $elements)) {
							// cache the current element
							$e = $elements[$key];
							// set as pinned
							$e ['pinned'] = true;
							// unset it
							unset($elements[$key]);
							// prepend it to the begening of the list
							array_unshift($elements, $e);
						}	
					}
				}
						
			}
		}
		
		/**
		 * 
		 * Pin a $checked elements into the a setting
		 * @param string $settingKey
		 * @param array $checked
		 */
		protected function __pin($settingKey, $checked) {			
			$newPins = '';
			$pinSettting = extension_edui::getConfigVal($settingKey);
			
			foreach($checked as $handle) {
				// if not already pinned
				// 0 !== FALSE but 0 == FALSE
				if (strpos($pinSettting, $handle) === FALSE) {
					$newPins .= ', ' . $handle;
				}
			}
			
			$newPins = trim(trim($newPins, ','));
			
			if (strlen($pinSettting) > 0) {
				$pinSettting .= ',';
			}
			
			$pinSettting .= $newPins;
			
			// save
			// set config                    (name, value, group)
			Symphony::Configuration()->set($settingKey, $pinSettting, extension_edui::SETTING_GROUP);
			Administration::instance()->saveConfig();
		}
		
		/**
		 * 
		 * Unpin a $checked elements into the a setting
		 * @param string $settingKey
		 * @param array $checked
		 */
		protected function __unpin($settingKey, $checked) {
			$pinSettting = extension_edui::getConfigVal($settingKey);
			
			$ex_pinSettting = explode(',', $pinSettting);
			
			//clean up
			$x = 0;
			foreach ($ex_pinSettting as $pinSet) {
				$pinSet = str_replace(' ', '', trim($pinSet));
				
				if (strlen($pinSet) > 0) {
					// trim elements
					$ex_pinSettting[$x] = $pinSet;
					$x++;
				} else {
					// removes empty elements
					array_splice($ex_pinSettting, $x, 1);
				}
			}

			// actual unpin
			foreach($checked as $handle) {

				$idx = array_search($handle, $ex_pinSettting, false);
				
				// (0 != FALSE) -> false
				// (0 !== FALSE) -> true
				if ($idx !== FALSE) {
					array_splice($ex_pinSettting, $idx, 1);
				}
			}
			
			// the a string back
			$pinSettting = implode(',', $ex_pinSettting);
			
			// save
			// set config                    (name, value, group)
			Symphony::Configuration()->set($settingKey, $pinSettting, extension_edui::SETTING_GROUP);
			Administration::instance()->saveConfig();
		}
		
		/**
		 * 
		 * Creates a TableData cell with the pin button in it.
		 * @param array $data
		 */
		protected function createPinNode($data) {
			$img = new XMLElement('img');
			
			$img->setAttribute('style', 'cursor: pointer;');
			
			if (isset($data['pinned']) && $data['pinned']) {
				$img->setAttribute('src', URL . '/extensions/edui/assets/images/unpin.gif');
				$img->setAttribute('class', 'pin unpin');
			} else {
				$img->setAttribute('src', URL . '/extensions/edui/assets/images/pin.gif');
				$img->setAttribute('class', 'pin');
			}
			
			return Widget::TableData( $img );
			
		}
		
		
		
	}