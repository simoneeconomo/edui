<?php
	
	Class extension_edui extends Extension {
		public function about() {
			return array(
				'name'			=> 'Events, Datasources & Utilities Index',
				'version'		=> '0.1',
				'release-date'	=> '2010-08-15',
				'author' => array('name' => 'Simone Economo',
					'website' => 'http://www.lineheight.net',
					'email' => 'my.ekoes@gmail.com'),
				'description'	=> 'Adds separates index pages for events, datasources and utilities.'
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
	}
	
?>
