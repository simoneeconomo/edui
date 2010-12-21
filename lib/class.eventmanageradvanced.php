<?php

	require_once(TOOLKIT . '/class.eventmanager.php');

	Class EventManagerAdvanced extends EventManager{

		const FILTER_IS       = 0;
		const FILTER_CONTAINS = 1;

		static private $_events = array();

		public function listAll(){
			if (empty(self::$_events))
				return self::$_events = parent::listAll();

			return self::$_events;
		}

		public function sortByName($order, $data = array()) {
			if ($order == 'asc') krsort($data);

			return $data;
		}

		public function sortBySource($order, $data = array()) {
			foreach ($data as $key => $about) {
				$source[$key] = $about['type'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($source, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		public function sortByAuthor($order, $data = array()) {
			foreach ($data as $key => $about) {
				$author[$key] = $about['author']['name'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($author, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		public function filterByName($value, $mode = self::FILTER_IS, $data = array()) {
			$result = array();

			if ($mode == self::FILTER_IS) {
				foreach($data as $d) {
					if ($d['name'] == $value)
						$result[$d['handle']] = $d;
				}
			}
			else {
				foreach($data as $d) {
					if (stristr($d['name'], $value))
						$result[$d['handle']] = $d;
				}
			}

			return $result;
		}

		public function filterByPages($value, $mode = self::FILTER_IS, $data = array()) {
			$result = array();
			$values = explode(',', $value);

			foreach($data as $d) {
				$pages = $this->getLinkedPages($d['handle']);
				$accum = true;

				if ($mode == self::FILTER_IS && $value == '' && empty($pages)) {
					$result[] = $d;
					break;
				}
				else if ($mode == self::FILTER_IS && count($values) != count($pages)) continue;

				foreach($values as $v) {
					if (!$accum) break;

					$accum = $accum && isset($pages[$v]);
				}

				if ($accum) $result[] = $d;
			}

			return $result;
		}

		public function filterBySource($value, $mode = self::FILTER_IS, $data = array()) {
			$result = array();

			if ($mode == self::FILTER_IS) {
				foreach($data as $d) {
					if ($d['type'] == $value)
						$result[$d['handle']] = $d;
				}
			}
			else {
				foreach($data as $d) {
					if (stristr($d['type'], $value))
						$result[$d['handle']] = $d;
				}
			}

			return $result;
		}

		public function filterByAuthor($value, $mode = self::FILTER_IS, $data = array()) {
			$result = array();

			if ($mode == self::FILTER_IS) {
				foreach($data as $d) {
					if ($d['author']['name'] == $value)
						$result[$d['handle']] = $d;
				}
			}
			else {
				foreach($data as $d) {
					if (stristr($d['author']['name'], $value))
						$result[$d['handle']] = $d;
				}
			}

			return $result;
		}

		public function getLinkedPages($handle) {
			if (!$handle) return array();

			$query = 'SELECT `id`, `title`
			          FROM tbl_pages
			          WHERE `events` REGEXP "' . $handle . ',|,' . $handle . ',|' . $handle . '$"';
			$pages = $this->_Parent->Database->fetch($query);

			$result = array();

			foreach($pages as $p) {
				$result[$p['id']] = $p['title'];
			}

			return $result;
		}

	}

?>
