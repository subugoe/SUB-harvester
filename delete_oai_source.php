<?php
/*
 * Löscht eine OAI-Quelle, geht dabei in folgender Reihenfolge vor:
 * 1. Index
 * 2. Datenbank
 *    2.1 Sessions (oai_source_edit_sessions)
 *    2.2 Logs (oai_logs)
 *    2.3 Sets (oai_sets)
 *    2.4 Quelle (oai_sources)
 */


// Kopf
$content .= "	<h2>OAI-Quelle löschen</h2>";

if (array_key_exists('confirmed', $_POST)) {
	
	// Wird auf false gesetzt, falls irgendwo was schiefgeht
	$delete_successful = true;
	
	// Indexeinträge entfernen
	// Anzahl der Indexeinträge zur Anzeige direkt vom dem Löschen abfragen
	
	$index_entry_count = 0;
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, SOLR."/select?version=2.2&rows=0&q=oai_repository_id%3A".$_POST['id']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$http_response = curl_exec($ch);
	
	if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
		
		$dom = new DOMDocument();
		$dom->loadXML($http_response);	
	
		$XPath = new DOMXPath($dom);
		$XPath_count_query = $XPath->query('/response/result/@numFound');
	
		$index_entry_count = $XPath_count_query->item(0)->nodeValue;
	
	} else {
		// Der Server ist nicht erreichbar
		$index_entry_count = -1;
	}
	
	$delete_xml = "";
	// Löschanweisung laden
	$file = fopen(dirname(__FILE__) . '/templates/remove_oai_source.xml', "r");
	while (!feof($file)) {
	        $delete_xml .= fgets($file);
	}
	fclose($file);
	
	// ID eintragen
	$delete_xml = str_replace("%id%", $_POST['id'], $delete_xml);
	
	// Temporäre Datei mit der Löschanfrage erzeugen
	$temp_filename = tempnam(sys_get_temp_dir(), "delete");
	
	// Daten schreiben
	$temp_file = fopen($temp_filename, "w");
	fwrite($temp_file, $delete_xml);
	fclose($temp_file);

	if ($temp_filename) {
		
		if ($index_entry_count >= 0) {
			// Index ist online, Löschanfrage stellen
			curl_setopt($ch, CURLOPT_URL, SOLR."/update");
			
			// Post setzen
			$post = array('file' => '@'.$temp_filename);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			
			$response = curl_exec($ch);
			
			// Temporäre Datei löschen
			unlink($temp_filename);
			
			// Commit senden
			$post = array('file' => '@'. dirname(__FILE__) . '/templates/commit.xml');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
			
			$response = curl_exec($ch);
			
			// Erneuter Abruf der Anzahl der Indexeinträge der Quelle (muss jetzt 0 sein)
			curl_setopt($ch, CURLOPT_URL, SOLR."/select?version=2.2&rows=0&q=oai_repository_id%3A".$_POST['id']);
			
			$http_response = curl_exec($ch);
			
			if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
				
				$dom = new DOMDocument();
				$dom->loadXML($http_response);	
			
				$XPath = new DOMXPath($dom);
				$XPath_count_query = $XPath->query('/response/result/@numFound');
			
				$index_entry_count_check = $XPath_count_query->item(0)->nodeValue;
			
			} else {
				// Der Server ist nicht erreichbar
				$index_entry_count_check = -1;
			}
			
			// Alle Indexeinträge müsste jetzt entfernt sein
			if ($index_entry_count_check == 0) {
				// Das Löschen war erfolgreich
				$content .= "	<p><em>".$index_entry_count."</em> Indexeintr".( $index_entry_count == 1 ? "ag" : "äge" )." gelöscht.<br />";
			} else {
				// Indexeinträge gibt es noch
				$content .= "	<p>Fehler beim Löschen der Indexeinträge. Es befinden sich noch ".$index_entry_count_check." Einträge im Index. Bitte manuell alle Einträge der ID <em>".$_POST['id']."</em> löschen.</p>";
			}
			
		} else {
			$content .= "<p>Der Index ist nicht erreichbar. Die Einträge konnten nicht gelöscht werden. Bitte manuell alle Einträge der ID <em>".$_POST['id']."</em> löschen.</p>";
		}
	} else {
		$content .= "<p>Fehler beim Erzeugen der Temporären Datei. Die Einträge konnten nicht gelöscht werden. Bitte manuell alle Einträge der ID <em>".$_POST['id']."</em> löschen.</p>";
	}
	
	
	// evtl. geharvestete Daten löschen?????? TODO
	
	// Falls noch eine Session gespeichert ist...
	// Falls der Datensatz gerade editiert wird - Pech für den Editierenden... dies wird nicht geprüft.
	$sql = "DELETE FROM oai_source_edit_sessions
			WHERE oai_source = " . intval($_POST['id']);
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
	
	$sql = "DELETE FROM oai_logs
			WHERE oai_set IN ( 
				SELECT id FROM oai_sets 
				WHERE oai_source = " . intval($_POST['id']) . "
			)";
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
	
	$content .= "	<em>".mysql_affected_rows($db_link)."</em> Logeinträge gelöscht (Tabelle \"oai_logs\").<br />";
	
	// Sets der OAI-Quelle löschen
	$sql = "DELETE FROM oai_sets
			WHERE oai_source = " . intval($_POST['id']);
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
	
	if (mysql_affected_rows($db_link) < 1) {$delete_successful = false;}
	$content .= "	<em>".mysql_affected_rows($db_link)."</em> Sets gelöscht (Tabelle \"oai_sets\").<br />";
	
	// OAi-Quelle löchen
	$sql = "DELETE FROM oai_sources
			WHERE id = " . intval($_POST['id']);
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
	
	if (mysql_affected_rows($db_link) < 1) {$delete_successful = false;}
	$content .= "	<em>".mysql_affected_rows($db_link)."</em> OAI-Quelle gelöscht (Tabelle \"oai_sources\").</p>";
	
	if ($delete_successful) {
		$content .= "	<p>Die OAI-Quelle wurde erfolgreich gelöscht.</p>";
	} else {
		$content .= "	<p>Beim Löschen der OAI-Quelle sind Probleme aufgetreten, bitte Datenbank und Index prüfen!</p>";
	}
	
	
} else {
	// Bestätigungsformular generieren
	
	// Daten abfragen
	// Abfrage der Informationen zur Quelle
	$sql = "SELECT 	
				oai_sources.id AS id,
				oai_sources.url AS url,
				oai_sources.name AS name,
				DATE_FORMAT(oai_sources.added, '%W, %e. %M %Y, %k:%i Uhr') AS added,
				COUNT(oai_sets.id) - 1 AS sets 
			FROM oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source
			WHERE oai_sources.id = " . intval($_POST['id']) . "
			GROUP BY oai_sources.id";
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
	$oai_source_data = mysql_fetch_array($result, MYSQL_ASSOC);
	
	
	$content .= "		<table border=\"0\" width=\"100%\">\n";
	$content .= "			<colgroup>\n";
	$content .= "			    <col width=\"23%\" />\n";
	$content .= "			    <col width=\"77%\" />\n";
	$content .= "			 </colgroup>";
	$content .= "			<tr>\n";
	$content .= "				<td align=\"right\" class=\"table_field_description\">Name:</td>\n";
	$content .= "				<td align=\"left\" class=\"table_field_data\">".$oai_source_data['name']."</td>\n";
	$content .= "			</tr>\n";
	$content .= "			<tr>\n";
	$content .= "				<td align=\"right\" class=\"table_field_description\">Request URL:</td>\n";
	$content .= "				<td align=\"left\" class=\"table_field_data\">".$oai_source_data['url']."</td>\n";
	$content .= "			</tr>\n";
	$content .= "			<tr>\n";
	$content .= "				<td align=\"right\" class=\"table_field_description\">Id:</td>\n";
	$content .= "				<td align=\"left\" class=\"table_field_data\">".$oai_source_data['id']."</td>\n";
	$content .= "			</tr>\n";
	$content .= "			<tr>\n";
	$content .= "				<td align=\"right\" class=\"table_field_description\">Hinzugefügt:</td>\n";
	$content .= "				<td align=\"left\" class=\"table_field_data\">".$oai_source_data['added']."</td>\n";
	$content .= "			</tr>\n";
	$content .= "			<tr>\n";
	$content .= "				<td align=\"right\" class=\"table_field_description\">Anzahl der Sets:</td>\n";
	$content .= "				<td align=\"left\" class=\"table_field_data\">".$oai_source_data['sets']."</td>\n";
	$content .= "			</tr>\n";
	$content .= "			<tr>\n";
	$content .= "				<td align=\"right\" class=\"table_field_description\">Anzahl der Indexeinträge:</td>\n";	
	
	// Anzahl der Indexeinträge
	
	$index_entry_count = 0;
	
	// Index abfragen
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, SOLR."/select?version=2.2&rows=0&q=oai_repository_id%3A".$oai_source_data['id']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	$http_response = curl_exec($ch);
	
	if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
		
		$dom = new DOMDocument();
		$dom->loadXML($http_response);	
	
		$XPath = new DOMXPath($dom);
		$XPath_count_query = $XPath->query('/response/result/@numFound');
	
		$index_entry_count = $XPath_count_query->item(0)->nodeValue;
	
	} else {
		// Der Server ist nicht erreichbar
		$index_entry_count = -1;
	}

	$content .= "					<td align=\"left\" class=\"table_field_data\">".( $index_entry_count >= 0 ? $index_entry_count : "<span style=\"color: red;\">Der Index ist zurzeit nicht erreichbar.</span>" )."</td>\n";
	$content .= "			</tr>\n";
	$content .= "		</table>\n";
	$content .= "		<p style=\"color: red; font-size: 20px; font-weight: bold; text-align: center;\">Soll diese OAI-Quelle endgültig aus der Datenbank und dem Index gelöscht werden?</p>\n";
}

