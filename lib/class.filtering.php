<?php

	Class Filtering {

		const FILTER_IS          = 0;
		const FILTER_CONTAINS    = 1;
		const FILTER_NULL        = 2;

		const MODE_DATASOURCES   = 100;
		const MODE_EVENTS        = 200;

		private $_Parent;
		private $_mode;

		public function __construct(&$parent, $mode){
			$this->_Parent = $parent;
			$this->_mode = $mode;
		}

		public static function filterByName($value, $mode = self::FILTER_IS, $data = array()) {
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

		public static function filterByPages($value, $mode = self::FILTER_IS, $data = array()) {
			$result = array();
			$values = explode(',', $value);

			foreach($data as $d) {
				$pages = $this->getLinkedPages($d['handle']);
				$accum = true;

#				if ($mode == self::FILTER_IS && $value == '' && empty($pages)) {
#					$result[] = $d;
#					break;
#				}
				if ($mode == self::FILTER_IS && count($values) != count($pages)) continue;

				foreach($values as $v) {
					if (!$accum) break;

					$accum = $accum && isset($pages[$v]);
				}

				if ($accum) $result[] = $d;
			}

			return $result;
		}

		public static function filterBySource($value, $mode = self::FILTER_IS, $data = array()) {
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

		public static function filterByAuthor($value, $mode = self::FILTER_IS, $data = array()) {
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

			$field = ($this->_mode == self::MODE_DATASOURCES) ? "data_sources" : "events";

			$query = 'SELECT `id`, `title`
			          FROM tbl_pages
			          WHERE `' . $field . '` REGEXP "' . $handle . ',|,' . $handle . ',|' . $handle . '$"';
			$pages = $this->_Parent->Database->fetch($query);

			$result = array();

			foreach($pages as $p) {
				$result[$p['id']] = $p['title'];
			}

			return $result;
		}

		public function displayFiltersPanel(&$data) {
			$filters_panel = new XMLElement('div', null, array('class' => 'filters'));
			$filters_panel->appendChild(new XMLElement('h3', __('Filters')));

			$filters_count = 0;

			if(isset($_REQUEST['filter'])){
				$filters = explode(';', $_REQUEST['filter']);

				foreach($filters as $f) {
					if ($f == '') continue;

					list($key, $value) = explode(':', $f);

					$mode = ($key{strlen($key)-1} == "*")
						? self::FILTER_CONTAINS
						: self::FILTER_IS;

					$key = ($key{strlen($key)-1} == "*") ? substr($key, 0, strlen($key)-1) : $key;
					$value = rawurldecode($value);

					$filter_box = new XMLElement('div', null, array('class' => 'filter'));

					$filter_keys = array(
						array('name', false, __('Name')),
						array('source', false, __('Source')),
						array('pages', false, __('Pages')),
						array('author', false, __('Author')),
					);

					$filter_modes = array(
						array('0', ($mode == self::FILTER_IS),       __('is')),
						array('1', ($mode == self::FILTER_CONTAINS), __('contains')),
					);

					switch($key) {
						case 'name':
							$data = self::filterByName($value, $mode, $data);

							$filter_keys[0][1] = true;
							break;
						case 'source':
							$data = self::filterBySource($value, $mode, $data);

							$filter_keys[1][1] = true;
							$query = 'SELECT `name`
							          FROM tbl_sections
							          WHERE `id` = "' . General::sanitize($value) .'"';
							$results = $this->_Parent->Database->fetch($query);
							$value = $results[0]['name'];
							break;
						case 'pages':
							$data = self::filterByPages($value, $mode, $data);

							$filter_keys[2][1] = true;
							$values = explode(',', $value);

							foreach($values as &$v) {
								$query = 'SELECT `title`
								          FROM tbl_pages
								          WHERE `id` = "' . General::sanitize(trim($v)) .'"';
								$results = $this->_Parent->Database->fetch($query);
								$v = $results[0]['title'];
							}

							$value = implode(',', $values);
							break;
						case 'author':
							$data = self::filterByAuthor($value, $mode, $data);

							$filter_keys[3][1] = true;
							break;
					}

					$filter_box->appendChild(Widget::Select('filter-key-' . $filters_count, $filter_keys));
					$filter_box->appendChild(Widget::Select('filter-mode-' . $filters_count, $filter_modes));
					$filter_box->appendChild(Widget::Input('filter-value-' . $filters_count, $value));
					$filter_box->appendChild(Widget::Input('action[filter-skip-' . $filters_count .']', __('Remove filter'), 'submit', array('class' => 'button delete')));

					$filters_panel->appendChild($filter_box);
					++$filters_count;
				}

			}

			$filter_box = new XMLElement('div', null, array('class' => 'filter default'));

			$filter_keys = array(
				array('name', false, __('Name')),
				array('source', false, __('Source')),
				array('pages', false, __('Pages')),
				array('author', false, __('Author')),
			);

			$filter_box->appendChild(Widget::Select('filter-key-' . $filters_count, $filter_keys));

			$filter_modes = array(
				array('0', false, __('is')),
				array('1', false, __('contains')),
			);

			$filter_box->appendChild(Widget::Select('filter-mode-' . $filters_count, $filter_modes));

			$filter_box->appendChild(Widget::Input('filter-value-' . $filters_count));

			$filters_panel->appendChild($filter_box);
			$filters_panel->appendChild(Widget::Input('action[process-filters]', __('Apply'), 'submit', array('class' => 'button apply')));;

			return $filters_panel;
		}

		public function buildFiltersString($jump = null) {
			$string = "?filter=";

			for ($i = 0; isset($_POST['filter-key-' . $i]); ++$i) {
				if (($jump != null && $i == intval($jump)) || $_POST['filter-value-' . $i] == '') continue;

				$key = $_POST['filter-key-' . $i];
				$mode = (intval($_POST['filter-mode-' . $i]) == self::FILTER_IS) ? ':' : '*:';

				if ($key == 'source') {
					$query = 'SELECT `id`
					          FROM tbl_sections
					          WHERE `name` = "' . General::sanitize($_POST['filter-value-' . $i]) .'"';
					$results = $this->_Parent->Database->fetch($query);
					$value = $results[0]['id'];
				}
				else if ($key == 'pages') {
					$values = explode(',', $_POST['filter-value-' . $i]);

					foreach($values as &$v) {
						$query = 'SELECT `id`
						          FROM tbl_pages
						          WHERE `title` = "' . General::sanitize(trim($v)) .'"';
						$results = $this->_Parent->Database->fetch($query);
						$v = $results[0]['id'];

					}

					$value = implode(",", $values);
				}
				else {
					$value = $_POST['filter-value-' . $i];
				}

				$value = rawurlencode($value);

				$string .= $key . $mode . $value .";";
			}

			return ($string == "?filter=") ? "" : $string;
		}

	}

?>
