<?php

/*
 * Script, das das Harvesten der OAI-Quellen steuert.
 * Es führt keine Weiterverabeitung der Daten durch. Diese Aufgabe übernimmt das
 * Indexerscript.
 * 
 * Beim Harvesten erzeugt dieses Script Logmeldungen.
 */

// Funktionen einbinden
require_once(dirname(__FILE__) . '/scripts_funcs.php');
// Einstellungen laden
readConfiguration();

// --------------------------------------------------------------------------------

// Für Verzeichnisnamen
$current_date = date('Y-m-d') . 'T' . date('H-i-s');


// wenn nötig, »harvest« Ordner anlegen
if (!file_exists(HARVEST_FOLDER)) {
	mkdir(HARVEST_FOLDER, CHMOD, TRUE);
}

// Prüfen, ob bereits ein Harvestprozess läuft
if (file_exists(HARVEST_FOLDER."/HARVESTING.txt")) {
	// Harvesten nicht starten
	echo utf8_decode("Es läuft bereits ein Harvesting-Prozess - Vorgang für $current_date abgebrochen.");
} else {
	
// Sperrdatei anlegen, damit der Harvestprozess nicht doppelt läuft.
$filename = HARVEST_FOLDER."/HARVESTING.txt";	
$text = "HARVESTING";
$file = fopen($filename, "w");
fputs($file, $text);
fclose($file);


// Datenbankverbindung herstellen	
@$db_link = mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if (!$db_link) {
	
	// Wenn das Dateisystem hier auch Probleme macht, Pech... 
	@mkdir(HARVEST_FOLDER."/".$current_date, CHMOD);
	
	$filename = HARVEST_FOLDER."/".$current_date."/mysql_error.txt";
	$file = fopen($filename, "w");
	fputs($file, "Kein Zugriff auf die Datenbank '".DB_SERVER."' möglich.\n Harvesten abgebrochen.");
	fclose($file);

} else {

// Rechte???? TODO
// Verzeichnis für Harvesttag erstellen
if(mkdir(HARVEST_FOLDER."/".$current_date, CHMOD)) {
	
	

// DB-Einstellungen
mysql_select_db(DB_NAME);
mysql_query("SET NAMES 'utf8'");
mysql_query("SET CHARACTER SET 'utf8'");

// Datenbankabfrage
$sql = "SELECT 	oai_sources.id AS source_id , 
				oai_sources.url AS url , 
				oai_sources.name AS source_name , 
				oai_sources.reindex AS reindex ,
				DATE_FORMAT(oai_sources.from, '%Y-%m-%d') AS 'start_from' , 
				DATE_FORMAT(oai_sources.harvested_since, '%Y-%m-%d') AS 'harvested_since' , 
				oai_sets.id AS set_id , 
				oai_sets.setSpec AS setSpec , 
				oai_sets.setName AS setName , 
				DATE_FORMAT(oai_sets.last_harvested - INTERVAL 1 DAY, '%Y-%m-%d') AS 'from' ,
				oai_sets.harvest_status AS harvest_status 
		FROM 	oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source
		WHERE 	oai_sources.active = TRUE 
				AND oai_sets.harvest = TRUE 
				AND ( 
					CURDATE() >= (oai_sources.last_harvest + INTERVAL 1 DAY + INTERVAL harvest_period DAY) 
					OR oai_sources.last_harvest IS NULL 
					)
		ORDER BY source_id , set_id";

$result = mysql_query($sql, $db_link);

if (!$result) { 
	// Fehlermeldung in Textdatei
	$filename = HARVEST_FOLDER."/".$current_date."/mysql_error.txt";
	$file = fopen($filename, "w");
	
	$text = "";
	$text .= "Fehler bei der Datenbankabfrage:\n\n";
	$text .= "\"".mysql_error($db_link)."\"\n\n";
	$text .= "Die SQL-Abfrage war:\n\n";
	$text .= "\"".$sql."\"";
	
	fputs($file, $text);
	fclose($file);

} else {

if (mysql_num_rows($result) == 0) {
	// Heute keine Quelle zum Harvesten
	// Meldung in Textdatei, da Logs mit Sets verknüpft sind - passt einfach nicht in DB
	
	$filename = HARVEST_FOLDER."/".$current_date."/no_harvest_scheduled.txt";
	$file = fopen($filename, "w");
	fputs($file, "Kein Quelle zum Harvesten vorgesehen.");
	fclose($file);
	
} else {
	
// Aus der Datenbankabfrage ein mehrdimensionales Array mit den Harvestaufgaben aufbauen.
	
$source_id = "";
$harvest_tasks = Array();

while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	// Neue Quelle?
	if ($source_id != $row['source_id']) {
		$harvest_tasks[] = 
			Array(
				"source_id"			=> $row['source_id'] , 
				"url"				=> $row['url'] , 
				"source_name"		=> $row['source_name'] ,
				"reindex"			=> $row['reindex'] ,
				"start_from"		=> $row['start_from'] , 
				"harvested_since"	=> $row['harvested_since'] , 
				"sets"				=> Array()
			);
		$source_id = $row['source_id'];
	}
	
	// Setdaten hinzufügen
	$harvest_tasks[count($harvest_tasks)-1]['sets'][] = 
		Array(
			"set_id"			=> $row['set_id'] ,
			"setName"			=> $row['setName'] , 
			"setSpec"			=> $row['setSpec'] ,
			"harvest_status"	=> $row['harvest_status'] , 
			"from"				=> $row['from'] 
		);
}


/**********************************
 * Start des Harvestens
 * 
 */ 


// Http-Handler initialisieren
$oai_harvester_ch = curl_init();

// Einstellungen setzen
curl_setopt($oai_harvester_ch, CURLOPT_RETURNTRANSFER, 1);

// Ignoriere SSL-Zertifikate bei HTTPS
curl_setopt($oai_harvester_ch, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($oai_harvester_ch, CURLOPT_LOW_SPEED_LIMIT, SPEED_LIMIT);
curl_setopt($oai_harvester_ch, CURLOPT_LOW_SPEED_TIME, SPEED_TIME);

// UserAgent setzen
curl_setopt($oai_harvester_ch, CURLOPT_USERAGENT, USERAGENT);

// Wert für den until-paramemter = gestern
$oai_until = date("Y-m-d", strtotime("-1 days"));

// Alle Quelle bearbeiten	
foreach($harvest_tasks as $oai_source) {
	
	// Verzeichnis erstellen
	if (!mkdir(HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id'], CHMOD)) {
		// Probleme beim Erstellen des Verzeichnisses
		// Harvesten abbrechen, Fehlermeldung wird an das erste zu Harvestende Set gehängt.
		insert_log($oai_source['sets'][0]['set_id'], 1, "Fehler beim Erstellen des Verzeichnises \"".HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id']."\". Komplettes Harvesten abgebrochen", $db_link, 0, 0, 0);
		break;
		
	} else {
		// Verzeichnis für Quelle erfolgreich erstellt
		
		// Quelle für den Indexer sperren
		
		$filename = HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id']."/HARVESTING";	
		$text = "HARVESTING";
		$file = fopen($filename, "w");
		fputs($file, $text);
		fclose($file);
	
		// Textdatei mit Quelldaten schreiben
		
		$filename = HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id']."/source_data.txt";
		
		$text = "";
		$text .= "Id:\t\t\t".$oai_source['source_id'].chr(13).chr(10);
		$text .= "Name:\t\t\t".$oai_source['source_name'].chr(13).chr(10);
		$text .= "Url:\t\t\t".$oai_source['url'].chr(13).chr(10);
		$text .= "Neuindexierung:\t\t".( $oai_source['reindex'] ? "Ja" : "Nein" ).chr(13).chr(10);
		$text .= "Start from:\t\t".( is_null($oai_source['start_from']) ? "Nicht gesetzt" : $oai_source['start_from']); 
		
		$file = fopen($filename, "w");
		fputs($file, $text);
		fclose($file);
		
		
		// Bestimmung des from-Paramters
		// In der Regel ist der Zeitpunkt, der in den Setdaten gespeichert ist,
		// es gibt aber Ausnahmen, bei denen bestimmte Quellen-Einstellung
		// einen anderen Wert erfordern. Dieser gilt dann für alle Sets der Quelle.
		
		// Speichert den from Wert, falls Quellen-Einstellungen greifen, sonst bleibt
		// er ein leerer String. Dieser Wert (soweit nicht leer) aktualisert auch 
		// harvested_since in der Datenbank
		$source_from = "";
		
		// Soll die Quelle neu indexiert werden?
		if ($oai_source['reindex']) {
			// Reindex ist gesetzt, soll die Quelle erst ab einem bestimmten Zeitpunkt geharvested werden?
			if (!is_null($oai_source['start_from'])) {
				// start_from ist vorhanden und wird gesetzt.
				$source_from = $oai_source['start_from'];
			} else {
				// Ist kein start_from vorhanden, erfolgt der Aufruf ohne from-Parameter
				$source_from = 'reindex';
			}
		} else {
			// Quelle ist nicht zum Neuindexierung vorgesehen.
			if (!is_null($oai_source['start_from']) && !is_null($oai_source['harvested_since'])) {			
				// Sowohl start_from als auch harvested_since sind gesetzt
				// Die Quelle wurde schon mind. einmal geharvested und es gibt ein
				// vorgegebenes Startdatum (Harvesten ab)
				
				// Wurde der Zeitpunkt zum Starten des Harvestens geändert?
				// Hierbei ist nur der Fall interessant, dass der neue Startzeitpunkt vor dem alten liegt.
				// (Der andere denkbare Fall ist, dass sie gleich sind, da evtl. schon das 
				//  Update-Script gegriffen hat)
				if ($oai_source['start_from'] < $oai_source['harvested_since']) {
					// Der neue Startzeitpunkt liegt vor dem alten
					// => der neue Startzeitpunkt muss zum Harvesten verwendet werden und
					//    auch gespeichert werden (s. u.)
					$source_from = $oai_source['start_from'];
				}
			} else if (!is_null($oai_source['start_from']) && is_null($oai_source['harvested_since'])) {
				// Trifft beim ersten Harvesten auf, wenn ein Startdatum gesetzt wurde, bzw.
				// beim ersten Harvesten nachdem erstmalig ein Startdatum gesetzt wurde
				$source_from = $oai_source['start_from'];
			}
		}		
		
		// Alle Sets bearbeiten
		foreach($oai_source['sets'] as $oai_set) {
			// Verzeichnis erstellen
			
			$set_folder = HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id']."/".$oai_set['set_id'];
			
			if (!mkdir($set_folder, CHMOD)) {
				// Probleme beim Erstellen des Verzeichnisses
				
				insert_log($oai_set['set_id'], 1, "Fehler beim Erstellen des Verzeichnises \"".$set_folder."\".", $db_link, 0, 0, 0);
				// Abbrechen, nächste Quelle versuchen
				break;
			
			} else {
				// Verzeichnis für Set erfolgreich erstellt
				
				// Textdatei mit Setdaten schreiben
				$filename = $set_folder."/set_data.txt";
		
				$text = "";
				$text .= "Id:\t\t\t\t".$oai_set['set_id'].chr(13).chr(10);
				$text .= "setName:\t\t".$oai_set['setName'].chr(13).chr(10);
				$text .= "setSpec:\t\t".$oai_set['setSpec'].chr(13).chr(10);
				$text .= "from:\t\t\t".( is_null($oai_set['from']) ? "noch nie geharvestet" : $oai_set['from'] ) .chr(13).chr(10);
				$text .= "Harvest-Status:\t".$oai_set['harvest_status'];
				
				$file = fopen($filename, "w");
				fputs($file, $text);
				fclose($file);
				
				// Nummerierung für die geharvesteten XML-Dateien
				$i = 0;
				
				// Geharvestete Records zählen (Antwort wird eh geparsed...)
				$records_harvested = 0;
				
				// Speichert, ob in der Schleife schon ein Logeintrag erzeugt wurde
				$log_created = false;
				// Speichert, ob das Harvesten mit einer Fehlermeldung abgebrochen hat
				$harvest_successful = true;
				
				// Speichert, Dateien gespeichert worden sind
				// $files_generated = true;
				
				// Speichert, ob die Anfrage mit "noRecordsMatch" beantwortet wurde
				$norecordsmatch = false;
				
				// Zeitpunkt der ersten Anfrage, dieser ist ausschlaggebend für den nächsten
				// Harvestvorgang (zur Sicherheit wird dort noch einen Tag zurück gerechnet
				// für evtl. Zeitzonenprobleme)
				$oai_last_harvest = date("Y-m-d H:i:s", time());
				
				// Zählt, wie oft eine Anfrage abgebrochen worden ist.
				$error_counter = 0;
				
				do {
					// Zeit in Sekunden, bis das Script abbricht. Muss vor jedem Schleifendurchlauf neu
					// gesetzt werden.
					set_time_limit(SPEED_TIME + 30);
					
					$url = $oai_source['url']."?verb=ListRecords";
					
					if (isset($resumptionToken)) {
						// Abfrage mit resumptionToken 
						$url .= "&resumptionToken=".urlencode($resumptionToken);
				
					} else {
						// Die erste Anfrage
						// Format
						$url .= "&metadataPrefix=oai_dc";
						
						// Startzeitpunkt ermitteln
						// Ist er von den Quelleneinstellungen vorgegeben?
						if (strlen($source_from) > 1) {
							// Soll die Quelle neu indexiert werden?
							if ($source_from != 'reindex') {							
								// Eine Quelleneinstellung überlagert den eigentlichen Zeitpunkt
								$url .= "&from=".$source_from;
							}
						} else {
							// Keine Quellenseinstellung überlagert den eigentlichen Zeitpunkt
							// Ist ein Wert vorhanden?
							if (!is_null($oai_set['from'])) {
								// Der Wert ist vorhanden und wird verwendet
								$url .= "&from=".$oai_set['from'];
							}
						}
						
						// Set
						if ($oai_set['setSpec'] != "" && $oai_set['setSpec'] != "allSets") {
							$url .= "&set=".$oai_set['setSpec'];
						}
					}
					
					curl_setopt($oai_harvester_ch, CURLOPT_URL, $url);
					$http_response = curl_exec($oai_harvester_ch);
					
					// Ist der Server erreichbar und ist seine Antwort nicht leer?
					if ($http_response && curl_getinfo($oai_harvester_ch, CURLINFO_HTTP_CODE) == 200) {
						
						$xml_parseable = false;
						$dom = new DOMDocument();
						
						$xml_parseable = @$dom->loadXML($http_response);
						
						if (!$xml_parseable) {
							// Die Antwort des Servers konnte nicht als XML geparsed werden
							// Dafür werden zwei Fälle angenommen:
							// 1. Das XML enthält Fehler, ist nicht wohlgeformt usw.
							// 2. Der Server ist überlastet o. Ä. gibt aber trotzdem keinen HTTP Fehlercode aus
							//    sondern nur eine Nachricht, die nicht als XML geparsed werden kann
							
							// Hier wird der 1. Fall bearbeitet und versucht das XML mit Tidy zu reparieren
							// 2. Fall weiter unten...
							$tidy = new tidy();
							$tidy_options = array("input-xml" => 1, "wrap" => 0);
							$http_response = $tidy->repairString($http_response, $tidy_options, 'utf8');
							
							// Neuer Parseversuch
							$xml_parseable = @$dom->loadXML($http_response);
						}
						
						if ($xml_parseable) {
						
							// ResumptionToken
							$resumptionToken_node = $dom->getElementsByTagName('resumptionToken');
							
							if ($resumptionToken_node->length == 0) {
								unset($resumptionToken);
							} else {
								$resumptionToken = $resumptionToken_node->item(0)->nodeValue;
								// Das resumptionToken-Element darf nicht leer sein
								if (strlen($resumptionToken) == 0) {
									unset($resumptionToken);
								}
							}
							
							// Gibt es in der Antwort der Quelle einen Errorcode? (oder mehrere...)
							$error_nodes = $dom->getElementsByTagName('error');
							
							if ($error_nodes->length > 0) {
								// Es gibt Fehler
								// Meist gibt es nur eine error-node, aber das Protokoll erlaubt mehrere
								// GDZ nutzt das z. B. 
								
								// Der Fall "noRecordsMatch" muss kein Fehler sein, sondern wird häufig auftreten
								// wenn es einfach keinen neuen Records gibt. Deshalb wird er hier
								// abgefangen. Der Harvester geht davon aus, dass es in diesem Fall
								// nur eine error-node gibt.
								
								if ($error_nodes->item(0)->getAttribute('code') == "noRecordsMatch") {
									// Die erste error-node enthält noRecordsmatch
									// alle weiterne werden ignoriert, aber eigentlich sollte es keine geben..
									// XML wird ja gespeichert, daher kann ein weiterer Fehler trotzdem
									// nachvollzogen werden.
									insert_log($oai_set['set_id'], 0, "Keine neuen Records gefunden.", $db_link, 0, 0, 0);
									$log_created = true;
									$norecordsmatch = true;
									
								} else {
									// Erste error-node enthält anderen Fehler
									// In diesem Fall wird es als Fehler gewertet und alle error-nodes
									// ausgewertet. Text ist nicht superausformuliert (Plural etc.)...
									
									$message = "Die Anfrage \"".$url."\" wurde von der OAI-Quelle mit folgendem Fehler(n) beantwortet:";
									
									foreach($error_nodes as $error_node) {
										// Meldungen werden einfach aneinander gereiht
										$message .= " Code \"".$error_node->getAttribute('code')."\"".
												( $error_node->nodeValue != "" ? " - \"".$error_node->nodeValue."\"" : "" );
									
									}
									
									$harvest_successful = false;
									$log_created = true;
									insert_log($oai_set['set_id'], 1, $message, $db_link, 0, 0, 0);
								}
							} else {
								// Es gibt keinen Error-Code
								// Records zählen
								$records = $dom->getElementsByTagName('record');
								
								if ($records->length == 0) {
									// Kein Error-Code aber keine Records darf eigentlich nicht 
									// passieren, aber es gibt alles... 
									$message = "Die Anfrage \"".$url."\" führte zu keinen Error-Code, beinhaltete aber keine Records. Harvesten abgebrochen.";
									
									insert_log($oai_set['set_id'], 1, $message, $db_link, 0, 0, 0);
									$harvest_successful = false;
									$log_created = true;
									unset($resumptionToken);	
								}
							}

						} else {
							// 2. Fall XML konnte nicht geparsed und nicht repariert werden
							// Serverfehler wird agenommen und weitere Versuch gestartet.
							// Maximale Fehlerzahl schon erreicht?
							if ($error_counter < ERROR_RETRY) {
								// Nein... Zähler hochsetzen
								$error_counter++;
								// Script pausieren
								set_time_limit(ERROR_RETRY_DELAY + 30);
								sleep(ERROR_RETRY_DELAY);
								// Neuer Versuch
								continue;
							} else {							
								// Maximale Fehlerzahl erreicht, Harvesten abbrechen
								$error_array = error_get_last();
								insert_log($oai_set['set_id'], 1, "Die Antwort auf die Anfrage \"".$url."\" konnte nicht geparsed werden:\n".$error_array['message'], $db_link, 0, 0, 0);
								$log_created = true;
								$harvest_successful = false;
								// Nach dem Speichern wird die Schleife damit abgebrochen
								unset($resumptionToken);
							}
						}
						
						// Speichern
						$filename = HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id']."/".$oai_set['set_id']."/oai_".$i.".xml";
			
						$file = fopen($filename, "w");
						fputs($file, $http_response);
						fclose($file);
						
						// Anfrage war erfolgreich, Fehlerzähler auf 0 setzen
						$error_counter = 0;
						
						if ($harvest_successful && isset($records)) {
							// Records zählen
							// $records = $dom->getElementsByTagName('record');
							$records_harvested += $records->length;	
							$i++;
							unset($records);
						}	
					} else {
						// Server nicht erreichbar
						// Maximale Fehlerzahl schon erreicht?
						if ($error_counter < ERROR_RETRY) {
							// Nein... Zähler hochsetzen
							$error_counter++;
							// Script pausieren
							set_time_limit(ERROR_RETRY_DELAY + 30);
							sleep(ERROR_RETRY_DELAY);
							// Neuer Versuch
							continue;
						} else {
							insert_log($oai_set['set_id'], 1, "Die Anfrage \"".$url."\" wurde mit folgendem Fehler abgebrochen: \"".curl_error($oai_harvester_ch)."\"", $db_link, 0, 0, 0);
							$log_created = true;
							$harvest_successful = false;
							break;
						}
					}
					
					sleep(LISTRECORDS_DELAY);

				// Zum Abbruch muss der Resumption Token leer sein	
				} while (isset($resumptionToken));
				
				// Muss vor dem nächsten Set reseted werden
				// Falls noch nicht geschehen (bei Fehlern, die die Schleife abbrechen)
				unset($resumptionToken);	
				
				if (!$log_created) {
					// Es wurde noch kein Logeintrag erzeugt.
					insert_log($oai_set['set_id'], 0, $records_harvested." Record".( $records_harvested > 1 ? "s" : "" )." geharvested.", $db_link, $records_harvested, 0, 0);
				}
				
				// oai_sets aktualisieren
				if ($harvest_successful) {
					$sql = "UPDATE oai_sets SET 
								harvest_status = 0 , 
								last_harvested = '".$oai_last_harvest."'   
							WHERE id = ".$oai_set['set_id'];	
				} else {
					// Beim Harvesten ist ein Fehler aufgetreten, harvest_status wird um 1 erhöht, last_harvested nicht aktualisert
					$sql = "UPDATE oai_sets SET 
								harvest_status = ".( $oai_set['harvest_status'] == -1 ? "1" : $oai_set['harvest_status'] + 1 )."  
							WHERE id = ".$oai_set['set_id'];
				}	
				
				// Alle geharvesteten Dateien werden in den Unterordner "error" bzw. "norecordsmatch"
				// verschoben und nicht indexiert.
				// if (($files_generated && !$harvest_successful) || $norecordsmatch) {
				if ((!$harvest_successful) || $norecordsmatch) {
					// Es sind Dateien erstellt worden, die jetzt verschoben werden müssen
					// Generell kommen sie in den Ordner error, mit der Ausnahme von 
					// "noRecordsMatch", dafür wird ein eigener Unterordner angelegt
					// um dies beim anschauen der Verzeichnisse kenntlich zu machen
					
					$move_folder = $norecordsmatch ? "norecordsmatch" : "error";
					
					// Errror-Verzeichnis erstellen
					mkdir($set_folder."/".$move_folder, CHMOD);					
					
					// Verzeichnis einlesen
					$harvest_directory = scandir($set_folder);
					
					// Alle xml-Dateien in Error-Ordner verschieben
					// Fehler beim Kopieren / Löschen abfangen?
					foreach($harvest_directory as $file_name) {
						// Ist die Datei eine XML-Datei?
						if (preg_match("/.xml/", $file_name)) {
							// In den Error-Ordner kopieren...
							copy($set_folder."/".$file_name, $set_folder."/".$move_folder."/".$file_name);
							// ...und im Quelleverzeichnis löschen (Gibt kein Verschieben bei PHP... x-(
							unlink($set_folder."/".$file_name);
						}
					}
				}
				
				// Besteht die Verbindung noch?
				$db_link = mysql_connection_check($db_link);
				
				$result = mysql_query($sql, $db_link);
				// mysql-Fehler abfangen
				if (!$result) { 
					// Fehlermeldung in Textdatei
					$filename = $set_folder."/mysql_error.txt";
					$file = fopen($filename, "w");
					
					$text = "";
					$text .= "Fehler bei der Datenbankabfrage:";
					$text .= "\"".mysql_error($db_link)."\"".chr(13).chr(10).chr(13).chr(10);
					$text .= "Die SQL-Abfrage war:".chr(13).chr(10);
					$text .= "\"".$sql."\"";
					
					fputs($file, $text);
					fclose($file);
				}			
			} // Verzeichnis erstellt, beendet praktisch die Set-Schleife
		} // Set-Schleife Ende
	}
	// oai_sources mit letzten Harvesten updaten
	// unabhängig ob ein Fehler aufgetreten ist oder nicht
	// TODO evtl. am nächsten Tag nochmal versuchen?
	// TODO !!! reindex wird auch bei eine fehlgeschlagenen Harvesten zurückgesetzt
	$sql = "UPDATE oai_sources SET 
				reindex = FALSE ,
				".( strlen($source_from) == 10 ? "harvested_since = '".$source_from."' , " : ""  )." 
				last_harvest = '".$oai_until."'  
			WHERE id = ".$oai_source['source_id'];
	
	// Besteht die Verbindung noch?
	$db_link = mysql_connection_check($db_link);
	
	$result = mysql_query($sql, $db_link);
	// mysql-Fehler abfangen
	if (!$result) { 
		// Fehlermeldung in Textdatei
		$filename = $set_folder."/mysql_error.txt";
		$file = fopen($filename, "w");
		
		$text = "";
		$text .= "Fehler bei der Datenbankabfrage:".chr(13).chr(10);
		$text .= "\"".mysql_error($db_link)."\"".chr(13).chr(10).chr(13).chr(10);
		$text .= "Die SQL-Abfrage war:".chr(13).chr(10);
		$text .= "\"".$sql."\"";
		
		fputs($file, $text);
		fclose($file);
	}
	
	// Quelle für den Indexer freigeben
	unlink(HARVEST_FOLDER."/".$current_date."/".$oai_source['source_id']."/HARVESTING");	
	
} // source Schleife geht hier zu

curl_close($oai_harvester_ch);

} // keine Quelle zum Harvesten "else" geht hier zu


} // mysql_query if geht hier zu


} // Ende if mkdir Verzeichnis Harvesttag
 
mysql_close($db_link);

} // DB-link if else geht hier zu

unlink(HARVEST_FOLDER."/HARVESTING.txt");
echo "Harvester Ende.";

} // Ende ELSE Fileexist HARVESTING
?>
