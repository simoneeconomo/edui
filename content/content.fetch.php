<?php

	require_once(TOOLKIT . '/class.ajaxpage.php');

	Class contentExtensionEduiFetch extends AjaxPage{

		public function view(){
			$results = array();

			foreach($_REQUEST as $key => $value) {
				if($key == "type" && $value == "source") {
					$query = "SELECT id, name
					          FROM `tbl_sections`";
				}
				else if($key == "type" && $value == "pages") {
					$query = "SELECT id, title
					          FROM `tbl_pages`";
				}

				$results = $this->_Parent->Database->fetch($query);
			}

			$this->_Result = json_encode($results);
		}
		
		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

?>
