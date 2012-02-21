<?php

/*
 * Aktualisiert die Daten einer OAI-Quelle
 */


// Funktionen einbinden
require_once(dirname(__FILE__) . '/update_oai_source_funcs.php');

// Datensatz freigeben, dabei wird auch der Token verglichen, für den Fall, dass er schon abgelaufen ist
// TODO? Richtig wäre, den Datensatz erst am Ende freizugeben, aber es ist so unwahrscheinlich, dass ihn zwischenzeitlich (in dem Bruchteil der Sekunde) jemand öffnet
$sql = "DELETE FROM `oai_source_edit_sessions` 
		WHERE oai_source = ".$_POST['edit_id']." AND MD5(timestamp) = '".$_POST['edit_token']."'";
$result = mysql_query($sql, $db_link);
if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}

if ($_POST['edit_abort']) {
	
	// Abbruch -> Auf Startseite umleiten
	$content .= "<meta http-equiv=\"refresh\" content=\"0; URL=./index.php\" />";

} else {
	// Kopf
	$content .= "	<p style=\"text-align: right; margin-top: -20px;\"><input type=\"button\" value=\" Zur Startseite\" onclick=\"gotoStart();\"></input></p>\n";
	$content .= "	<h2>OAI-Quelle editieren</h2>";
	
	// Prüfen, ob der Datensatz noch reserviert war
	if (mysql_affected_rows($db_link) == 1) {
		// Die Session war noch aktiv und wurde gelöscht, Änderungen können gespeichert werden
		
		// Prüfen, ob eine Neuindexierung notwendig ist
		// Abfrage aus der Datenbank
		$sql = "SELECT
				oai_sources.view_creator AS 'view_creator', 
				oai_sources.view_contributor AS 'view_contributor', 
				oai_sources.view_publisher AS 'view_publisher', 
				oai_sources.view_date AS 'view_date', 
				oai_sources.view_identifier AS 'view_identifier', 
				oai_sources.index_relation AS 'index_relation', 
				oai_sources.index_creator AS 'index_creator', 
				oai_sources.index_contributor AS 'index_contributor', 
				oai_sources.index_publisher AS 'index_publisher', 
				oai_sources.index_date AS 'index_date', 
				oai_sources.index_identifier AS 'index_identifier', 
				oai_sources.index_subject AS 'index_subject', 
				oai_sources.index_description AS 'index_description', 
				oai_sources.index_source AS 'index_source', 
				oai_sources.dc_date_postproc AS 'dc_date_postproc', 
				oai_sources.identifier_filter AS 'identifier_filter', 
				oai_sources.identifier_resolver AS 'identifier_resolver', 
				oai_sources.identifier_resolver_filter AS 'identifier_resolver_filter', 
				oai_sources.identifier_alternative AS 'identifier_alternative', 
				oai_sources.country_code AS 'country', 
				oai_sources.from AS 'from', 
				oai_sources.harvested_since AS 'harvested_since', 
				oai_sources.last_harvest AS 'last_harvest', 
				oai_sources.reindex AS 'reindex', 
				oai_sets.harvest AS 'allSets_harvest' 
				FROM `oai_sources` INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source 
				WHERE oai_sources.id = ".$_POST['edit_id']." AND oai_sets.setSpec = 'allSets'";
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		$source_data_db = mysql_fetch_array($result, MYSQL_ASSOC);
		
		// Ist die Quelle schon zum Neuindexierung markiert? Dann ist die Prüfung nicht notwendig und die Reindexieurng bleibt erhalten
		if ($source_data_db['reindex']) {
			$reindex = 1;
		} else {
			// Prüfung ist notwendig
			$reindex = 0;
			foreach($source_data_db as $key => $value) {	
				// Bugfixing
				// $content .= "<p>Key: ".$key." - Value: ".$value."</p>";
				
				// Prüfung nur für relevante Felder
				if ($key != "reindex" && $key != "allSets_harvest" && $key != "from" && $key != "last_harvest" && $key != 'harvested_since') {
					
					// Der eigentliche Test
					if($key == "identifier_filter" || $key == "identifier_resolver" || $key == "identifier_alternative" || $key == "identifier_resolver_filter" || $key == "country" || $key == "dc_date_postproc") {
						// Stringvergleich bei diesen Werten
						if($_POST[$key] != $value) {
							$reindex = 1;
							break; // Prüfung beenden
						}
						
					} else {					
						// Bool-Vergleich
						if((isset($_POST[$key]) ? 1 : 0) != $value ) {
							// Ein Wert wurde geändert -> Neuindexierung nötig
							$reindex = 1;
							break; // Prüfung beenden
						}
					}
				}		
			}		
		}
		
		// Variable zum Speichern des "last_harvest", standardmäßig der Wert aus der Datenbank
		$new_last_harvest = is_null($source_data_db['last_harvest']) ? 'NULL' : $source_data_db['last_harvest'] ;
		
		// speichert die gelöschten Dateien, initial -2
		$delete_count = -2;
		
		// Ermitteln, ob Daten aus dem Index entfernt, bzw. nachgeladen werden müssen
		if ((strlen($_POST['from']) == 10 && strlen($_POST['current_from_db']) == 10 && $_POST['from'] > $_POST['current_from_db']) || (strlen($_POST['from']) == 10 && strlen($_POST['current_from_db']) != 10)) {
			// Das neue from-Datum ist neuer als das alte, bzw. es gibt keine altes
			// => Es müssen Indexeinträge bis zum Tag vor dem from-Datum entfernt werden			
					
			$delete_count = delete_source_dateRange($_POST['edit_id'], $_POST['new_from_day_before']);

		} else if ((strlen($_POST['from']) == 10 && strlen($_POST['current_from_db']) == 10 && $_POST['from'] < $_POST['current_from_db'])) {
			// Ist ein altes und eine neues from-Datum gesetzt und
			// Liegt das neue "from" Datum vor dem alten, müssen Daten nachgeharvested werden
			// Korrekt wäre mit "from" und "until" zu harvesten - da dies der aber nicht vorgesehen ist
			// wird einfach das "last_harvested" entsprechend zurückgesetzt,
			// was das Problem löst, wenn auch dabei "zu viel" harvested.
			$new_last_harvest = $_POST['from'];
		
		} else if (strlen($_POST['from']) == 0 && strlen($_POST['current_from_db']) == 10) {
			// Es gibt ein altes from-Datum. Dies wurde beim Editieren gelöscht
			// Der Nutzer wurde darauf hingewiesen, dass durch diese Konstellation die Quelle
			// komplett neu geharvested wird und hat dies bestätigt.
			$new_last_harvest = 'NULL';
		}
		
		// Veränderungen speichern
		// "Allgemeine Einstellungen"
		$sql = "UPDATE `oai_sources` SET 
					name = '".mysql_real_escape_string(stripslashes($_POST['name']))."' , 
					view_creator = ".(isset($_POST['view_creator']) ? 1 : 0)." , 
					view_contributor = ".(isset($_POST['view_contributor']) ? 1 : 0)." , 
					view_publisher = ".(isset($_POST['view_publisher']) ? 1 : 0)." , 
					view_date = ".(isset($_POST['view_date']) ? 1 : 0)." , 
					view_identifier = ".(isset($_POST['view_identifier']) ? 1 : 0)." , 
					index_relation = ".(isset($_POST['index_relation']) ? 1 : 0)." , 
					index_creator = ".(isset($_POST['index_creator']) ? 1 : 0)." , 
					index_contributor = ".(isset($_POST['index_contributor']) ? 1 : 0)." , 
					index_publisher = ".(isset($_POST['index_publisher']) ? 1 : 0)." , 
					index_date = ".(isset($_POST['index_date']) ? 1 : 0)." , 
					index_identifier = ".(isset($_POST['index_identifier']) ? 1 : 0)." , 
					index_subject = ".(isset($_POST['index_subject']) ? 1 : 0)." , 
					index_description = ".(isset($_POST['index_description']) ? 1 : 0)." , 
					index_source = ".(isset($_POST['index_source']) ? 1 : 0)." , 
					dc_date_postproc = ".$_POST['dc_date_postproc']." , 
					identifier_filter = '".mysql_real_escape_string(stripslashes($_POST['identifier_filter']))."' , 
					identifier_resolver = '".mysql_real_escape_string(stripslashes($_POST['identifier_resolver']))."' , 
					identifier_resolver_filter = '".mysql_real_escape_string(stripslashes($_POST['identifier_resolver_filter']))."' , 
					identifier_alternative = '".mysql_real_escape_string(stripslashes($_POST['identifier_alternative']))."' , 
					country_code = '".$_POST['country']."' , 
					active = ".(isset($_POST['active']) ? 1 : 0)." , 
					`from` = ".(strlen($_POST['from']) != 10 ? 'NULL' : "'".$_POST['from']."'")." , 
					harvested_since = ".( strlen($_POST['from']) == 10 && !is_null($source_data_db['harvested_since']) && ($source_data_db['harvested_since'] < $_POST['from']) ? "'".$_POST['from']."'" : !is_null($source_data_db['harvested_since']) ? "'".$source_data_db['harvested_since']."'" : "NULL" )." , 
					last_harvest = ".( $new_last_harvest == "NULL" ? "NULL" : "'".$new_last_harvest."'" )." , 
					harvest_period = ".$_POST['harvest_period']." , 
					reindex = ".$reindex." , 
					comment = '".mysql_real_escape_string(stripslashes($_POST['comment']))."' 
					WHERE id = ".$_POST['edit_id'];
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}	
		
		// Bugfixing
		//$content .= "<p>".$sql."</p>";
		
		// Sets
		if (count($_POST['sets']['unchanged']) > 0) {
			// Diese Sets sind bereits in der Datenbank			
			foreach($_POST['sets']['unchanged'] AS $set) {
				$sql = "UPDATE `oai_sets` SET 
						online = '1' , 
						harvest = ".(isset($set['harvest']) ? 1 : 0)." 
						WHERE id = ".$set['id'];
				$result = mysql_query($sql, $db_link);
				if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}	
			}
		}
		
		if (array_key_exists('new', $_POST['sets']) && count($_POST['sets']['new']) > 0) {
			// Neu hinzugekommene Sets	
			$sql = "INSERT INTO `oai_sets` ( 
				id,
				oai_source ,
				setSpec ,
				setName ,
				online ,
				harvest ,
				harvest_status ,
				index_status
				)
				VALUES ";
	
			foreach($_POST['sets']['new'] as $set) {
				$sql .= "(NULL , ".$_POST['edit_id'].", '".mysql_real_escape_string($set['setSpec'])."', '".mysql_real_escape_string(stripslashes($set['setName']))."', TRUE, ".(isset($set['harvest']) ? 1 : 0).", -1, 0), ";
			}
			
			$sql = substr($sql, 0, -2);
			$result = mysql_query($sql, $db_link);
			if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		}
		
		if (array_key_exists('deleted',$_POST['sets']) && count($_POST['sets']['deleted']) > 0) {
			// gelöschte Sets
			foreach($_POST['sets']['deleted'] AS $set) {
				$sql = "UPDATE `oai_sets` SET 
						online = '0' , 
						setName = '".mysql_real_escape_string(stripslashes($set['setName']))."' , 
						harvest = ".(isset($set['harvest']) ? 1 : 0)."  
						WHERE id = ".$set['id'];
				$result = mysql_query($sql, $db_link);
				if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}	
			}
		}
		
		
		$content .= "			<p>Die OAI-Quelle wurde gespeichert.</p>";
		
		// Anzeigen, ob die Quelle zum Neuindexierung markiert wurde
		if ($reindex) {
			$content .= "			<p>Die OAI-Quelle wird im nächsten Harvestvorgang komplett geharvested und mit den neuen Einstellungen indexiert. Die Änderungen im Index sind erst danach sichtbar!</p>";
		}
		
		// Für die Zeitangaben
		$german_months = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
		
		// Anzeigen, ob Einträge aus dem Index entfernt wurden, bzw. dabei ein Fehler auftrat
		// Der Fall "0" wird nicht bearbeitet, in diesem Fall erscheint keine Meldung (ist ja nix passiert).
		if ($delete_count > -2) {
			// Es wurde eine Löschanfrage gestellt
			if ($delete_count == -1) { 
				// Ein Fehler ist aufgetreten
				$content .= "			<p style=\"color: red; font-weight: bold;\">Beim Löschen der Indexeinträge ist ein Fehler aufgetreten. Bitte den Vorgang später noch einmal wiederholen (\"Harvesten ab\" um einen Tag verschieben).</p>";
			}
			
			if ($delete_count > 0) {
				// Indexeinträge wurden gelöscht
				$date = date_create($_POST['from']);
				$content .= "			<p><em>$delete_count</em> Einträge wurden aus dem Index entfernt. Es befinden sich jetzt nur noch Einträge ab dem ".(date_format($date, 'j. ')).$german_months[(date_format($date, 'n'))-1].(date_format($date, ' Y'))." im Index. </p>";
			}
		} else if (strlen($_POST['from']) == 10 && strlen($_POST['current_from_db']) == 10 && $_POST['from'] < $_POST['current_from_db']) {
			// Ist ein altes und eine neues from-Datum gesetzt und
			// Liegt das neue "from" Datum vor dem alten, müssen Daten nachgeharvested werden
			// siehe auch oben...
			$date = date_create($_POST['from']);
			$content .= "			<p>Das neue 'Harvesten ab'-Datum liegt vor dem alten, daher wird die Quelle ab dem ".(date_format($date, 'j. ')).$german_months[(date_format($date, 'n'))-1].(date_format($date, ' Y'))." neu geharvested.</p>";
		}
		
		if (strlen($_POST['from']) == 0 && strlen($_POST['current_from_db']) == 10) {
			// Es gibt ein altes from-Datum. Dies wurde beim Editieren gelöscht
			// Der Nutzer wurde darauf hingewiesen, dass durch diese Konstellation die Quelle
			// komplett neu geharvested wird und hat dies bestätigt.
			$content .= "			<p>Das 'Harvesten ab'-Datum wurde gelöscht - die Quelle wird komplett neu geharvested.</p>";
		}

		
		
	} else {
		// Die Session ist abgelaufen
		$content .= "			<p>Die Editiersession ist abgelaufen und der Datensatz wurde in der Zwischenzeit zum Editieren geöffnet.</p>";
		$content .= "			<p style=\"color: red; font-weight: bold;\">Die Änderungen wurden nicht gespeichert.</p>";
	}
	
	
	// Button
	
	// Zurück zur Trefferliste
	$content .= "			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n";
	$content .= "				<div>\n";
	$content .= "					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n";
	
	// filter_name
	$content .= "				<input type=\"hidden\" name=\"filter_name\" value=\"";
	$current_filter_name = isset($_POST['filter_name']) ? $_POST['filter_name'] : "";
	$content .= $current_filter_name."\"></input>\n";
	
	// filter_url
	$content .= "				<input type=\"hidden\" name=\"filter_url\" value=\"";
	$current_filter_url = isset($_POST['filter_url']) ? $_POST['filter_url'] : "";
	$content .= $current_filter_url."\"></input>\n";
	
	// filter_bool
	$content .= "				<input type=\"hidden\" name=\"filter_bool\" value=\"";
	$current_filter_bool = isset($_POST['filter_bool']) ? $_POST['filter_bool'] : "AND";
	$content .= $current_filter_bool."\"></input>\n";
	
	// sortby
	$content .= "				<input type=\"hidden\" name=\"sortby\" value=\"";
	$current_sortby = isset($_POST['sortby']) ? $_POST['sortby'] : "name";
	$content .= $current_sortby."\"></input>\n";
	
	// sorthow
	$content .= "				<input type=\"hidden\" name=\"sorthow\" value=\"";
	$current_sorthow = isset($_POST['sorthow']) ? $_POST['sorthow'] : "ASC";
	$content .= $current_sorthow."\"></input>\n";
	
	// id
	$content .= "				<input type=\"hidden\" name=\"id\" value=\"";
	$content .= isset($_POST['id']) ? $_POST['id'] : "none";
	$content .= "\"></input>\n";
	
	// start
	$content .= "				<input type=\"hidden\" name=\"start\" value=\"";
	$current_start = isset($_POST['start']) ? $_POST['start'] : "0";
	$content .= $current_start;
	$content .= "\"></input>\n";
	
	// limit
	$content .= "				<input type=\"hidden\" name=\"limit\" value=\"";
	$current_limit = isset($_POST['limit']) ? $_POST['limit'] : 20;
	$content .= $current_limit;
	$content .= "\"></input>\n";
	
	// show_active
	$content .= "				<input type=\"hidden\" name=\"show_active\" value=\"";
	$current_show_active = isset($_POST['show_active']) ? $_POST['show_active'] : 0;
	$content .= $current_show_active;
	$content .= "\"></input>\n";
	
	// show_status
	$content .= "				<input type=\"hidden\" name=\"show_status\" value=\"";
	$current_show_status = isset($_POST['show_status']) ? $_POST['show_status'] : 0;
	$content .= $current_show_status;
	$content .= "\"></input>\n";
	
	/*
	if ($token) {
		// edit_id
		$content .= "				<input type=\"hidden\" name=\"edit_id\" value=\"".$_POST['id']."\"></input>\n";
		// token
		$content .= "				<input type=\"hidden\" name=\"edit_token\" value=\"".$token."\"></input>\n";
	} */
	
	$content .= "			</div>\n";
	$content .= "				<p style=\"text-align: center; margin-top: 5px;\">\n";
	$content .= "					<input type=\"submit\" value=\" Zurück zur Trefferliste\" onclick=\"document.forms[0].action = 'index.php#filter'\"></input>\n";
	$content .= "				</p>\n";		
	$content .= "			</form>\n";
	
}

?>
