<?php

	require_once(EXTENSIONS . '/edui/lib/class.EDUIPage.php');
	
	class contentExtensionEduiUtilities extends EDUIPage {

		private function transformIntoArray($utilities) {
			$r = array();
			
			foreach ($utilities as $k => $u) {
				$r [$u] = array (
					'name' => $u
				);
			}
			
			return $r;
		}
		
		public function __viewIndex(){
			$this->setPageType('table');	
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Utilities'))));
			$this->appendSubheading(__('Utilities'), Widget::Anchor(__('Create New'), URL . '/symphony/blueprints/utilities/new/', __('Create a new utility'), 'create button'));

			$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utilities = $utilities['filelist'];
			
			$utilities = $this->transformIntoArray($utilities);
			
			/* Pinning */
			$this->pinElements(extension_edui::SETTING_PINNED_UT, $utilities);

			$aTableHead = array(

				array(__('Name'), 'col'),
			);

			$aTableBody = array();

			if(!is_array($utilities) || empty($utilities)){

				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				
				$bOdd = true;

				foreach($utilities as $u) {
					$name = Widget::TableData(
						Widget::Anchor(
							$u['name'],
							URL . '/symphony/blueprints/utilities/edit/' . str_replace('.xsl', '', $u) . '/')
					);

					$name->appendChild(Widget::Input('items[' . $u['name'] . ']', null, 'checkbox'));
					
					if (isset($u['pinned']) && $u['pinned']) {
						$name->appendChild($this->createPinnedNode());
					}

					$aTableBody[] = Widget::TableRow(array($name), null);
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead), 
				NULL, 
				Widget::TableBody($aTableBody),
				'selectable'
			);

			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('pin', false, __('Pin')),
				array('unpin', false, __('Unpin')),
				array('delete', false, __('Delete'), 'confirm'),
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function __actionIndex(){
			$checked = ($_POST['items']) ? @array_keys($_POST['items']) : NULL;

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':
						$canProceed = true;
						foreach($checked as $name) {
							if (!General::deleteFile(UTILITIES . '/' . $name)) {
								$this->pageAlert(__('Failed to delete <code>%s</code>. Please check permissions.', array($name)),Alert::ERROR);
								$canProceed = false;
							}
						}

						if ($canProceed) redirect(Administration::instance()->getCurrentPageURL());
						break;
						
				case 'pin':
								
					$this->__pin(extension_edui::SETTING_PINNED_UT, $checked);
					break;

				case 'unpin':
								
					$this->__unpin(extension_edui::SETTING_PINNED_UT, $checked);
					break;
								
				}
			}

		}

	}
	
?>
