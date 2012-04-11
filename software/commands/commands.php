<?php

require_once(dirname(__FILE__) . '/../classes/log.php');

abstract class command {

	public $commandName;

	public $parameters;

	public $db_link;

	public $document;

	public $contentElement;

	public $headElement;



	abstract protected function appendContent ();



	public static function subclassForCommand ($commandName, $parameters) {
		$commandObject;

		switch($commandName) {
			case 'add_oai_source':
				require_once(dirname(__FILE__) . '/command_add_oai_source.php');
				$commandObject = new command_addOAISource();
				break;
			case 'save_oai_source':
				require_once(dirname(__FILE__) . '/command_save_oai_source.php');
				$commandObject = new command_saveOAISource();
				break;
			case 'list_oai_sources':
				require_once(dirname(__FILE__) . '/command_list_oai_sources.php');
				$commandObject = new command_listOAISources();
				break;
			case 'edit_oai_source':
				require_once(dirname(__FILE__) . '/command_edit_oai_source.php');
				$commandObject = new command_editOAISource();
				break;
			case 'update_oai_source':
				require_once(dirname(__FILE__) . '/command_update_oai_source.php');
				$commandObject = new command_updateOAISource();
				break;
			case 'show_oai_source':
				require_once(dirname(__FILE__) . '/command_show_oai_source.php');
				$commandObject = new command_showOAISource();
				break;
			case 'delete_oai_source':
				require_once(dirname(__FILE__) . '/command_delete_oai_source.php');
				$commandObject = new command_listOAISource();
				break;
			case 'preview_oai_set':
				require_once(dirname(__FILE__) . '/command_preview_oai_set.php');
				$commandObject = new command_previewOAISet();
				break;
			case 'display_log':
				require_once(dirname(__FILE__) . '/command_display_log.php');
				$commandObject = new command_displayLog();
				break;
			default:
				require_once(dirname(__FILE__) . '/command_start.php');
				$commandObject = new command_start();
				$command = 'start';
		}

		$commandObject->commandName = $commandName;
		$commandObject->parameters = $parameters;

		// Datenbankverbindung herstellen
		$commandObject->db_link = @mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
		if (!$commandObject->db_link) {
			// Konfiguration ist nicht möglich
			// TODO: Fehlerausgabe
			$content .= "<p class=\"errormsg\">Keine Verbindung zur Datenbank - Konfiguration zurzeit nicht möglich.</p>\n";
		}
		else {
			// Konfiguration ist möglich

			// Sprache für PHP-Funktionen
			setlocale(LC_ALL, "de_DE.utf8");

			// DB-Einstellungen
			mysql_select_db(DB_NAME, $commandObject->db_link);
			mysql_query("SET NAMES 'utf8'");
			mysql_query("SET CHARACTER SET 'utf8'");
			mysql_query("SET lc_time_names = 'de_DE'", $commandObject->db_link);
		}

		return $commandObject;
	}


	public function __destruct () {
		@mysql_close($this->db_link);
	}


	protected function setupTemplate () {
		$this->document = new DOMDocument();

		$htmlElement = $this->document->createElement('html');
		$this->document->appendChild($htmlElement);
		$htmlElement->setAttribute('lang', 'de');

		$this->headElement = $this->document->createElement('head');
		$htmlElement->appendChild($this->headElement);

		$metaElement = $this->document->createElement('meta');
		$this->headElement->appendChild($metaElement);
		$metaElement->setAttribute('http-equiv', 'Content-Type');
		$metaElement->setAttribute('content', 'text/html;charset=utf-8');

		$titleElement = $this->document->createElement('title');
		$this->headElement->appendChild($titleElement);
		$titleElement->appendChild($this->document->createTextNode(SERVICE_NAME . ' OAI-Harvester'));

		$this->addCSSToHead('resources/css/oai_harvester.css');
		$this->addCSSToHead('resources/css/jquery-ui-1.8.18.custom.css');

		$this->addJSToHead('resources/javascript/jquery-1.7.1.min.js');
		$this->addJSToHead('resources/javascript/jquery-ui-1.8.18.custom.min.js');
		$this->addJSToHead('resources/javascript/jquery.uitablefilter.js');
		$this->addJSToHead('resources/javascript/jquery.tablesorter.min.js');
		$this->addJSToHead('resources/javascript/common.js');
		$this->addJSToHead('resources/javascript/add_oai_source.js');
		$this->addJSToHead('resources/javascript/edit_oai_source.js');
		$this->addJSToHead('resources/javascript/list_oai_sources.js');
		$this->addJSToHead('resources/javascript/preview_oai_set.js');

		$bodyElement = $this->document->createElement('body');
		$htmlElement->appendChild($bodyElement);

		$mainDivElement = $this->document->createElement('div');
		$htmlElement->appendChild($mainDivElement);
		$mainDivElement->setAttribute('id', 'main');

		$h1Element = $this->document->createElement('h1');
		$mainDivElement->appendChild($h1Element);
		$h1LinkElement = $this->document->createElement('a');
		$h1Element->appendChild($h1LinkElement);
		$h1LinkElement->setAttribute('href', 'index.php');
		$h1LinkElement->appendChild($this->document->createTextNode(SERVICE_NAME . ' OAI-Harvester'));

		$this->contentElement = $this->document->createElement('div');
		$mainDivElement->appendChild($this->contentElement);
		$this->contentElement->setAttribute('id', 'content');
	}


