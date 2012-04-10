<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zur Ausgabe einer Tabelle mit Log-Nachrichten.
 */
class command_displayLog extends command {

	public function getContent () {
		global $db_link;
		$content = '';

		/*
		 * Erstellt die Logtabelle mit hilfe der Klasse log
		 */
		require_once(dirname(__FILE__) . "/../classes/log.php");

		if (isset($_POST['id'])) {
			$log = new log($db_link, $_POST['status'], $_POST['type'], $_POST['limit'], $_POST['start'], $_POST['id']);
		} else {
			$log = new log($db_link, $_POST['status'], $_POST['type'], $_POST['limit'], $_POST['start']);
		}

		return $log->getOutput();
	}



	public function getTemplate () {
		return '%content%';
	}


}

?>
