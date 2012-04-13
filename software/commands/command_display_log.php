<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zur Ausgabe einer Tabelle mit Log-Nachrichten.
 */
class command_displayLog extends command {

	public function appendContent () {
		// Erstellt die Logtabelle mit Hilfe der Klasse log.
		require_once(dirname(__FILE__) . '/../classes/log.php');
		$log = new log($this);

		if (array_key_exists('id', $this->parameters) && $this->parameters['id']) {
			$this->contentElement->appendChild($log->getLogMessages($this->parameters['status'], $this->parameters['type'], $this->parameters['limit'], $this->parameters['start'], $this->parameters['id']));
		} else {
			$this->contentElement->appendChild($log->getLogMessages($this->parameters['status'], $this->parameters['type'], $this->parameters['limit'], $this->parameters['start']));
		}
	}


	public function setupTemplate () {
		$this->document = new DOMDocument();
		$this->contentElement = $this->document;
	}


}

?>
