<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentEduiFetch extends AdministrationPage{

		public function build() {
			$this->addHeaderToPage('Content-Type', 'application/json');
		}

		public function view(){
			$results = array();

			foreach($_GET as $key => $value) {
				if($key == "mode" && $value == "sections") {
					$query = "SELECT id, title
					          FROM `tbl_pages`";
				}
				else if($key == "mode" && $value == "pages") {
					$query = "SELECT id, name
					          FROM `tbl_pages`";
				}

				$results = $this->_Parent->Database->fetch($query);
			}

			$this->_Result = json_encode($results);
		}
		
#		public function generate(){
#			header('Content-Type: application/json');
#			echo $this->_Result;
#			exit;
#		}

	}

?>
