<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	/**
	 * @package field_stream_list
	 */
	Class fieldstream_list extends Field {

		protected static $imageMimeTypes = array(
			'image/gif',
			'image/jpg',
			'image/jpeg',
			'image/pjpeg',
			'image/png',
			'image/x-png',
		);

		protected static $accepted_ext = array(
			'mp3',
			'ogg',
			'wma'
		);


		public function __construct() {
			parent::__construct();
			$this->_name = __('Stream List');
			$this->_required = true;

			$this->set('required', 'no');
			$this->set('show_column', 'no');
		}

		public function createTable() {
			try {
				Symphony::Database()->query(sprintf("
						CREATE TABLE IF NOT EXISTS `sym_entries_data_%d` (
							`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
							`entry_id` INT(11) UNSIGNED NOT NULL,
							`track_name` TEXT NULL,
				  			`file` varchar(255) default NULL,
				  			`size` int(11) unsigned NULL,
				  			`mimetype` varchar(100) default NULL,
				  			`meta` varchar(255) default NULL,
							PRIMARY KEY (`id`),
							KEY `entry_id` (`entry_id`),
				  			KEY `file` (`file`),
				  			KEY `mimetype` (`mimetype`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
					", $this->get('id')
				));
				return true;
			}
			catch (Exception $ex) {
				return false;
			}
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		/**
		 * Displays setting panel in section editor.
		 *
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			// Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			$order = $this->get('sortorder');

			$group = new XMLElement('div');

			// Default Keys
			$label = Widget::Label(__('APP ID'));
			$label->setAttribute('class', 'column');
			$label->appendChild(Widget::Input(
				"fields[{$order}][app_id]", $this->get('app_id')
			));
			$label->appendChild(
				new XMLElement('p', __('Place your app id from soundcloud here.'), array('class' => 'help'))
			);

			$group->appendChild($label);

			$label = Widget::Label(__('Username'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
				new XMLElement('i', __('Optional'))
			);
			$label->appendChild(Widget::Input(
				"fields[{$order}][soundcloud_user]", $this->get('soundcloud_user')
			));
			$label->appendChild(
				new XMLElement('p', __('Place the username for your soundcloud app here.'), array('class' => 'help'))
			);

			$group->appendChild($label);

			$label = Widget::Label(__('Password'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
				new XMLElement('i', __('Optional'))
			);
			$label->appendChild(Widget::Input(
				"fields[{$order}][soundcloud_pass]", $this->get('soundcloud_pass')
			));
			$label->appendChild(
				new XMLElement('p', __('Place the password for your soundcloud app here.'), array('class' => 'help'))
			);

			$group->appendChild($label);

			$wrapper->appendChild($group);

			// Defaults
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);
		}

		/**
		 * Save field settings in section editor.
		 */
		public function commit() {
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if($id === false) return false;

			$fields = array(
				'field_id' => $id,
				'app_id' => $this->get('app_id'),
				'soundcloud_user' => $this->get('soundcloud_user'),
				'soundcloud_pass' => $this->get('soundcloud_pass'),
			);

			return Symphony::Database()->insert($fields, "sym_fields_{$handle}", true);
		}

		public static function getMetaInfo($file, $type){
			$meta = array();

			if(!file_exists($file) || !is_readable($file)) return $meta;

			$meta['creation'] = DateTimeObj::get('c', filemtime($file));

			if(General::in_iarray($type, fieldstream_list::$imageMimeTypes) && $array = @getimagesize($file)){
				$meta['width'] = $array[0];
				$meta['height'] = $array[1];
			}

			return $meta;
		}
		
		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {
			extension_stream_list::appendAssets();

			// Label
			$label = Widget::Label($this->get('label'));
			if ($this->get('required') == 'no') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}

			// from metakeys -------------------------------------

			// Setup Duplicator
			$duplicator = new XMLElement('div', null, array('class' => 'frame stream_list-duplicator'));
			$pairs = new XMLElement('ol');
			$pairs->setAttribute('data-add', __('Add Track'));
			$pairs->setAttribute('data-remove', __('Remove Track'));

			// Add a blank template
			$pairs->appendChild(
				$this->buildPair()
			);

			if($data) {
				if(array_key_exists("track_name",$data)) {
					if(is_array($data['track_name'])) {
						foreach($data["track_name"] as $i => $track_name) {
							// Check if there is a value set:
							$pairs->appendChild(
								$this->buildPair($track_name, $data['file'][$i], $i)
							);
						}
					} else {
						$pairs->appendChild(
							$this->buildPair($data['track_name'], $data['file'], 0)
						);					
					}
				}
			} else {
	/*			$pairs->appendChild(
					$this->buildPair(null, null, 0)
				);       */
			}

			$duplicator->appendChild($pairs);
			$label->appendChild($duplicator);

			// from meta-keys ----------------------------

			$wrapper->appendChild($label);

			if (!is_null($flagWithError)) {
				$wrapper = Widget::Error($wrapper, $flagWithError);
			}
		}

		public function buildPair($track_name = null, $file = null, $i = -1) {

			$element_name = $this->get('element_name');

			$li = new XMLElement('li');
			if($i == -1) {
				$li->setAttribute('class', 'template');
			}


			// Header
			$header = new XMLElement('header');
			$label = !is_null($key) ? $key : __('New Pair');
			$header->setAttribute('data-name', 'track');
			if($i!=-1) {
				$header->appendChild(new XMLElement('h4', '<strong>Track '.($i+1).'</strong>'));
			} else {
				$header->appendChild(new XMLElement('h4', '<strong>New Track</strong>'));
			}
			$li->appendChild($header);

			// Track Name
			$label = Widget::Label('Name');
			$label->appendChild(
				Widget::Input(
					"fields[$element_name][$i][track_name]", $track_name, 'text', array('placeholder' => __('Track Name'))
				)
			);
			$li->appendChild($label);

			// Track Endpoint
			$label = Widget::Label('');


			// symph file upload code
			$label->setAttribute('class', 'file');

			$span = new XMLElement('span', NULL, array('class' => 'frame'));

			if ($file) {
				// Check to see if the file exists without a user having to
				// attempt to save the entry. RE: #1649
				$file_path = WORKSPACE . preg_replace(array('%/+%', '%(^|/)\.\./%'), '/', $data['file']);

				if (file_exists($file_path) === false || !is_readable($file_path)) {
					$flagWithError = __('The file uploaded is no longer available. Please check that it exists, and is readable.');
				}

				$span->appendChild(new XMLElement('span', Widget::Anchor('/workspace' . preg_replace("![^a-z0-9]+!i", "$0&#8203;", $file), URL . '/workspace' . $file)));
			}

			$span->appendChild(Widget::Input("fields[$element_name][$i][file]", $file, ($file ? 'hidden' : 'file')));

			$label->appendChild($span);

			// end symph file upload code

			$li->appendChild($label);

			return $li;
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {

			if(is_array($data)) foreach($data as $i => $entry) {
				if(empty($data[$i]['track_name'])) {
					$message = __('Track Name is missing in one on entry %i.', array($i));
					return self::__MISSING_FIELDS__;
				}
			}

			// check for acceptable audio format
			$accepted_ext = array('mp3','ogg','wma');
			var_dump($data[$i]['file']);
			if(is_array($data[$i]['file']) && !empty($data[$i]['file']['name']) ) {
				if( !in_array(General::getExtension($data[$i]['file']['name']), fieldstream_list::$accepted_ext) ) {
					$message = __('%s is not a valid file format for streaming, try one of the following: %s.', array( General::getExtension($data[$i]['file']['name']), implode(', ',$accepted_ext)) );
					echo "hello";
					return self::__INVALID_FIELDS__;
				}
			}


			// Return if it's allowed to be empty (and is empty)
//			if(empty($data[0]['value'])) return self::__OK__;


			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=null) {

			$status = self::__OK__;

			$result = array();

			if(is_array($data)) foreach($data as $i => $pair) {

				// Check to see if the entry already has a file associated with it:
				if (is_null($entry_id) === false) {
					$row = Symphony::Database()->fetchRow(0, sprintf(
						"SELECT * FROM `sym_entries_data_%s` WHERE `entry_id` = %d AND `track_name` = '%s' LIMIT 1",
						$this->get('id'),
						$entry_id,
						mysql_real_escape_string($data[$i]['track_name'])
					));

					$existing_file = '/' . trim($row['file'], '/');
					// File was removed:
					if(is_array($data[$i]['file'])) {
						if (
							$data[$i]['file']['error'] == UPLOAD_ERR_NO_FILE
							&& !is_null($existing_file)
							&& is_file(WORKSPACE . $existing_file)
						) {
							General::deleteFile(WORKSPACE . $existing_file);
						}
					}
				}

				// Do not continue on upload error:
				if(is_array($data[$i]['file'])) {
					if ($data[$i]['file']['error'] == UPLOAD_ERR_NO_FILE ) {
						$result['track_name'][$i] =	$data[$i]['track_name'];
						$result['file'][$i] =		null;
						$result['size'][$i] =		null;
						$result['mimetype'][$i] =	null;
						$result['meta'][$i] =		null;
						if($i<count($data)-1) {
							continue;
						}
						return $result;
					}

				// Do not continue if value is already uploaded
				} else {
					if (isset($entry_id) && !is_array($data[$i]['file']) ) {
						$row = Symphony::Database()->fetchRow(0, sprintf(
							"SELECT `track_name`, `file`, `mimetype`, `size`, `meta` FROM `sym_entries_data_%d` WHERE `entry_id` = %d AND `track_name` = '%s'",
							$this->get('id'),
							$entry_id,
							$data[$i]['track_name']
						));

						$result['track_name'][$i] =		$row['track_name'];
						$result['file'][$i] = 			$row['file'];
						$result['size'][$i] =			$row['size'];
						$result['mimetype'][$i] =		$row['mimetype'];
						$result['meta'][$i] =			$row['meta'];
						if($i<count($data)-1) {
							continue;
						}

						return $result;
					}
				}


				$abs_path = DOCROOT . '/workspace/album-files/';
				$rel_path = '/album-files/';

				$data[$i]['file']['name'] = Lang::createFilename($data[$i]['file']['name']);
				$file = rtrim($rel_path, '/') . '/' . trim($data[$i]['file']['name'], '/');

				// Attempt to upload the file:
				$uploaded = General::uploadFile(
					$abs_path, $data[$i]['file']['name'], $data[$i]['file']['tmp_name'],
					Symphony::Configuration()->get('write_mode', 'file')
				);

				if ($uploaded === false) {
					$message = __(
						'There was an error while trying to upload the file %1$s to the target directory %2$s.',
						array(
							'<code>' . $data[$i]['file']['name'] . '</code>',
							'<code>workspace/' . ltrim($rel_path, '/') . '</code>'
						)
					);
					$status = self::__ERROR_CUSTOM__;

					return false;
				}


				$result['track_name'][$i] =	$data[$i]['track_name'];
				$result['file'][$i] =		$file;
				$result['size'][$i] =		$data[$i]['file']['size'];
				$result['mimetype'][$i] =	$data[$i]['file']['type'];
				$result['meta'][$i] =		serialize(self::getMetaInfo(WORKSPACE . $file, $data[$i]['file']['type']));

			}

			// If there's no values, return null:
			if(empty($result)) return null;

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function fetchIncludableElements() {
			return array(
				$this->get('element_name'),
			);
		}

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(!is_array($data) || empty($data)) return;

			$field = new XMLElement('album-set');
			$field->setAttribute('name',$this->get('element_name'));
			$field->setAttribute('sc-account',$this->get('soundcloud_user'));

			if(!is_array($data['track_name'])) {
				$data = array(
					'track_name' => array($data['track_name']),
					'file' => array($data['file']),
				);
			}

			for($i = 0, $ii = count($data['track_name']); $i < $ii; $i++) {

				$track = new XMLElement('track');

				$track->setAttribute('name', $data['track_name'][$i]);

				$track_endpoint = new XMLElement('file');
				$track_endpoint->setValue($data['file'][$i]);

				$track->appendChild($track_endpoint);
				$field->appendChild($track);
			}

			$wrapper->appendChild($field);
		}

		public function getParameterPoolValue(array $data, $entry_id=NULL) {
			return $data['track_name'];
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			if(is_null($data)) return __('None');

			$tracks = is_array($data['track_name'])
						? implode(', ', $data['track_name'])
						: $data['track_name'];

			return parent::prepareTableValue(array('track_name' => $tracks), $link);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {

//			$field_id = $this->get('id');

//			
//			if (!is_array($data)) $data = array($data);
//			foreach ($data as &$value) {
//				$value = $this->cleanValue($value);
//			}
//			$this->_key++;
//			$data = implode("', '", $data);
//			$joins .= "
//				LEFT JOIN
//					`sym_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
//				ON
//					(e.id = t{$field_id}_{$this->_key}.entry_id)
//			";
//			$where .= "
//				AND (
//					t{$field_id}_{$this->_key}.key_value IN ('{$data}')
//					OR
//					t{$field_id}_{$this->_key}.key_handle IN ('{$data}')
//				)
//			";//

//			return true;
		}

	}

?>