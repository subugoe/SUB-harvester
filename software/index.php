<?php

/***************************************************************
 **************                                  ***************
 **************   EROMM Search - OAI-Harvester   ***************
 **************                                  ***************
 ***************************************************************
 *
 * Diese Datei dient zur Konfiguration des OAI-Harvesters
 * von EROMM Search.
 *
 */

$mysq_error_message = "Ein Fehler in der Datenbankabfrage";
$output = "";
$content = "";
$javascript = "";
$jquery = "resources/javascript/jquery-1.6.2.min.js";

// Funktionen einbinden
require_once(dirname(__FILE__) . '/scripts/scripts_funcs.php');
// Einstellungen laden
readConfiguration();

// Template laden
$file = fopen(dirname(__FILE__) . '/templates/html_template.html', "r");

while (!feof($file)) {
    $output .= fgets($file);
}
fclose($file);
$output = str_replace("%name%", SERVICE_NAME, $output);


// Datenbankverbindung herstellen
$db_link = @mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if (!$db_link) {
	// Konfiguration ist nicht möglich
	$content .= "<p class=\"errormsg\">Keine Verbindung zur Datenbank - Konfiguration zurzeit nicht möglich.</p>\n";
	$output = str_replace("%javascript%", "", $output);
} else {

	// Konfiguration ist möglich

	// Sprache für PHP-Funktionen
	setlocale(LC_ALL,"de_DE.utf8");

	// DB-Einstellungen
	mysql_select_db(DB_NAME, $db_link);
	mysql_query("SET NAMES 'utf8'");
	mysql_query("SET CHARACTER SET 'utf8'");

	// Sprache setzen
	$sql = "SET lc_time_names = 'de_DE'";
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}


	// Welche Funktion wird aufgerufen?
	// Die Umsetzung erfolgt zur besseren Übersicht in externen Dateien
	// Ggf. wird hier Javascript eingebunden.
	$parameters = $_POST;
	$parameters = array_merge($parameters, $_GET);

	switch(isset($parameters['do']) ? $parameters['do'] : "" ) {

		case "":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"resources/javascript/start.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once(dirname(__FILE__) . "/start.php");
			break;

		case "add_oai_source":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script><script src=\"resources/javascript/add_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script><script src=\"resources/javascript/jquery-ui-1.8.14.custom.min.js\" type=\"text/javascript\" charset=\"utf-8\"></script><link type=\"text/css\" href=\"resources/css/jquery-ui-1.8.14.custom.css\" rel=\"stylesheet\" />", $output);
			require_once(dirname(__FILE__) . "/add_oai_source.php");
			break;

		case "save_oai_source":
			$output = str_replace("%javascript%", "", $output);
			require_once(dirname(__FILE__) . "/save_oai_source.php");
			break;

		case "list_oai_sources":
			$output = str_replace("%javascript%", "<script src=\"resources/javascript/list_oai_sources.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once(dirname(__FILE__) . "/list_oai_sources.php");
			break;

		case "edit_oai_source":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"resources/javascript/edit_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script><script src=\"resources/javascript/jquery-ui-1.8.14.custom.min.js\" type=\"text/javascript\" charset=\"utf-8\"></script><link type=\"text/css\" href=\"resources/css/jquery-ui-1.8.14.custom.css\" rel=\"stylesheet\" />", $output);
			require_once(dirname(__FILE__) . "/edit_oai_source.php");
			break;

		case "update_oai_source":
			$output = str_replace("%javascript%", "<script src=\"resources/javascript/update_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once(dirname(__FILE__) . "/update_oai_source.php");
			break;

		case "show_oai_source":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"resources/javascript/show_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once(dirname(__FILE__) . "/show_oai_source.php");
			break;

		case "delete_oai_source":
			$output = str_replace("%javascript%", "<script src=\"resources/javascript/delete_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once(dirname(__FILE__) . "/delete_oai_source.php");
			break;

		case "preview_oai_set":
			require_once(dirname(__FILE__) . "/preview_oai_set.php");
			break;


	}







}

@mysql_close($db_link);

echo str_replace("%content%", $content, $output);

?>
