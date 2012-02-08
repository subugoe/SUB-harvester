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
$jquery = "jquery-1.6.2.min.js";

// Template laden
$file = fopen('./templates/html_template.html', "r");
	
while (!feof($file)) {
        $output .= fgets($file);
}
fclose($file);
	
// Datenbankverbindung herstellen	
require_once("./db_connect.php");
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
	
	switch(isset($_POST['do']) ? $_POST['do'] : "" ) {
		
		case "":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"start.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once("./start.php");
			break;
			
		case "add_oai_source":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script><script src=\"add_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script><script src=\"jquery-ui-1.8.14.custom.min.js\" type=\"text/javascript\" charset=\"utf-8\"></script><link type=\"text/css\" href=\"jquery-ui-1.8.14.custom.css\" rel=\"stylesheet\" />", $output);
			require_once("./add_oai_source.php");
			break;
		
		case "save_oai_source":
			$output = str_replace("%javascript%", "", $output);
			require_once("./save_oai_source.php");
			break;
		
		case "list_oai_sources":
			$output = str_replace("%javascript%", "<script src=\"list_oai_sources.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once("./list_oai_sources.php");
			break;
			
		case "edit_oai_source":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"edit_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script><script src=\"jquery-ui-1.8.14.custom.min.js\" type=\"text/javascript\" charset=\"utf-8\"></script><link type=\"text/css\" href=\"jquery-ui-1.8.14.custom.css\" rel=\"stylesheet\" />", $output);
			require_once("./edit_oai_source.php");
			break;
			
		case "update_oai_source":
			$output = str_replace("%javascript%", "<script src=\"update_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once("./update_oai_source.php");
			break;
		
		case "show_oai_source":
			$output = str_replace("%javascript%", "<script src=\"$jquery\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"show_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once("./show_oai_source.php");
			break;

		case "delete_oai_source":
			$output = str_replace("%javascript%", "<script src=\"delete_oai_source.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);
			require_once("./delete_oai_source.php");
			break;
		
	}
	
	
	
	
	
	

}

@mysql_close($db_link);

echo str_replace("%content%", $content, $output);

?>