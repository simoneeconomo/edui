<?php

	class Sorting {

		public function __construct(&$data, &$sort, &$order) {
			if (isset($_REQUEST['sort']) && is_numeric($_REQUEST['sort'])) {
				$sort = intval($_REQUEST['sort']);
				$order = ($_REQUEST['order'] == 'desc' ? 'desc' : 'asc');
			}
			else {
				$sort = 0;
				$order = 'desc';
			}

			if ($sort == 1)
				Sorting::sortBySource($order, $data);
			else if ($sort == 3)
				Sorting::sortByAuthor($order, $data);
			else
				Sorting::sortByName($order, $data);
		}

		public static function sortByName($order, &$data = array()) {
			if ($order == 'asc') krsort($data);

			return $data;
		}

		public static function sortBySource($order, &$data = array()) {
			foreach ($data as $key => $about) {
				$source[$key] = $about['type'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($source, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		public static function sortByAuthor($order, &$data = array()) {
			foreach ($data as $key => $about) {
				$author[$key] = $about['author']['name'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($author, $sort, $label, SORT_ASC, $data);

			return $data;
		}

	}

?>
