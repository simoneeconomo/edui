<?php

	require_once("class.multilanguage.php");

	class PageManager {

		public function listAll(){
			if (Multilanguage::isMultiLangual()) {
				$lang = Multilanguage::getLanguage();
				$cols = "`id`, `parent`, `title`, `handle`, `page_lhandles_t_$lang` as `title_t`, `page_lhandles_h_$lang` as `handle_t`";
			} else {
				$cols = "`id`, `parent`, `title`, `handle`";
			}
			
			$query = "SELECT $cols
			          FROM `tbl_pages`
			          ORDER BY `title` ASC";
			
			if (Symphony::Database()->query($query)) {
				$pages = Symphony::Database()->fetch();
			}

			$results = array();
			$this->pageWalkRecursive(NULL, $pages, $results);

			return $results;
		}

		private function pageWalkRecursive($parent_id, $pages, &$results) {
			if (!is_array($pages)) return;

			foreach($pages as $page) {
				if ($page->parent == $parent_id) {
					$results[] = array(
						'id' => $page->id,
						'title' => (empty($page->title_t) ? $page->title : $page->title_t),
						'handle' => (empty($page->handle_t) ? $page->handle : $page->handle_t),
						'children' => NULL
					);

					$this->pageWalkRecursive($page->id, $pages,
						$results[count($results) - 1]['children']);
				}
			}
		}

		public function flatView() {
			$pages = $this->listAll();

			$results = array();
			$this->buildFlatView(NULL, $pages, $results);
			
			return $results;
		}
		
		private function buildFlatView($path, $pages, &$results) {
			if (!is_array($pages)) return;

			foreach($pages as $page) {
				$label = ($path == NULL) ? $page['title'] : $path . ' / ' . $page['title'];

				$results[] = array(
					'id' => $page['id'],
					'title' => $label,
					'handle' => $page['handle'],
				);

				$this->buildFlatView($label, $page['children'], $results);
				$label = $path;
			}
		}

		private function linkResource($field, $r_handle, $page_id) {
			$query = "SELECT `$field`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				if (!in_array($r_handle, explode(',', $result))) {

					if (strlen($result) > 0) $result .= ",";
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

				$values = explode(',', $result);
				$idx = array_search($r_handle, $values, false);

				if ($idx !== false) {
					array_splice($values, $idx, 1);
					$result = implode(',', $values);

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

	}


?>
