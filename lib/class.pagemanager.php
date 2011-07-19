<?php

	class PageManager {

		public function listAll(){
			$query = "SELECT `handle`, `title`, `id`
			          FROM `tbl_pages`
			          ORDER BY `sortorder` ASC";

			$results = Symphony::Database()->fetch($query);

			return $results;
		}

		private function linkResource($field, $r_handle, $page_id) {
			$query = "SELECT `$field`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				if (!in_array($r_handle, explode(',', $result))) {

					// add only if requiered
					if (strlen($result) > 0) {
						$result .= ",";
					}

					// append new ressource
					$result .= $r_handle;

					$query = "UPDATE `tbl_pages`
					          SET `$field` = '" . MySQL::cleanValue($result) . "'
					          WHERE `id` = '$page_id'";

					Symphony::Database()->query($query);
				}
			}
		}

		public function unlinkResource($field, $r_handle, $page_id) {
			$query = "SELECT `$field`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				// Do not use str_replace since this may replace some good infos.
				// ex.: When unlinking DS videos, another DS named video_2
				//      will be transform into _2, which breaks the front end
				//$result = str_replace($r_handle, '', $result);
				//$result = str_replace(',,', ',', $result);

				$ex_result = explode(',', $result);

				$idx = array_search($r_handle, $ex_result, true);

				if ($idx !== FALSE) { // I got fooled again by 0 == FALSE but 0 !== FALSE

					// remove the element from the array
					array_splice($ex_result, $idx, 1);

					// get the string back
					$result = implode(',', $ex_result);

					$query = "UPDATE `tbl_pages`
					          SET `$field` = '" . MySQL::cleanValue($result) . "'
					          WHERE `id` = '$page_id'";

					Symphony::Database()->query($query);
				}
			}
		}

		public function linkDatasource($d_handle, $page_id) {
			$this->linkResource("data_sources", $d_handle, $page_id);
		}

		public function unlinkDatasource($d_handle, $page_id) {
			$this->unlinkResource("data_sources", $d_handle, $page_id);
		}

		public function linkEvent($e_handle, $page_id) {
			$this->linkResource("events", $e_handle, $page_id);
		}

		public function unlinkEvent($e_handle, $page_id) {
			$this->unlinkResource("events", $e_handle, $page_id);
		}


		/**
		 *
		 * Appends a new field, 'path' in each array in $flat and appends the parents page title to the current one
		 * @param array $flat
		 */
		public function buildPathAndTitle(&$flat) {
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

		/**
		 *
		 * Generate a "flat" view of the current page and ancestors
		 * return array of all pages, starting with the current page
		 */
		public function getHierarchy() {
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

	}


?>