// Formular mit den Daten einer evtl. vorgegangenen Suche um nach dort zurückzukehren
$content .= "			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n";
$content .= "				<div>\n";
$content .= "					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n";

// filter_name
$content .= "					<input type=\"hidden\" name=\"filter_name\" value=\"";
$current_filter_name = isset($_POST['filter_name']) ? $_POST['filter_name'] : "";
$content .= $current_filter_name."\"></input>\n";

// filter_url
$content .= "					<input type=\"hidden\" name=\"filter_url\" value=\"";
$current_filter_url = isset($_POST['filter_url']) ? $_POST['filter_url'] : "";
$content .= $current_filter_url."\"></input>\n";

// filter_bool
$content .= "					<input type=\"hidden\" name=\"filter_bool\" value=\"";
$current_filter_bool = isset($_POST['filter_bool']) ? $_POST['filter_bool'] : "AND";
$content .= $current_filter_bool."\"></input>\n";

// sortby
$content .= "					<input type=\"hidden\" name=\"sortby\" value=\"";
$current_sortby = isset($_POST['sortby']) ? $_POST['sortby'] : "name";
$content .= $current_sortby."\"></input>\n";

// sorthow
$content .= "					<input type=\"hidden\" name=\"sorthow\" value=\"";
$current_sorthow = isset($_POST['sorthow']) ? $_POST['sorthow'] : "ASC";
$content .= $current_sorthow."\"></input>\n";

