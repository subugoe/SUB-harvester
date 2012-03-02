<?php
/*
 * Erstellt die Logtabelle mit hilfe der Klasse log
 */


require_once(dirname(__FILE__) . "/classes/log.php");

// Datenbankverbindung herstellen	
$db_link = @mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if (!$db_link) {
	// Konfiguration ist nicht möglich
	$content .= "<p class=\"errormsg\">Keine Verbindung zur Datenbank - Konfiguration zurzeit nicht möglich.</p>\n";
	$output = str_replace("%javascript%", "", $output);
} else {

	// Konfiguration ist möglich
	
	// DB-Einstellungen
	mysql_select_db(DB_NAME);
	mysql_query("SET NAMES 'utf8'");
	mysql_query("SET CHARACTER SET 'utf8'");
	
	if (isset($_POST['id'])) {
		$log = new log($db_link, $_POST['status'], $_POST['type'], $_POST['limit'], $_POST['start'], $_POST['id']);
	} else {
		$log = new log($db_link, $_POST['status'], $_POST['type'], $_POST['limit'], $_POST['start']);
	}
	echo $log->getOutput();
}

?>