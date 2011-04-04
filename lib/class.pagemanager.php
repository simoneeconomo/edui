<?php

	class PageManager {

		public function listAll(){
			$query = "SELECT `handle`, `title`
			          FROM `tbl_pages`";

			$results = Symphony::Database()->fetch($query);

			return $results;
		}

		private function linkResource($field, $r_handle, $page_handle) {
			$query = "SELECT `" . $field . "`
			          FROM `tbl_pages`
			          WHERE `handle` = '" . $page_handle . "'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				if (!in_array($r_handle, explode(',', $result))) {
					$result .= "," . $r_handle;

					$query = "UPDATE `tbl_pages`
					          SET `" . $field . "` = '" . $result . "'
					          WHERE `handle` = '" . $page_handle . "'";

					Symphony::Database()->fetch($query);
				}
			}
		}

		public function unlinkResource($field, $r_handle, $page_handle) {
			$query = "SELECT `" . $field . "`
			          FROM `tbl_pages`
			          WHERE `handle` = '" . $page_handle . "'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				if (in_array($r_handle, explode(',', $result))) {
					$result = str_replace($r_handle, '', $result);
					$result = str_replace(',,', ',', $result);

					$query = "UPDATE `tbl_pages`
					          SET `" . $field . "` = '" . $result . "'
					          WHERE `handle` = '" . $page_handle . "'";

					Symphony::Database()->fetch($query);
				}
			}
		}

		public function linkDatasource($d_handle, $page_handle) {
			$this->linkResource("data_sources", $d_handle, $page_handle);
		}

		public function unlinkDatasource($d_handle, $page_handle) {
			$this->unlinkResource("data_sources", $d_handle, $page_handle);
		}

		public function linkEvent($e_handle, $page_handle) {
			$this->linkResource("events", $e_handle, $page_handle);
		}

		public function unlinkEvent($e_handle, $page_handle) {
			$this->unlinkResource("events", $e_handle, $page_handle);
		}

	}


?>
