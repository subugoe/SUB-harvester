<?php

require_once(dirname(__FILE__) . '/../classes/log.php');
require_once(dirname(__FILE__) . '/../classes/error.php');

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
				$commandObject = new command_deleteOAISource();
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



	public function run () {
		$this->setupTemplate();
		$this->appendContent();
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
		$h1LinkElement->setAttribute('href', 'index.php?edit_abort=1');
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



	public function makeFormWithSubmitButton ($buttonText, $onclick = '') {
		$form = $this->makeForm();
		$button = $this->makeInput('submit', NULL, $buttonText);
		$p = $this->makeElementWithContent('p', $button);
		$form->appendChild($p);
		if ($onclick !== '') {
			$button->setAttribute('onclick', $onclick);
		}
		return $form;
	}



	public function makeInput ($type, $name=NULL, $value='') {
		$input = $this->document->createElement('input');
		$input->setAttribute('type', $type);
		$input->setAttribute('id', $name);
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
		$label->setAttribute('id', 'label_' . $for);
		$label->appendChild($this->document->createTextNode($labelText));

		return $label;
	}



	public function makeInputWithLabel ($type, $name, $labelText, $defaultValue = '') {
		$container = $this->document->createElement('span');
		$container->setAttribute('class', 'inputContainer ' . $type);
		$container->appendChild($this->makeLabel($name, $labelText));
		$container->appendChild($this->makeInput($type, $name, $defaultValue));

		return $container;
	}



	public function makeSelectWithOptions ($name, $options) {
		$select = $this->document->createElement('select');
		$select->setAttribute('name', $name);
		foreach ($options as $option) {
			$select->appendChild($this->makeOption($name, $option));
		}
		return $select;
	}



	public function makeElementWithContent ($elementName, $content, $class = '') {
		$element = $this->document->createElement($elementName);
		$element->appendChild($content);
		if($class !== '') {
			$element->setAttribute('class', $class);
		}
		return $element;
	}



	public function makeElementWithText ($elementName, $text, $class = '') {
		$content = $this->document->createTextNode($text);
		$element = $this->makeElementWithContent($elementName, $content, $class);
		return $element;
	}



	public function appendDTDDWithContentTo ($DTContent, $DDContent, $container, $class = '') {
		$dt = $this->document->createElement('dt');
		$container->appendChild($dt);
		$dt->appendChild($DTContent);
		if ($class !== '') {
			$dt->setAttribute('class', $class);
		}

		$dd = $this->document->createElement('dd');
		$container->appendChild($dd);#
		$dd->appendChild($DDContent);
		if ($class !== '') {
			$dd->setAttribute('class', $class);
		}
	}



	public function appendDTDDWithTextTo ($DTText, $DDText, $container, $class = '') {
		$DTContent = $this->document->createTextNode($DTText);
		$DDContent = $this->document->createTextNode($DDText);
		$this->appendDTDDWithContentTo($DTContent, $DDContent, $container, $class);
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



	public function makeRedirect ($destination = '0; URL=./index.php') {
		$meta = $this->document->createElement('meta');
		$meta->setAttribute('http-equiv', 'refresh');
		$meta->setAttribute('content', $destination);
		return $meta;
	}



	public function makeClear () {
		$div = $this->document->createElement('div');
		$div->setAttribute('class', 'clear');
		return $div;
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

		$filterForm->appendChild($this->makeLabel('filter_name', 'Name:'));
		$filterForm->appendChild($this->makeInputForParameter('text', 'filter_name'));

		$select = $this->makeSelectWithOptions('filter_bool', Array(
			Array('value' => 'AND', 'label' => 'und', 'defaulSelection' => TRUE),
			Array('value' => 'OR', 'label' => 'oder')
		));
		$filterForm->appendchild($select);

		$filterForm->appendChild($this->makeLabel('filter_url', 'URL:'));
		$filterForm->appendChild($this->makeInputForParameter('text', 'filter_url'));

		$filterForm->appendChild($this->makeInput('submit', NULL, 'Suchen'));

		return $filterForm;
	}



	public function logSection ($oai_source_id = NULL) {
		$container = $this->document->createElement('div');
		$container->setAttribute('class', 'logs');

		$h2 = $this->document->createElement('h2');
		$container->appendChild($h2);
		$h2->appendChild($this->document->createTextNode('Logs'));

		$form = $this->makeForm();
		$container->appendChild($form);
		$form->setAttribute('class', 'list-configuration');

		$form->appendChild($this->makeLabel('max_hit_display', 'Anzahl der Meldungen:'));
		$select = $this->makeSelectWithOptions('limit_select', Array(
			Array('value' => 5),
			Array('value' => 10, 'defaultSelection' => TRUE),
			Array('value' => 50),
			Array('value' => 100),
			Array('value' => 200)
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'navigate(0)');
		$select->setAttribute('id', 'max_hit_display');

		$form->appendChild($this->makeLabel('show_status_select', 'Status:'));
		$select = $this->makeSelectWithOptions('show_status_select', Array(
			Array('value' => -1, 'label' => 'egal', 'defaultSelection' => TRUE),
			Array('value' => 0, 'label' => 'OK'),
			Array('value' => 1, 'label' => 'Fehler')
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'navigate(0)');
		$select->setAttribute('id', 'show_status_select');

		$form->appendChild($this->makeLabel('show_type_select', 'Typ:'));
		$select = $this->makeSelectWithOptions('show_type_select', Array(
			Array('value' => -1, 'label' => 'egal', 'defaultSelection' => TRUE),
			Array('value' => 0, 'label' => 'Harvester'),
			Array('value' => 1, 'label' => 'Indexer')
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'navigate(0)');
		$select->setAttribute('id', 'show_type_select');

		$log = new log($this);
		$container->appendChild($log->getLogMessages(-1, -1, 10, 0, $oai_source_id));

		$container->appendChild($this->makeClear());

		return $container;
	}



	public function makeGeneralInformation ($oai_source_data, $oai_source_status = 0) {
		$container = $this->document->createElement('div');
		$container->appendChild($this->makeElementWithText('h3', 'Allgemeine Informationen'));

		$dl = $this->document->createElement('dl');
		$container->appendChild($dl);
		$this->appendDTDDWithTextTo('Name:', $oai_source_data['name'], $dl);

		if ($oai_source_status > 0) {
			$lastDD = $dl->lastChild;
			$a = $this->makeElementWithText('a', 'Es liegen Fehlermeldungen für diese OAI-Quelle vor. Bitte klicken um zu den Logs zu springen.', 'errorIcon');
			$dl->lastChild->appendChild($a);
			$a->setAttribute('href', '#logs');
		}

		$this->appendDTDDWithTextTo('Request URL:', $oai_source_data['url'], $dl);

		if (array_key_exists('id', $oai_source_data)) {
			$this->appendDTDDWithTextTo('Interne ID:', $oai_source_data['id'], $dl);
		}

		if (array_key_exists('added', $oai_source_data)) {
			$this->appendDTDDWithTextTo('Hinzugefügt:', $oai_source_data['added'], $dl);
		}

		if (array_key_exists('country_name', $oai_source_data)) {
			$this->appendDTDDWithTextTo('Land:', $oai_source_data['country_name'], $dl);
		}

		if (array_key_exists('active', $oai_source_data)) {
			$img = $this->document->createElement('img');
			if ($oai_source_data['active']) {
				$img->setAttribute('src', 'resources/images/ok.png');
				$img->setAttribute('alt', 'OAI-Quelle wird geharvestet.');
			} else {
				$img->setAttribute('src', 'resources/images/not_ok.png');
				$img->setAttribute('alt', 'OAI-Quelle wird nicht geharvestet.');
			}
			$this->appendDTDDWithContentTo($this->document->createTextNode('Aktiv:'), $img, $dl);
		}

		if (array_key_exists('reindex', $oai_source_data)) {
			$img = $this->document->createElement('img');
			if ($oai_source_data['reindex']) {
				$img->setAttribute('src', 'resources/images/ok.png');
				$img->setAttribute('alt', 'OAI-Quelle wird beim nächsten Harvesten komplett neu indexiert.');
			} else {
				$img->setAttribute('src', 'resources/images/not_ok.png');
				$img->setAttribute('alt', 'OAI-Quelle ist nicht zur Neuindexierung markiert.');
			}
			$this->appendDTDDWithContentTo($this->document->createTextNode('Neuindexierung:'), $img, $dl);
		}

		if (array_key_exists('from', $oai_source_data)) {
			$fromText = 'Für diese Quelle ist kein Startzeitpunkt festgelegt.';
			if (!is_null($oai_source_data['from'])) {
				$fromText = $oai_source_data['from'];
			}
			$this->appendDTDDWithTextTo('Request URL:', $fromText, $dl);
		}

		if (array_key_exists('harvest_period', $oai_source_data)) {
			$intervalText = 'täglich';
			if ($oai_source_data['harvest_period'] > 1) {
				$intervalText = 'Alle ' . $oai_source_data['harvest_period'] . ' Tage';
			}
			$this->appendDTDDWithTextTo('Harvest-Rhythmus:', $intervalText, $dl);
		}

		if (array_key_exists('last_harvested', $oai_source_data)) {
			$lastHarvestDate = 'Diese Quelle wurde noch nicht geharvested.';
			if (!empty($oai_source_data['last_harvested'])) {
				$lastHarvestDate = $oai_source_data['last_harvested'];
			}
			$this->appendDTDDWithTextTo('Letztes erfolgreiches Harvesten:', $lastHarvestDate, $dl);
		}

		if (array_key_exists('last_indexed', $oai_source_data)) {
			$lastIndexDate = 'Diese Quelle wurde noch nicht indexiert.';
			if (!empty($oai_source_data['last_indexed'])) {
				$lastIndexDate = $oai_source_data['last_indexed'];
			}
			$this->appendDTDDWithTextTo('Letztes erfolgreiches Indizieren:', $lastIndexDate, $dl);
		}

		if (array_key_exists('next_harvest', $oai_source_data)) {
			$nextHarvestDate = $oai_source_data['next_harvest'];
			if ($nextHarvestDate === NULL) {
				$nextHarvestDate = strftime('%A, %d. %B %Y', time() + 86400);
			}
			$this->appendDTDDWithTextTo('Nächstes Harvesten:', $nextHarvestDate, $dl);
		}

		$index_entry_count = $this->getIndexEntryCount($oai_source_data);
		$class = '';
		if ($index_entry_count === -1) {
			$index_entry_count = 'Der Index ist zurzeit nicht erreichbar.';
			$class = 'warning';
		}
		$this->appendDTDDWithTextTo('Anzahl der Indexeinträge:', $index_entry_count, $dl, $class);

		if (array_key_exists('comment', $oai_source_data)) {
			$this->appendDTDDWithTextTo('Kommentar:', $oai_source_data['comment'], $dl);
		}

		$container->appendChild($this->makeClear());

		return $container;
	}



	public function getIndexEntryCount ($oai_source_data) {
		$index_entry_count = 0;

		// Index abfragen
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, SOLR . '/select?version=2.2&rows=0&q=oai_repository_id%3A' . $oai_source_data['id']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$http_response = curl_exec($ch);

		if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
			$dom = new DOMDocument();
			$dom->loadXML($http_response);

			$XPath = new DOMXPath($dom);
			$XPath_count_query = $XPath->query('/response/result/@numFound');

			$index_entry_count = $XPath_count_query->item(0)->nodeValue;
		}
		else {
			// Der Server ist nicht erreichbar
			$index_entry_count = -1;
		}

		return $index_entry_count;
	}



	public function makeButtons ($oai_source_data) {
		$form = $this->makeForm();
		$form->setAttribute('id', 'command');

		$form->appendChild($this->makeInput('hidden', 'do', 'list_oai_sources'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_url'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_bool', 'AND'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_sortby', 'name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_sorthow', 'ASC'));
		$form->appendChild($this->makeInputForParameter('hidden', 'id'));
		$form->appendChild($this->makeInputForParameter('hidden', 'start', 0));
		$form->appendChild($this->makeInputForParameter('hidden', 'limit', 20));
		$form->appendChild($this->makeInputForParameter('hidden', 'show_active', 0));
		$form->appendChild($this->makeInputForParameter('hidden', 'show_status', 0));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));

		$p = $this->document->createElement('p');
		$form->appendChild($p);
		$p->setAttribute('class', 'buttons');

		$button = $this->makeInput('submit', NULL, 'Bearbeiten');
		$p->appendChild($button);
		$button->setAttribute('onclick', 'edit(' . $oai_source_data['id'] . ')');

		$button = $this->makeInput('submit', NULL, 'Löschen');
		$p->appendChild($button);
		$button->setAttribute('onclick', 'remove(' . $oai_source_data['id'] . ')');

		$button = $this->makeInput('submit', NULL, 'Zurück zur Quellenliste');
		$p->appendChild($button);

		return $form;
	}

}

?>
