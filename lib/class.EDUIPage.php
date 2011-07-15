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
		
		protected function pinElements($settingKey, array &$elements) {
			$pinSettting = extension_edui::getConfigVal($settingKey);
			$pinSettting = explode(',', $pinSettting);
			
			//var_dump($elements);
			//die();
			
			if (count($pinSettting) > 0) {
				
				// reverse the array to get them in order on the page
				$pinSettting = array_reverse($pinSettting, true);
				
				// for all pinned elements
				foreach ($pinSettting as $pinned) {
				
					// get the key
					$key = str_replace(' ', '_', strtolower(trim($pinned, ' ') ) );
					
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
		
		protected function __pin($settingKey, $checked) {			
			$newPins = '';
			$pinSettting = extension_edui::getConfigVal($settingKey);
			
			foreach($checked as $handle) {
				// if not already pinned
				if (!strpos($pinSettting, $handle)) {
					$newPins .= ', ' . $handle;
				}
			}
			
			$newPins = trim(trim($newPins, ','), ' ');
			
			if (strlen($pinSettting) > 0) {
				$pinSettting .= ', ';
			}
			
			$pinSettting .= $newPins;
			
			// save
			// set config                    (name, value, group)
			Symphony::Configuration()->set($settingKey, $pinSettting, extension_edui::SETTING_GROUP);
			Administration::instance()->saveConfig();

		}
		
		protected function __unpin($settingKey, $checked) {
			$pinSettting = extension_edui::getConfigVal($settingKey);
			
			foreach($checked as $handle) {
				if (strpos($pinSettting, $handle) > -1) {
					$pinSettting = str_replace($handle, '', $pinSettting);
				}
			}
			
			// clean up
			$pinSettting = str_replace(', ,', ',', $pinSettting);
			$pinSettting = str_replace('  ', ' ', $pinSettting);
			$pinSettting = str_replace(',,', ',', $pinSettting);
			
			// save
			// set config                    (name, value, group)
			Symphony::Configuration()->set($settingKey, $pinSettting, extension_edui::SETTING_GROUP);
			Administration::instance()->saveConfig();
		}
		
		protected function createPinnedNode() {
			return new XMLElement('span', __(' <em>Pinned</em>'));
		}
		
	}