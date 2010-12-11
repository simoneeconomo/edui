<?php

	require_once(TOOLKIT . '/class.datasourcemanager.php');

	Class DatasourceManagerAdvanced extends DatasourceManager{

		static private $_datasources = array();

		public function listAll(){
			if (empty(self::$_datasources))
				return self::$_datasources = parent::listAll();

			return self::$_datasources;
		}

		public function sortByName($order = 'desc') {
			$data = $this->listAll();

			if ($order == 'asc') krsort($data);

			return $data;
		}

		public function sortBySource($order = 'desc') {
			$data = $this->listAll();

			foreach ($data as $key => $about) {
				$source[$key] = $about['type'];
				$label[$key] = $key;
			}

			$sort = ($order == 'desc') ? SORT_DESC : SORT_ASC;

			array_multisort($source, $sort, $label, SORT_ASC, $data);

			return $data;
		}

		public function sortByAuthor($order = 'desc') {
			$data = $this->listAll();

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