	protected function addCSSToHead ($URL) {
		if ($this->headElement && $URL) {
			$linkElement = $this->document->createElement('link');
			$linkElement->setAttribute('href', $URL);
			$linkElement->setAttribute('type', 'text/css');
			$linkElement->setAttribute('rel', 'stylesheet');
			$this->headElement->appendChild($linkElement);
		}
	}


	protected function addJSToHead ($URL) {
		if ($this->headElement && $URL) {
			$scriptElement = $this->document->createElement('script');
			$scriptElement->setAttribute('src', $URL);
			$scriptElement->setAttribute('type', 'text/javascript');
			$this->headElement->appendChild($scriptElement);
		}
	}


	public function run () {
		$this->setupTemplate();
		$this->appendContent();
	}


	protected function clearEditLock () {
		if (isset($_POST['edit_id'])) {
			$sql = "DELETE FROM oai_source_edit_sessions
					WHERE oai_source = " . intval($_POST['edit_id']) . "
					AND MD5(timestamp) = '" . mysql_real_escape_string($_POST['edit_token']) . "'";
			$result = mysql_query($sql, $db_link);

			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return;
			}
		}
	}


	public function makeForm ($action='index.php', $method='POST') {
		$form = $this->document->createElement('form');
		$form->setAttribute('method', $method);
		$form->setAttribute('action', $action);
		$form->setAttribute('accept-charset', 'UTF-8');

		return $form;
	}


	public function makeInput ($type, $name=NULL, $value='') {
		$input = $this->document->createElement('input');
		$input->setAttribute('type', $type);
		$input->setAttribute('value', $value);
		if ($name !== NULL) {
			$input->setAttribute('name', $name);
		}

		return $input;
	}


	public function makeInputForParameter ($type, $name, $defaultValue = '') {
		$value = $defaultValue;
		if (array_key_exists($name, $this->parameters)) {
			$value = $this->parameters[$name];
		}

		$input = $this->makeInput($type, $name, $value);
		return $input;
	}


	public function makeLabel ($for, $labelText) {
		$label = $this->document->createElement('label');
		$label->setAttribute('for', $for);
		$label->appendChild($this->document->createTextNode($labelText));

		return $label;
	}


	public function makeSelectWithOptions ($name, $options) {
		$select = $this->document->createElement('select');
		$select->setAttribute('name', $name);
		foreach ($options as $option) {
			$select->appendChild($this->makeOption($name, $option));
		}
		return $select;
	}


	public function makeElementWithContent ($elementName, $content, $class=NULL) {
		$element = $this->document->createElement($elementName);
		$element->appendChild($content);
		if($class !== NULL) {
			$element->setAttribute('class', $class);
		}
		return $element;
	}


	public function makeElementWithText ($elementName, $text, $class=NULL) {
		$content = $this->document->createTextNode($text);
		$element = $this->makeElementWithContent($elementName, $content, $class);
		return $element;
	}


	public function makeOption ($name, $configuration) {
		$option = $this->document->createElement('option');
		$option->setAttribute('value', $configuration['value']);

		// Make this option selected if the parameter $name is set to its value
		// or the parameter does not exist and it is marked as the default selection.
		if (array_key_exists($name, $this->parameters)) {
			if ($this->parameters[$name] === $configuration['value']) {
				$option->setAttribute('selected', 'selected');
			}
		}
		else {
			if (array_key_exists('defaultSelection', $configuration)) {
				$option->setAttribute('selected', 'selected');
			}
		}

		// If no label is given, display the value.
		$labelText = $configuration['value'];
		if (array_key_exists('label', $configuration)) {
			$labelText = $configuration['label'];
		}
		$option->appendChild($this->document->createTextNode($labelText));

		return $option;
	}


	public function filterForm () {
		$filterForm = $this->makeForm();
		$filterForm->setAttribute('class', 'list-oai-sources');
		$filterForm->appendChild($this->makeInput('hidden', 'do', 'list_oai_sources'));

		$filterForm->appendChild($this->makeInputForParameter('hidden', 'filter_url'));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'filter_bool', 'AND'));

		$filterForm->appendChild($this->makeInputForParameter('hidden', 'sortby', 'name'));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'sorthow', 'ASC'));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'id', 'none'));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'start'));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'limit', 50));

		$filterForm->appendChild($this->makeInputForParameter('hidden', 'show_active', 0));
		$filterForm->appendChild($this->makeInputForParameter('hidden', 'show_status', 0));

		$filterForm->appendChild($this->makeLabel('filter_namet', 'Name:'));
		$filterForm->appendChild($this->makeInput('text', 'filter_name'));

		$select = $this->makeSelectWithOptions('filter_bool', Array(
			Array('value' => 'AND', 'label' => 'und', 'defaulSelection' => TRUE),
			Array('value' => 'OR', 'label' => 'or')
		));
		$filterForm->appendchild($select);

		$filterForm->appendChild($this->makeLabel('filter_url', 'URL:'));
		$filterForm->appendChild($this->makeInput('text', 'filter_url'));

		$filterForm->appendChild($this->makeInput('submit', NULL, 'Suchen'));

		return $filterForm;
	}


}

?>