// id
$content .= "					<input type=\"hidden\" name=\"id\" value=\"";
$content .= isset($_POST['id']) ? $_POST['id'] : "none";
$content .= "\"></input>\n";

// edit_token
$content .= "					<input type=\"hidden\" name=\"edit_token\" value=\"".$_POST['edit_token']."\"></input>\n";

// edit_id
$content .= "					<input type=\"hidden\" name=\"edit_id\" value=\"".$_POST['id']."\"></input>\n";

// start
$content .= "					<input type=\"hidden\" name=\"start\" value=\"";
$current_start = isset($_POST['start']) ? $_POST['start'] : "0";
$content .= $current_start;
$content .= "\"></input>\n";

// limit
$content .= "					<input type=\"hidden\" name=\"limit\" value=\"";
$current_limit = isset($_POST['limit']) ? $_POST['limit'] : 20;
$content .= $current_limit;
$content .= "\"></input>\n";

// show_active
$content .= "					<input type=\"hidden\" name=\"show_active\" value=\"";
$current_show_active = isset($_POST['show_active']) ? $_POST['show_active'] : 0;
$content .= $current_show_active;
$content .= "\"></input>\n";

// show_status
$content .= "					<input type=\"hidden\" name=\"show_status\" value=\"";
$current_show_status = isset($_POST['show_status']) ? $_POST['show_status'] : 0;
$content .= $current_show_status;
$content .= "\"></input>\n";

// show_status
$content .= "					<input type=\"hidden\" name=\"show_status\" value=\"";
$current_show_status = isset($_POST['show_status']) ? $_POST['show_status'] : 0;
$content .= $current_show_status;
$content .= "\"></input>\n";

// confirmed
$content .= "					<input type=\"hidden\" name=\"confirmed\" value=\"1\"></input>\n";

$content .= "				</div>\n";
// Buttons
$content .= "				<p style=\"text-align: center; margin-top: 25px;\">\n";

if (!isset($_POST['confirmed'])) {
	$content .= "					<input type=\"submit\" value=\" OAI-Quelle Löschen\" onclick=\"remove(".$oai_source_data['id'].")\"></input><br /><br /><br />\n";
	$content .= "					<input type=\"submit\" value=\" Bearbeiten\" onclick=\"edit(".$oai_source_data['id'].")\"></input>&nbsp;\n";
	$content .= "					<input type=\"submit\" value=\" Anzeigen\" onclick=\"show(".$oai_source_data['id'].")\"></input><br />\n";
}
$content .= "					<input type=\"submit\" value=\" Zurück zur Trefferliste\" onclick=\"document.forms[0].action = 'index.php#filter'\"></input>\n";
$content .= "				</p>\n";		
$content .= "			</form>\n";


?>
