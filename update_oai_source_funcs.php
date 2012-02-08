<?php
/*
 * Stellt Funktionen für update_oai_source.php zur Verfügung
 */

// Einstellungen einbinden
require_once("./scripts/settings.php");

// Ermittelt die Anzahl der Indexeinträge der Quelle mit der $oai_source_id 
// mit oai_datestamp bis zum Zeitpunkt $until (Format "JJJJ-MM-TT")
// Gibt die Anzahl der Indexeinträge zurück, im Fehlerfall -1
function get_source_count_dateRange($oai_source_id, $until) {
	
	$index_entry_count = 0;
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, SOLR."/select?version=".urlencode("2.2")."&rows=0&q=".urlencode("+oai_repository_id:".$_POST['id']." +oai_datestamp:[* TO ".$until."T23:59:59Z]"));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$http_response = curl_exec($ch);
	
	if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
		
		$dom = new DOMDocument();
		$dom->loadXML($http_response);	
	
		$XPath = new DOMXPath($dom);
		$XPath_count_query = $XPath->query('/response/result/@numFound');
	
		$index_entry_count = $XPath_count_query->item(0)->nodeValue;
		
		curl_close($ch);
		return $index_entry_count;
		
	} else {
		// Der Server ist nicht erreichbar
		curl_close($ch);
		return -1;
	}
}


// Löscht alle Indexeinträge der Quelle mit der $oai_source_id mit oai_datestamp
// bis zum Zeitpunkt $until (Format "JJJJ-MM-TT")
// gibt die Anzahl der gelöschten Datensätze zurück, im Fehlerfall -1
function delete_source_dateRange($oai_source_id, $until) {
	
	// Sind überhaupt Einträge von der Löschanfrage betroffen?
	$delete_count = get_source_count_dateRange($oai_source_id, $until);
	
	if ($delete_count > 0) {
	
		$delete_xml = "";
		// Löschanweisung laden
		$file = fopen('./templates/remove_source_daterange.xml', "r");
		while (!feof($file)) {
		        $delete_xml .= fgets($file);
		}
		fclose($file);
		
		// ID eintragen
		$delete_xml = str_replace("%id%", $oai_source_id, $delete_xml);
		$delete_xml = str_replace("%until%", $until, $delete_xml);
		
		// Temporäre Datei mit der Löschanfrage erzeugen
		$temp_filename = tempnam(sys_get_temp_dir(), "delete");
		
		// Daten schreiben
		$temp_file = fopen($temp_filename, "w");
		fwrite($temp_file, $delete_xml);
		fclose($temp_file);
	
		// Inhalt Schreiben
		if ($temp_filename)  {
			
			if ($delete_count > -1) {
				// Index ist online, Löschanfrage stellen
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, SOLR."/update");
				
				// Post setzen
				$post = array('file' => '@'.$temp_filename);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				$response = curl_exec($ch);
				
				// Temporäre Datei löschen
				unlink($temp_filename);
				
				// Commit senden
				$post = array('file' => '@'.getcwd().'/templates/commit.xml');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
				
				$response = curl_exec($ch);
				
				curl_close($ch);
				
				// Erneuter Abruf der Anzahl der Indexeinträge der Quelle (muss jetzt 0 sein)
				if (get_source_count_dateRange($oai_source_id, $until) == 0) {
					// Löschen erfolgreich
					return $delete_count;	
				}
			}
			// Index war nicht zu erreichen
		}
		// Ein Fehler beim Schreiben der temporären Datei ist aufgetreten
		return -1;
	} else {
		// Zählung ergab 0 bzw. einen Fehler -1
		return $delete_count;
	}
}

























?>