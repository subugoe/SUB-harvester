<?php

/*
 * Script, dass die geharvesteten OAI-XML-Dateien an den Solr-Index übergibt
 * Dazu werden die Daten vorher in das Solr-Format konvertiert.
 *
 * Beim Indexieren erzeugt dieses Script Logmeldungen.
 */

// Funktionen einbinden
require_once(dirname(__FILE__) . '/scripts_funcs.php');
// Einstellungen laden
readConfiguration();

// ---------------------------------------------------------------------------------


@$db_link = mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if (!$db_link) {

	// Wenn das Dateisystem hier auch Probleme macht, Pech...
	// Fehlermeldung wird in den Harvestordner geschrieben

	$filename = HARVEST_FOLDER."/mysql_error_indexer.txt";
	$file = fopen($filename, "w");
	fputs($file, "Kein Zugriff auf die Datenbank '".DB_SERVER."' möglich.\n Harvesten abgebrochen.");
	fclose($file);

} else {


// DB-Einstellungen
mysql_select_db(DB_NAME);
mysql_query("SET NAMES 'utf8'");
mysql_query("SET CHARACTER SET 'utf8'");


// wenn nötig, »error« Ordner anlegen
if (!file_exists(ERROR_FOLDER)) {
	mkdir(ERROR_FOLDER, CHMOD, TRUE);
}


// Http-Handler zum Senden der Daten an Solr initialisieren
$solr_ping = curl_init();

// Einstellungen setzen, Post-Feld wird für jede Datei gesetzt
curl_setopt($solr_ping, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($solr_ping, CURLOPT_URL, SOLR_PING);

$http_response = curl_exec($solr_ping);

// Hat der Indexer geantwortet?
if (curl_getinfo($solr_ping, CURLINFO_HTTP_CODE) != 200) {
	// Fehler, Indexieren abbrechen, Fehlerdatei in Error schreiben
	$filename = ERROR_FOLDER."/".date('Y-m-d')."T".date('H-i-s').'.txt';
	$file = fopen($filename, "w");
	fputs($file, "Der Solr-Index war nicht erreichbar.");
	fclose($file);
	curl_close($solr_ping);
} else {
// Index ist online
curl_close($solr_ping);

// Http-Handler zum Senden der Daten an Solr initialisieren
$oai_indexer_ch = curl_init();

// Einstellungen setzen, Post-Feld wird für jede Datei gesetzt
curl_setopt($oai_indexer_ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($oai_indexer_ch, CURLOPT_URL, SOLR_INDEXER);
curl_setopt($oai_indexer_ch, CURLOPT_POST, 1);
curl_setopt($oai_indexer_ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));

// Harvest-Verzeichnis einlesen
$day_folders = scandir(HARVEST_FOLDER);
//print_r($day_folders);

// wenn nötig, »temp« und »archive« Ordner anlegen
if (!file_exists(TEMP_FOLDER)) {
	mkdir(TEMP_FOLDER, CHMOD, TRUE);
}
if (!file_exists(ARCHIVE_FOLDER)) {
	mkdir(ARCHIVE_FOLDER, CHMOD, TRUE);
}

// Alle "Tagesverzeichnisse" durchgehen, z. B. "2011-01-01"
// Es wird nicht geprüft, ob das Verzeichnis einen Datumscode besitzt, es werden alle Verzeichnisse
// durchgegangen, unabhängig von ihrer Bezeichnung
foreach ($day_folders as $day_folder) {
	// Gleiches Verzeichnis im Temp-Ordner anlegen
	// Fehler abfangen TODO
	@mkdir(TEMP_FOLDER."/".$day_folder, CHMOD);

	// Nur Unterverzeichnisse, kein Sprung in höhere Ebenen, Harvester-Sperrdatei ignorieren
	if ($day_folder != "." && $day_folder != ".." && $day_folder != "HARVESTING.txt" && is_dir(HARVEST_FOLDER."/".$day_folder)) {
		$source_folders = scandir(HARVEST_FOLDER."/".$day_folder);
		//print_r($source_folders);


		// Zipdatei zum Archivieren von erfolgreichen Indexierungsvorgängen anlegen
		$archive = new ZipArchive();

		// Falls der Indexer schon Dateien dieses Tagesverzeichnisses bearbeitet hat
		// gibt es schon eine Zip-Datei.
		if(!file_exists(ARCHIVE_FOLDER."/".$day_folder.".zip")) {
			$archive->open(ARCHIVE_FOLDER."/".$day_folder.".zip", ZIPARCHIVE::CREATE);
			// Verzeichnisse anlegen
			$archive->addEmptyDir('harvest');
			$archive->addEmptyDir('index');

			// Zip-Kommentar setzten
			$archive->setArchiveComment('Created by EROMM-OAI-Harvester @ '.date('Y.m.d, H:i:s'));
			$archive->close();
		} else {
			// Datei gibt es schon, also einfach öffnen
			$archive->open(ARCHIVE_FOLDER."/".$day_folder.".zip");
		}

		// Quellenverzeichnisse z. B. "55"
		// Es wird geprüft, ob der Verzeichnisname nur aus Zahlen besteht
		// Enstpricht der Verzeichnisname nicht der ID einer OAI-Quelle löst dies später einen
		// Fehler aus, der das Indexieren abbricht
		foreach ($source_folders as $source_folder) {

			// Hier werden nur Verzeichnisse beachtet, die aus Zahlen bestehen = Source-Id
			// Zusätzlich wird geprüft, ob die Quelel gerade geharvested wird. In diesem Fall wird sie übersprungen.
			if (preg_match("/[0-9]+/", $source_folder) && !file_exists(HARVEST_FOLDER."/".$day_folder."/".$source_folder."/HARVESTING")) {
				$set_folders = scandir(HARVEST_FOLDER."/".$day_folder."/".$source_folder);
				//print_r($set_folders);

				// Gleiches Verzeichnis im Temp-Ordner anlegen
				@mkdir(TEMP_FOLDER."/".$day_folder."/".$source_folder, CHMOD);

				// Info-Datei kopieren
				copy(HARVEST_FOLDER."/".$day_folder."/".$source_folder."/source_data.txt", TEMP_FOLDER."/".$day_folder."/".$source_folder."/source_data.txt");

				// Speichert, ob mindestens ein Set der Quelle erfolgreich indexiert wurde
				$source_indexing_successful = false;


				// Quelleneinstellungen abfragen
				$sql = "SELECT
							view_creator,
						 	view_contributor,
						 	view_publisher,
						 	view_date,
						 	view_identifier,
						 	index_relation,
						 	index_creator,
						 	index_contributor,
						 	index_publisher,
						 	index_date,
						 	index_identifier,
						 	index_subject,
						 	index_description,
						 	index_source,
						 	dc_date_postproc,
						 	identifier_filter,
						 	identifier_resolver,
						 	identifier_resolver_filter,
						 	identifier_alternative,
						 	country_code
						 FROM oai_sources
						 WHERE id = ".$source_folder;

				$result = mysql_query($sql, $db_link);

				if (!$result) {
					// Datenbankabfrage fehlgeschlagen
					// Fehlermeldung in Textdatei.
					$filename = TEMP_FOLDER."/".$day_folder."/".$source_folder."/mysql_error.txt";
					$file = fopen($filename, "w");

					$text = "";
					$text .= "Fehler bei der Datenbankabfrage:\n\n";
					$text .= "\"".mysql_error($db_link)."\"\n\n";
					$text .= "Die SQL-Abfrage war:\n\n";
					$text .= "\"".$sql."\"";

					fputs($file, $text);
					fclose($file);

				} else {
					// Datenbankabfrage erfolgreich
					$source_settings = mysql_fetch_array($result, MYSQL_ASSOC);

					// Parameter für XSLT setzen
					// timesstamp wird vor jeder Konversion gesetzt
					$xsl_parameters = array(
					    'timestamp' 			=> "",
					    'country_code' 			=> $source_settings['country_code'],
						'oai_repository_id'		=> $source_folder,
						'oai_set_id'			=> "",
						'i_cre' 				=> $source_settings['index_creator'],
						'i_con' 				=> $source_settings['index_contributor'],
						'i_pub' 				=> $source_settings['index_publisher'],
						'i_dat' 				=> $source_settings['index_date'],
						'i_ide' 				=> $source_settings['index_identifier'],
						'i_rel' 				=> $source_settings['index_relation'],
						'i_sub' 				=> $source_settings['index_subject'],
						'i_des' 				=> $source_settings['index_description'],
						'i_sou' 				=> $source_settings['index_source'],
						'v_cre' 				=> $source_settings['view_creator'],
						'v_con' 				=> $source_settings['view_contributor'],
						'v_pub' 				=> $source_settings['view_publisher'],
						'v_dat' 				=> $source_settings['view_date'],
						'v_ide' 				=> $source_settings['view_identifier']
					);

					// Setverzeichnisse z. B. "5497524"
					// Gleich Prüfung wie bei den Quellenverzeichnissen.
					foreach ($set_folders as $set_folder) {
						// Hier werden nur Verzeichnisse beachtet, die aus Zahlen bestehen = Set-Id
						if (preg_match("/[0-9]+/", $set_folder)) {

							$full_set_folder_path_harvest = HARVEST_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder."/";

							// Zähler für die Dateinamen
							$i = 0;

							// $oai_filename bietet jetzt Zugriff auf alle zu indexierende OAI-XML-Datei
							$oai_filename = $full_set_folder_path_harvest."oai_".$i.".xml";

							// Gibt es die erste Datei? Falls beim Harvesten Fehler aufgetreten sind, gibt es
							// zwar den Ordner, aber die Daiteien liegen im Unterordner error
							if (file_exists($oai_filename)) {

								// Gleiches Verzeichnis im Temp-Ordner anlegen
								@mkdir(TEMP_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder, CHMOD);

								// set_id setzen
								$xsl_parameters['oai_set_id'] = $set_folder;

								// Zur Übersichtlichkeit
								$full_set_folder_path_harvest = HARVEST_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder."/";
								$full_set_folder_path_temp = TEMP_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder."/";

								// Info-Datei kopieren
								copy($full_set_folder_path_harvest."set_data.txt", $full_set_folder_path_temp."/set_data.txt");

								// Verzeichnis für die Solr-Antworten erstellen
								@mkdir($full_set_folder_path_temp."solr_msgs", CHMOD);

								// Zähler für Logeinträge
								$counter_add = 0;
								$counter_delete = 0;

								// Speichert, ob solr beim Indexieren der Datei einen Fehler meldet.
								$set_indexing_successful = true;

								// DOM-Element erzeugen
								$dom = new DOMDocument();

								while (file_exists($oai_filename)) {

									// Dateien bearbeiten
									if (@$dom->load($oai_filename)) {

										// Alle records ermitteln
										$record_nodes = $dom->getElementsByTagName('record');

										if ($record_nodes->length < 1) {
											// Es gibt keine Records
											// Dieser Fall sollte gemäß Protokoll nicht eintreten, da es dafür einen
											// Errorcode gibt... aber man weiß ja nie...

											// Log generieren
											insert_log($set_folder, 1, "Die Datei \"".$oai_filename."\" enthält keine Records. Das Harvesten des Sets wird abgebrochen.", $db_link, 0, 0, 1);

											// Das Indexieren dieses Sets wird abgebrochen
											$set_indexing_successful = false;
											break;

										} else {
											// Das Skript könnte länger als 60 Sekunden laufen.
											// Die Laufzweit wird nach jeder Datei auf 60 Sekunden gesetzt.
											set_time_limit(60);

											// Wenn keine oai_dc-Elemente gefunden werden, wird es auf false gesetzt
											// und das Indexieren abgebrochen
											// 2012-10, ssp: Diese Variable wird nicht wirklich benötigt, entfernen wenn oai_dc Handhabung ausgegliedert ist.
											$no_error = true;

											// Records durchgehen, oai_url Element mit der URL hinzufügen
											foreach($record_nodes as $record_node) {

												// oai_dc:dc-Element ermitteln
												// $oai_dc_node = $record_node->getElementsByTagNameNS('http://www.openarchives.org/OAI/2.0/oai_dc/', 'dc');
												$metadata_nodes = $record_node->getElementsByTagName('metadata');

												/* Handelt es sich beim record um das Anzeigen einer gelöschten Resource, gibt es kein oai_dc:dc-Element
												 * und ein Link kann nicht ermittelt werden
												 * Zusätztlich wird geprüft, ob das Header-Elemente ein Attribut "status='deleted' enthält.
												 * Diese doppelte Prüfung ist robuster. */
												if ($metadata_nodes->length > 0) {
													$metadata_node = $metadata_nodes->item(0);
													$oai_dc_node = $metadata_node->getElementsByTagNameNS('http://www.openarchives.org/OAI/2.0/oai_dc/', 'dc');
													// Bei OAI-Daten eigene Verbesserungen / Ergänzungen einfügen.
													if ($oai_dc_node->length > 0) {
														// Alle dc:identifier-Element ermitteln und ...
														$dc_identifier_nodes = $record_node->getElementsByTagNameNS('http://purl.org/dc/elements/1.1/', 'identifier');

														// Prüfen, ob Elemente gefunden wurden
														if ($dc_identifier_nodes->length) {
															// Filter anwenden
															$link_found = false;
															foreach($dc_identifier_nodes as $dc_identifier) {

																// Falscher Regulärer Ausdruck... abfangen? TODO?
																if (preg_match($source_settings['identifier_filter'], $dc_identifier->nodeValue)) {

																	// Passendes Element gefunden
																	$link = $dc_identifier->nodeValue;
																	$link_found = true;

																	if ($source_settings['identifier_resolver'] != "") {
																		if ($source_settings['identifier_resolver_filter'] != "") {

																			// Resolver-Filter ist gesetzt
																			// Resolver nur hinzufügen, wenn der Filter erfolgreich ist
																			if (preg_match($source_settings['identifier_resolver_filter'], $link)) {
																				$link = $source_settings['identifier_resolver'].$link;
																			}

																		} else {
																			// Kein Resolver-Filter gesetzt, Resolver einfach hinzufügen
																			$link = $source_settings['identifier_resolver'].$link;
																		}
																	}

																	// Neues dc_rights-Element erzeugen
																	$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
																	// Wert auf den link setzten
																	@$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
																	// und einhängen
																	$oai_dc_node->item(0)->appendChild($node);
																}
															}

															if (!$link_found) {
																// Aus keinem dc:identifier-Element konnte eine URL gebildet werden
																// default setzen
																$link = $source_settings['identifier_alternative'];
																// Neues dc_rights-Element erzeugen
																$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
																// Wert auf den link setzten
																$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
																// und einhängen
																$oai_dc_node->item(0)->appendChild($node);
															}
														} else {
															// Es gibt kein(e) dc:identifier-Element(e), default setzen
															$link = $source_settings['identifier_alternative'];
															// Neues dc_rights-Element erzeugen
															$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
															// Wert auf den link setzten
															$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
															// und einhängen
															$oai_dc_node->item(0)->appendChild($node);
														}

														// dc:date Bearbeitung
														// alle dc:date-Elemente ermitteln
														$dc_date_nodes = $record_node->getElementsByTagNameNS('http://purl.org/dc/elements/1.1/', 'date');

														// Prüfen, ob Elemente gefunden wurden
														if ($dc_date_nodes->length) {

															//$regex_with_valid prüft ist strenger, erlaubt keinen Tag und Monat 00 (ging aber für archive.org nicht..)
															//$regex_with_valid = '/^(?P<year>[\+-]?\d{4}(?!\d{2}\b))((-?)((?P<month>0[1-9]|1[0-2])(\3(?P<day>[12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';
															$regex_dc_date = '/^(?P<year>[\+-]?\d{1,4}(?!\d{2}\b))((-?)((?P<month>0[0-9]|1[0-2])(\3(?P<day>[12]\d|0[0-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';

															if ($dc_date_nodes->length > 1) {

																// Es gibt mind. 2 Elemente, das älteste, bzw. das erste mit Text ermitteln
																// Zum Speicher der Zeitangaben
																$dc_dates_array = array();

																// Alle dc_date Elemente durchgehen
																foreach($dc_date_nodes as $dc_date) {
																	// Alle dc_date Elemente durchgehen
																	$current_dc_date = '';

																	// Alle dc:date prüfen
																	if (preg_match($regex_dc_date, $dc_date->nodeValue, $matches)) {
																		// Wert ist eine codierte Zeitangabe, möglichst auf Tag genau
																		// herausfiltern und speichern

																		// "year" muss es geben, sonst schlägt der reguläre Ausdruck fehl
																		$current_dc_date = $matches['year'];

																		// Monat soweit vorhanden
																		if (array_key_exists('month', $matches) && strlen($matches['month']) > 0) {
																			$current_dc_date .= '-'.$matches['month'];

																			// Tag soweit vorhanden (nur wenn Monat vorhanden natürlich)
																			if (array_key_exists('day', $matches) && strlen($matches['day']) > 0) {
																				$current_dc_date .= '-'.$matches['day'];
																			}
																		}
																		$dc_dates_array[] = $current_dc_date;

																	} else {
																		// regulärer Ausdruck schlägt fehl, damit ist ein Vergleich aller Werte
																		// nicht möglich und der auslösende Wert wird genommen
																		// (In der Annahme dass es sich um eine Angabe mit sinvollem Text handelt)

																		// Array mit dc:dates löschen, um sicher zu stellen, dass nur eine Zeitangabe vorhanden ist.
																		unset($dc_dates_array);

																		// dc:date in [0] speichern, damit es einfach weiterverarbeitet werden kann.
																		$dc_dates_array = array();
																		$dc_dates_array[0] = $dc_date->nodeValue;
																		break;
																	}
																}

																// dc:date sortieren
																sort($dc_dates_array);

															} else {
																// nur ein dc:date-Element vorhanden
																$dc_dates_array[0] = $dc_date_nodes->item(0)->nodeValue;
															}

															$processed_dc_date = '';

															// Ist die Formatierung aktiviert?
															if ($source_settings['dc_date_postproc'] > 0) {

																// Wert filtern (leider nochmal, ist aber recht komplex anders)
																if (preg_match($regex_dc_date, $dc_dates_array[0], $matches)) {

																	// year muss es geben, sonst schlägt der reguläre Ausdruck nicht an
																	$processed_dc_date = $matches['year'];

																	// month wenn konfiguriert und vorhanden
																	if ($source_settings['dc_date_postproc'] > 1 && array_key_exists('month', $matches) && strlen($matches['month']) > 0) {
																		$processed_dc_date .= '-'.$matches['month'];

																		// day wenn konfiguriert und vorhanden
																		if ($source_settings['dc_date_postproc'] > 2 && array_key_exists('day', $matches) && strlen($matches['day']) > 0) {
																			$processed_dc_date .= '-'.$matches['day'];
																		}
																	}
																} else {
																	// Der Wert kann nicht formatiert werden und wird so übernommen
																	$processed_dc_date = $dc_dates_array[0];
																}
															} else {
																// Keine Formatierung
																$processed_dc_date = $dc_dates_array[0];
															}

															// Neues Element erzeugen mit dem verarbeiteten Wert einfügen
															// Erzeugen...
															$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:date');
															// ...Wert setzen...
															@$node->nodeValue = htmlspecialchars($processed_dc_date, ENT_QUOTES, "UTF-8", false);
															// und einhängen
															$oai_dc_node->item(0)->appendChild($node);


														} // $dc_date_nodes->length => Dann passiert gar nichts

													}
												}
												else {
													/*
													2012-10-19, ssp: auskommentiert
													Tut nichts, wenn das else unten nicht existiert

													// Prüfen ob es eine Update-Meldung gibt
													$header_node = $record_node->getElementsByTagName('header');

													if ($header_node->item(0)->getAttribute('status') == "deleted") {
														// Ja
														continue;
													}
													*/

													/*
													2012-10-19, ssp: auskommentiert, um auch nicht-DC Formate handhaben zu können.
														Alles oai_dc-spezifische sollte für eine saubere Lösung ausgegliedert werden.

													else {
														// Nein
														// Kann nur passieren, wenn der Namespace falsch benannt ist
														// Sollte eigentlich nicht vorkommen, aber in Praxis leider schon gesehen (SBB)
														// Damit sollte das Indexieren dieser Quelle abgebrochen werden
														$no_error = false;
														break;
													}
													*/
												}
											}

											if ($no_error) {

												// XSL importieren
												$xsl_xml = new DOMDocument();
												$xsl_xml->load(dirname(__FILE__) . '/../xsl/' . XSL_FILENAME);

												$xsl = new XSLTProcessor();
												$xsl->importStylesheet($xsl_xml);

												// timestamp setzen
												$xsl_parameters['timestamp'] = date('Y.m.d, H:i:s');

												$xsl->setParameter('', $xsl_parameters);

												// Modifizierten DOM speichern und erneut parsen
												$dom->loadXML($dom->saveXML());

												// XSLT anwenden
												$solr_add_string = $xsl->transformToXML($dom);

												if ($solr_add_string) {

													// Entfernen von "doppelten" Deskriptionszeichen und "verkorksten" Auslassungspunkten
													// Nicht schön, diese Dinge hier zu fixen, aber es ist in XSTL zu komplex und zeitaufwendig zu programmieren
													// Ggf. um weitere Ersetzungen ergänzen
													$search  = array('.. — ', ',, ', '....', '.....', '......', ' ...');
													$replace = array('. — ',  ', ',  '...',  '...',   '...',    '...') ;

													$solr_add_string = str_replace($search, $replace, $solr_add_string);

													// Ergebnis Speichern

													$solr_filename = $full_set_folder_path_temp."solr_".$i.".xml";
													file_put_contents($solr_filename, $solr_add_string);

													curl_setopt($oai_indexer_ch, CURLOPT_POSTFIELDS, $solr_add_string);
													$http_response = curl_exec($oai_indexer_ch);

													// Gibt es Fehlermeldungen vom Solr-Index?
													if (curl_getinfo($oai_indexer_ch, CURLINFO_HTTP_CODE) == 200) {
														// Datei wurde erfolgreich indexiert
														// Dann gibt es XML zurück, das in einer Datei gespeichert wird
														// Enthält nur einen Statuscode und die Indexzeit... nicht was für die
														// Logmeldung interessant wäre.
														$solr_msg = $full_set_folder_path_temp."/solr_msgs/solr_msg_".$i.".xml";
														$file = fopen($solr_msg, "w");
														fputs($file, $http_response);
														fclose($file);

														$dom->loadXML($solr_add_string);

														$add_nodes = $dom->getElementsByTagName('doc');
														$delete_nodes = $dom->getElementsByTagName('id');

														$counter_add += $add_nodes->length;
														$counter_delete += $delete_nodes->length;

													} else {
														// Datei wurde nicht erfolgreich indexiert
														// Solr liefert dann bereits einen Fehler-HTTP-Code und als Antwort
														// eine HTML seite, die auch gespeichert wird.
														$solr_msg = $full_set_folder_path_temp."/solr_msgs/solr_msg_".$i."_error.html";
														$file = fopen($solr_msg, "w");
														fputs($file, $http_response);
														fclose($file);

														// Log generieren
														insert_log($set_folder, 1, "Fehler beim Indexieren von \"".$solr_filename."\".\n HTTP-Fehlercode: ".curl_getinfo($oai_indexer_ch, CURLINFO_HTTP_CODE)."\nDie genaue Fehlermeldung befindet sich in der Datei ".$solr_msg.".", $db_link, 0, 0, 1);

														// Das Indexieren dieses Sets abbrechen
														$set_indexing_successful = false;
														break;
													}

												} else {
													// OAI-XML konnte nicht konvertiert werden
													$error_array = error_get_last();

													// Log generieren
													insert_log($set_folder, 1, "Die Datei \"".$oai_filename."\" konnte nicht konvertiert werden:\n".$error_array['message'], $db_link, 0, 0, 0);

													// Das Indexieren dieses Sets abbrechen
													$set_indexing_successful = false;
													break;
												}

												// Nächste Datei
												$i++;
												$oai_filename = $full_set_folder_path_harvest."oai_".$i.".xml";

											} else {
												// oai_dc == false => Die Daten sind nicht zu gebrauchen
												// Indexieren des Sets wird abgebrochen
												$set_indexing_successful = false;
												insert_log($set_folder, 1, "Keine oai_dc-Elemente gefunden.", $db_link, 0, 0, 1);
												break;
											}
										} // else geht zu, Datei enthält records

									// ende if load($file)
									} else {
										// die OAI-Datei kann nicht geparsted werden
										$error_array = error_get_last();

										// Log generieren
										insert_log($set_folder, 1, "Die Datei \"".$oai_filename."\" konnte nicht geparsed werden:\n".$error_array['message'], $db_link, 0, 0, 1);

										// Das Indexieren dieses Sets abbrechen
										$file_indexing_successful = false;
										break;
									}
								} // Ende while-Schleife (file_exists($oai_filename))


								// War das Indexieren des Sets erfolgreich?
								if ($set_indexing_successful) {
									// Das Indexieren des Sets war erfolgreich
									// Logmeldung generieren,
									$message = "";
									$message .= $counter_add > 0 ? $counter_add." Record".( $counter_add == 1 ? "" : "s" )." indexiert." : "";
									$message .= $counter_delete > 0 ? " ".$counter_delete." Records wurden aus dem Index entfernt." : "";
									insert_log($set_folder, 0, $message, $db_link, $counter_add, $counter_delete, 1);

									$sql = "UPDATE oai_sets SET
												index_status = 0 ,
												last_indexed = NOW()
											WHERE id = ".$set_folder;

									// Daten bleiben im Index
									$post = file_get_contents(dirname(__FILE__) . '/../templates/commit.xml');

									// Damit wurde ein Set der Quelle erfolgreich indexiert
									$source_indexing_successful = true;


								} else {
									// Das Indexieren des Sets war nicht erfolgreich
									// Eine Logmeldung wurde in diesem Fall bereits generiert

									// aktuellen Indexstatus ermitteln
									$sql = "SELECT index_status
											FROM oai_sets
											WHERE id = ".$set_folder;

									// Fehler abfangen? TODO
									$result = mysql_query($sql, $db_link);

									$index_status = mysql_result($result, 0);

									$sql = "UPDATE oai_sets SET
												index_status = ".( $index_status == -1 ? "1" : $index_status + 1 )."
											WHERE id = ".$set_folder;

									// TODO Verzeichnisse müssen noch ersetzt werden!!!!!
									$post = file_get_contents(dirname(__FILE__) . '/../templates/rollback.xml');

									// Da bei der Indexierung ein Fehler aufgetreten ist, werden die geharvesteten Dateien
									// und die bisher konvertierten Dateien in den Fehlerordner verschoben.

									// source_data.txt kopieren, falls sie noch nicht existiert
									if (!file_exists(ERROR_FOLDER."/".$day_folder."/indexing/".$source_folder."/source_data.txt")) {
										@mkdir(ERROR_FOLDER."/".$day_folder."/indexing/".$source_folder, CHMOD, true);
										copy(HARVEST_FOLDER."/".$day_folder."/".$source_folder."/source_data.txt", ERROR_FOLDER."/".$day_folder."/indexing/".$source_folder."/source_data.txt");
									}
									// Setverzeichnisse ins Error-Verzeichnis verschieben
									move_folder(TEMP_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder, ERROR_FOLDER."/".$day_folder."/indexing/".$source_folder."/".$set_folder."/solr");
									move_folder(HARVEST_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder, ERROR_FOLDER."/".$day_folder."/indexing/".$source_folder."/".$set_folder."/oai");
								}

								// Fehler abfangen? TODO
								mysql_query($sql, $db_link);

								curl_setopt($oai_indexer_ch, CURLOPT_POSTFIELDS, $post);

								// Zur Sicherheit, falls der Commit länger dauert.
								set_time_limit(600);
								$http_response = curl_exec($oai_indexer_ch);

								// DOM-Instanz löschen
								unset($dom);

							//  Ende if(file_exists($oai_filename))
							} else {
								// Es gibt kein(e) oai_$i.xml
								// => Beim Harvesten gab es Probleme
								// Set-Ordner aus dem Harvestordner in den Error-Ordner "harvesting" des $day_folder verschieben
								// Keine Logmeldung in diesem Fall - wurde beim Harvesten erstellt.

								// source_data.txt kopieren, falls sie noch nicht existiert
								if (!file_exists(ERROR_FOLDER."/".$day_folder."/harvesting/".$source_folder."/source_data.txt")) {
									@mkdir(ERROR_FOLDER."/".$day_folder."/harvesting/".$source_folder, CHMOD, true);
									copy(HARVEST_FOLDER."/".$day_folder."/".$source_folder."/source_data.txt", ERROR_FOLDER."/".$day_folder."/harvesting/".$source_folder."/source_data.txt");
								}
								// Setverzeichnis ins Error-Verzeichnis verschieben
								move_folder(HARVEST_FOLDER."/".$day_folder."/".$source_folder."/".$set_folder, ERROR_FOLDER."/".$day_folder."/harvesting/".$source_folder."/".$set_folder);
							}
						} // Ende pregmatch für set_folders
					} // Ende set_folders Schleife
				} // Ende Else Datenbankabfrage Quelle (erfolgreich)


				if($source_indexing_successful) {
					// Mindestens ein Set der Quelle wurde erfolgreich indexiert
					// Die Fehlerhaften Sets wurden bereits nach "error" verschoben
					// daher kann das komplette (übrig gebliebene) Quellenverzeichnis
					// archiviert werden.

					$archive->open(ARCHIVE_FOLDER."/".$day_folder.".zip");

					$archive->addEmptyDir('harvest/'.$source_folder);
					$archive->addEmptyDir('index/'.$source_folder);

					add_to_archive($archive, 'harvest/'.$source_folder, HARVEST_FOLDER."/".$day_folder."/".$source_folder);
					add_to_archive($archive, 'index/'.$source_folder, TEMP_FOLDER."/".$day_folder."/".$source_folder);

					// Zip-Archiv schließen und damit vor dem Löschen speichern.
					$archive->close();

					remove_folder(HARVEST_FOLDER."/".$day_folder."/".$source_folder);
					remove_folder(TEMP_FOLDER."/".$day_folder."/".$source_folder);

				} else {
					// Kein Set der Quelle wurde erfolgreich indexiert
					// die Sets wurden bereits verschoben, so dass der Quellenordner noch übrig ist
					// (mit source_data.txt) und gelöscht werden kann (Harvest und Temp Verzeichnis)
					remove_folder(HARVEST_FOLDER."/".$day_folder."/".$source_folder);
					remove_folder(TEMP_FOLDER."/".$day_folder."/".$source_folder);
				}

			} // Ende pregmatch folder if

			// Falls kein Harvestvorgang für den Tag vorgesehen war wird eine Textdatei angelegt
			// die ebenfalls in das Zip-Archiv verschoben wird
			if (file_exists(HARVEST_FOLDER."/".$day_folder."/no_harvest_scheduled.txt")) {
				$archive->open(ARCHIVE_FOLDER."/".$day_folder.".zip");
				add_to_archive($archive, 'harvest', HARVEST_FOLDER."/".$day_folder);
				$archive->close();
				unlink(HARVEST_FOLDER."/".$day_folder."/no_harvest_scheduled.txt");
			}
		} // Ende source_folders Schleife

		// $day_folders löschen
		// Diese Verzeichnisse müssten jetzt leer sein, deshalb löschen nur mit
		// nur rmdir, damit sie sonst erhalten bleiben
		@rmdir(HARVEST_FOLDER."/".$day_folder);
		@rmdir(TEMP_FOLDER."/".$day_folder);
	}
} // Ende day_folders Schleife


// Nachträgliches Löschen - eingeführt mit archive.org
// Sollte eigentlich nicht lange dauern, aber zur Sicherheit
set_time_limit(600);
// Alle Löschanfragen sind in der Datei postproc.xml,
// dort können auch weitere Korrekturen eingfügt werden.
$post = file_get_contents(dirname(__FILE__) . '/../templates/postproc.xml');
curl_setopt($oai_indexer_ch, CURLOPT_POSTFIELDS, $post);
$http_response = curl_exec($oai_indexer_ch);



// Index optimieren
// Zur Sicherheit, falls optimize bei großen Commits länger dauert.
set_time_limit(1200);
// Fehler abfangen? TODO
$post = file_get_contents(dirname(__FILE__) . '/../templates/optimize.xml');
curl_setopt($oai_indexer_ch, CURLOPT_POSTFIELDS, $post);
$http_response = curl_exec($oai_indexer_ch);
// Doppelt, sonst gibt Solr den Speicher nicht frei (warum auch immer...)
$http_response = curl_exec($oai_indexer_ch);
curl_close($oai_indexer_ch);

// Dateigröße des Indexes speichern
//require_once("../../statistics/statistics_funcs.php");
//save_index_filesize(SOLR_INDEX_FOLDER, getcwd()."/../../data/index_filesize.txt");


} // Ende else "Indexer antwortet"

} // Ende else "Datenbankverbindung hergestellt"

echo "Indexer Ende.\n";

?>
