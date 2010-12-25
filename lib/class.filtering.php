<?php

	Class Filtering {

		const MODE_EQUALS   = 0;
		const MODE_CONTAINS = 1;
		const MODE_EMPTY    = 2;

		private $_Parent;

		public function __construct(&$parent){
			$this->_Parent = $parent;
		}

		public function filterByName($value, $mode = self::MODE_EQUALS, $data = array()) {
			$result = array();

			if ($mode == self::MODE_EQUALS) {
				foreach($data as $d) {
					if ($d['name'] == $value)
						$result[$d['handle']] = $d;
				}
			}
			else if ($mode == self::MODE_CONTAINS) {
				foreach($data as $d) {
					if (stristr($d['name'], $value))
						$result[$d['handle']] = $d;
				}
			}

			return $result;
		}

		public function filterByPages($value, $mode = self::MODE_EQUALS, $data = array()) {
			$result = array();
			$values = explode(',', $value);

			foreach($data as $d) {
				if (isset($d['type'])) 
					$pages = $this->getDatasourceLinkedPages($d['handle']);
				else
					$pages = $this->getEventLinkedPages($d['handle']);
				$accum = true;

				if ($mode == self::MODE_EMPTY && $value == "" && empty($pages)) {
					$result[] = $d;
				}
				else if ($mode == self::MODE_EQUALS && count($values) != count($pages)) continue;

				foreach($values as $v) {
					if (!$accum) break;

					$accum = $accum && isset($pages[$v]);
				}

				if ($accum) $result[] = $d;
			}

			return $result;
		}

		public function filterBySource($value, $mode = self::MODE_EQUALS, $data = array()) {
			$result = array();

			if ($mode == self::MODE_EQUALS) {
				foreach($data as $d) {
					if ($d['type'] == $value || $d['source'] == $value)
						$result[$d['handle']] = $d;
				}
			}
			else if ($mode == self::MODE_CONTAINS) {
				foreach($data as $d) {
					if (stristr($d['source'], $value) || stristr($d['type'], $value))
						$result[$d['handle']] = $d;
				}
			}
			else { // Only for events
				foreach($data as $d) {
					if (!isset($d['source']) || $d['source'] == "")
						$result[$d['handle']] = $d;
				}
			}

			return $result;
		}

		public function filterByAuthor($value, $mode = self::MODE_EQUALS, $data = array()) {
			$result = array();

			if ($mode == self::MODE_EQUALS) {
				foreach($data as $d) {
					if ($d['author']['name'] == $value)
						$result[$d['handle']] = $d;
				}
			}
			else if ($mode == self::MODE_CONTAINS) {
				foreach($data as $d) {
					if (stristr($d['author']['name'], $value))
						$result[$d['handle']] = $d;
				}
			}

			return $result;
		}

		public function getDatasourceLinkedPages($handle) {
			if (!$handle) return array();

			$query = 'SELECT `id`, `title`
			          FROM tbl_pages
			          WHERE `data_sources` REGEXP "' . $handle . ',|,' . $handle . ',|' . $handle . '$"';

			$pages = $this->_Parent->Database->fetch($query);
			$result = array();

			foreach($pages as $p) {
				$result[$p['id']] = $p['title'];
			}

			return $result;
		}

		public function getEventLinkedPages($handle) {
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

		public function displayFiltersPanel(&$data) {
			$filters_panel = new XMLElement('div', null, array('class' => 'filters'));
			$filters_panel->appendChild(new XMLElement('h3', __('Filters')));

			$filters_count = 0;

			if(isset($_REQUEST['filter'])){
				$filters = explode(';', $_REQUEST['filter']);

				foreach($filters as $f) {
					if ($f == '') continue;

					list($key, $value) = explode(':', $f);

					if ($key{0} == "!") {
						$key = substr($key, 1);
						$mode = self::MODE_EMPTY;
					}
					else if ($key{strlen($key)-1} == "*") {
						$key = substr($key, 0, strlen($key)-1);
						$mode = self::MODE_CONTAINS;
					}
					else {
						$mode = self::MODE_EQUALS;
					}

					$value = rawurldecode($value);

					$filter_box = new XMLElement('div', null, array('class' => 'filter'));

					$filter_keys = array(
						array('name', false, __('Name')),
						array('source', false, __('Source')),
						array('pages', false, __('Pages')),
						array('author', false, __('Author')),
					);

					$filter_modes = array(
						array('0', ($mode == self::MODE_EQUALS),       __('equals')),
						array('1', ($mode == self::MODE_CONTAINS), __('contains')),
						array('2', ($mode == self::MODE_EMPTY),    __('is empty')),
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

							if (!empty($results)) $value = $results[0]['name'];
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

								if (!empty($results)) $v = $results[0]['title'];
							}

							$value = implode(',', $values);
							break;
						case 'author':
							$data = self::filterByAuthor($value, $mode, $data);

							$filter_keys[3][1] = true;
							break;
					}

					$filter_box->appendChild(Widget::Select('filter-key-' . $filters_count, $filter_keys, array('class' => 'key')));
					$filter_box->appendChild(Widget::Select('filter-mode-' . $filters_count, $filter_modes, array('class' => 'mode')));
					$filter_box->appendChild(Widget::Input('filter-value-' . $filters_count, $value, "text", array('class' => 'value')));
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

			$filter_box->appendChild(Widget::Select('filter-key-' . $filters_count, $filter_keys, array('class' => 'key')));

			$filter_modes = array(
				array('0', false, __('equals')),
				array('1', false, __('contains')),
				array('2', false, __('is empty')),
			);

			$filter_box->appendChild(Widget::Select('filter-mode-' . $filters_count, $filter_modes, array('class' => 'mode')));

			$filter_box->appendChild(Widget::Input('filter-value-' . $filters_count, NULL, "text", array('class' => 'value')));

			$filters_panel->appendChild($filter_box);
			$filters_panel->appendChild(Widget::Input('action[process-filters]', __('Apply'), 'submit', array('class' => 'button apply')));;

			return $filters_panel;
		}

		public function buildFiltersString($jump = null) {
			$string = "?filter=";

			for ($i = 0; isset($_POST['filter-key-' . $i]); ++$i) {
				if ($jump != null && $i == intval($jump)) continue;

				$key = $_POST['filter-key-' . $i];
				$mode = intval($_POST['filter-mode-' . $i]);

				if ($mode == self::MODE_EQUALS) {
					$sep = ":";
				}
				else if ($mode == self::MODE_CONTAINS) {
					$sep = "*:";
				}
				else {
					$key = "!" . $key;
				}

				$value = $_POST['filter-value-' . $i];

				if ($value == "" && $mode != self::MODE_EMPTY) continue;

				if ($key == 'source') {
					$query = 'SELECT `id`
					          FROM tbl_sections
					          WHERE `name` = "' . General::sanitize($value) .'"';
					$results = $this->_Parent->Database->fetch($query);

					if (!empty($results)) $value = $results[0]['id'];
					else $value = Lang::createHandle(strtolower($value));
				}
				else if ($key == 'pages') {
					$values = explode(',', $_POST['filter-value-' . $i]);

					foreach($values as &$v) {
						$query = 'SELECT `id`
						          FROM tbl_pages
						          WHERE `title` = "' . General::sanitize(trim($v)) .'"';
						$results = $this->_Parent->Database->fetch($query);

						if (!empty($results))
							$v = $results[0]['id'];
					}

					$value = implode(",", $values);
				}

				$value = rawurlencode($value);

				$string .= $key . $sep . $value .";";
			}

			return ($string == "?filter=") ? "" : $string;
		}

	}

?>
