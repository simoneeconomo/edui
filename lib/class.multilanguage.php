<?php

	class Multilanguage {

		public static function isMultilangual() {
			return (
				Symphony::ExtensionManager()->fetchStatus('page_lhandles') == EXTENSION_ENABLED
				&& Symphony::ExtensionManager()->fetchStatus('language_redirect') == EXTENSION_ENABLED
			);
		}
		
		public static function getLanguage() {
			if (self::isMultilangual()) {
				require_once (EXTENSIONS . '/language_redirect/lib/class.languageredirect.php');
				$lang = LanguageRedirect::instance()->getLanguageCode();
			}

			return (isset($lang) && !empty($lang) ? $lang : Lang::get());
		}

	}

?>
