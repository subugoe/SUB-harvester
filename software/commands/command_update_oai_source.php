<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zum Aktualiseren der Einstellungen einer OAI Quelle.
 */
class command_updateOAISource extends command {

	public function appendContent () {
		// Funktionen einbinden
		require_once(dirname(__FILE__) . '/update_oai_source_funcs.php');

		// Datensatz freigeben, dabei wird auch der Token verglichen, für den Fall, dass er schon abgelaufen ist
		// TODO? Richtig wäre, den Datensatz erst am Ende freizugeben, aber es ist so unwahrscheinlich, dass ihn zwischenzeitlich (in dem Bruchteil der Sekunde) jemand öffnet
		$editTokenQuoted = "'" . mysql_real_escape_string($this->parameters['edit_token']) . "'";

		$sql = "DELETE FROM oai_source_edit_sessions
				WHERE oai_source = " . intval($this->parameters['edit_id']) . "
				AND MD5(timestamp) = " . $editTokenQuoted;
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}
		if ($this->parameters['edit_abort']) {
			// Abbruch -> Auf Startseite umleiten
			$this->headElement->appendChild($this->makeRedirect());
		} else {
			// Kopf
			$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zur Startseite', 'gotoStart()'));

			$this->contentElement->appendChild($this->makeElementWithText('h2', 'OAI-Quelle bearbeiten'));

			// Prüfen, ob der Datensatz noch reserviert war
			if (mysql_affected_rows($this->db_link) == 1) {
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
						FROM oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source
						WHERE oai_sources.id = " . intval($this->parameters['edit_id']) ." AND oai_sets.setSpec = 'allSets'";
				$result = mysql_query($sql, $this->db_link);
				if (!$result) {
					$error = new error($this->document);
					$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
					return;
				}
				$source_data_db = mysql_fetch_array($result, MYSQL_ASSOC);

				// Ist die Quelle schon zum Neuindexierung markiert? Dann ist die Prüfung nicht notwendig und die Reindexieurng bleibt erhalten
				if ($source_data_db['reindex']) {
					$reindex = 1;
				} else {
					// Prüfung ist notwendig
					$reindex = 0;
					foreach($source_data_db as $key => $value) {
						// Bugfixing
						// $this->contentElement->appendChild($this->makeElementWithText('p', 'Key: ' . $key . ' - Value: ' . $value));

						// Prüfung nur für relevante Felder
						if ($key != "reindex" && $key != "allSets_harvest" && $key != "from" && $key != "last_harvest" && $key != 'harvested_since') {

							// Der eigentliche Test
							if($key == "identifier_filter" || $key == "identifier_resolver" || $key == "identifier_alternative" || $key == "identifier_resolver_filter" || $key == "country" || $key == "dc_date_postproc") {
								// Stringvergleich bei diesen Werten
								if($this->parameters[$key] != $value) {
									$reindex = 1;
									break; // Prüfung beenden
								}

							} else {
								// Bool-Vergleich
								if((isset($this->parameters[$key]) ? 1 : 0) != $value ) {
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
				if ((strlen($this->parameters['from']) == 10 && strlen($this->parameters['current_from_db']) == 10 && $this->parameters['from'] > $this->parameters['current_from_db']) || (strlen($this->parameters['from']) == 10 && strlen($this->parameters['current_from_db']) != 10)) {
					// Das neue from-Datum ist neuer als das alte, bzw. es gibt keine altes
					// => Es müssen Indexeinträge bis zum Tag vor dem from-Datum entfernt werden

					$delete_count = delete_source_dateRange($this->parameters['edit_id'], $this->parameters['new_from_day_before']);

				} else if ((strlen($this->parameters['from']) == 10 && strlen($this->parameters['current_from_db']) == 10 && $this->parameters['from'] < $this->parameters['current_from_db'])) {
					// Ist ein altes und eine neues from-Datum gesetzt und
					// Liegt das neue "from" Datum vor dem alten, müssen Daten nachgeharvested werden
					// Korrekt wäre mit "from" und "until" zu harvesten - da dies der aber nicht vorgesehen ist
					// wird einfach das "last_harvested" entsprechend zurückgesetzt,
					// was das Problem löst, wenn auch dabei "zu viel" harvested.
					$new_last_harvest = $this->parameters['from'];

				} else if (strlen($this->parameters['from']) == 0 && array_key_exists('current_from_db', $this->parameters) && strlen($this->parameters['current_from_db']) == 10) {
					// Es gibt ein altes from-Datum. Dies wurde beim Editieren gelöscht
					// Der Nutzer wurde darauf hingewiesen, dass durch diese Konstellation die Quelle
					// komplett neu geharvested wird und hat dies bestätigt.
					$new_last_harvest = 'NULL';
				}

				// Veränderungen speichern
				// "Allgemeine Einstellungen"
				$sql = "UPDATE oai_sources SET
							name = '" . mysql_real_escape_string(stripslashes($this->parameters['name'])) . "',
							" .
							/*
							view_creator = " . (isset($this->parameters['view_creator']) ? 1 : 0) . ",
							view_contributor = " . (isset($this->parameters['view_contributor']) ? 1 : 0) . ",
							view_publisher = " . (isset($this->parameters['view_publisher']) ? 1 : 0) . ",
							view_date = " . (isset($this->parameters['view_date']) ? 1 : 0) . ",
							view_identifier = " . (isset($this->parameters['view_identifier']) ? 1 : 0) . ",
							index_relation = " . (isset($this->parameters['index_relation']) ? 1 : 0) . ",
							index_creator = " . (isset($this->parameters['index_creator']) ? 1 : 0) . ",
							index_contributor = " . (isset($this->parameters['index_contributor']) ? 1 : 0) . ",
							index_publisher = " . (isset($this->parameters['index_publisher']) ? 1 : 0) . ",
							index_date = " . (isset($this->parameters['index_date']) ? 1 : 0) . ",
							index_identifier = " . (isset($this->parameters['index_identifier']) ? 1 : 0) . ",
							index_subject = " . (isset($this->parameters['index_subject']) ? 1 : 0) . ",
							index_description = " . (isset($this->parameters['index_description']) ? 1 : 0) . ",
							index_source = " . (isset($this->parameters['index_source']) ? 1 : 0) . ",
							dc_date_postproc = " . mysql_real_escape_string($this->parameters['dc_date_postproc']) . ",
							*/
							"identifier_filter = '" . mysql_real_escape_string($this->parameters['identifier_filter']) . "',
							identifier_resolver = '" . mysql_real_escape_string($this->parameters['identifier_resolver']) . "',
							identifier_resolver_filter = '" . mysql_real_escape_string($this->parameters['identifier_resolver_filter']) . "',
							identifier_alternative = '" . mysql_real_escape_string($this->parameters['identifier_alternative']) . "',
							country_code = '" . mysql_real_escape_string($this->parameters['country']) . "',
							active = " . (isset($this->parameters['active']) ? 1 : 0) . ",
							`from` = " . (strlen($this->parameters['from']) != 10 ? 'NULL' : "'". mysql_real_escape_string($this->parameters['from']) . "'") . ",
							harvested_since = " . (strlen($this->parameters['from']) == 10 && !is_null($source_data_db['harvested_since']) && ($source_data_db['harvested_since'] < $this->parameters['from']) ? "'" . mysql_real_escape_string($this->parameters['from']) . "'" : !is_null($source_data_db['harvested_since']) ? "'" . $source_data_db['harvested_since'] . "'" : "NULL" )." ,
							last_harvest = " . ($new_last_harvest == "NULL" ? "NULL" : "'" . mysql_real_escape_string($new_last_harvest) . "'") . ",
							harvest_period = " . intval($this->parameters['harvest_period']) . " ,
							reindex = " . $reindex . " ,
							comment = '" . mysql_real_escape_string($this->parameters['comment']) . "'
							WHERE id = " . intval($this->parameters['edit_id']);
				$result = mysql_query($sql, $this->db_link);
				if (!$result) {
					$error = new error($this->document);
					$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
					return;
				}

				// Bugfixing
				// $this->contentElement->appendChild($this->makeElementWithText('p', $sql));

				// Sets
				if (count($this->parameters['sets']['unchanged']) > 0) {
					// Diese Sets sind bereits in der Datenbank
					foreach($this->parameters['sets']['unchanged'] AS $set) {
						$sql = "UPDATE oai_sets SET
								online = 1,
								harvest = " . (isset($set['harvest']) ? 1 : 0) . "
								WHERE id = " . intval($set['id']);
						$result = mysql_query($sql, $this->db_link);
						if (!$result) {
							$error = new error($this->document);
							$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
							return;
						}
					}
				}

				if (array_key_exists('new', $this->parameters['sets']) && count($this->parameters['sets']['new']) > 0) {
					// Neu hinzugekommene Sets
					$sql = "INSERT INTO oai_sets (
						id,
						oai_source,
						setSpec,
						setName,
						online,
						harvest,
						harvest_status,
						index_status
						)
						VALUES ";

					foreach($this->parameters['sets']['new'] as $set) {
						$sql .= "(NULL, "
								. intval($this->parameters['edit_id']) . ", '"
								. mysql_real_escape_string($set['setSpec']) . "', '"
								. mysql_real_escape_string($set['setName']) . "', "
								. "TRUE, "
								. (isset($set['harvest']) ? 1 : 0) . ",
								-1,
								0), ";
					}

					$sql = substr($sql, 0, -2);
					$result = mysql_query($sql, $this->db_link);
					if (!$result) {
						$error = new error($this->document);
						$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
						return;
					}
				}

				if (array_key_exists('deleted',$this->parameters['sets']) && count($this->parameters['sets']['deleted']) > 0) {
					// gelöschte Sets
					foreach($this->parameters['sets']['deleted'] AS $set) {
						$sql = "UPDATE oai_sets SET
								online = 0,
								setName = '" . mysql_real_escape_string($set['setName']) . "',
								harvest = " . (isset($set['harvest']) ? 1 : 0) . "
								WHERE id = " . intval($set['id']);
						$result = mysql_query($sql, $this->db_link);
						if (!$result) {
							$error = new error($this->document);
							$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
							return;
						}
					}
				}


				$this->contentElement->appendChild($this->makeElementWithText('p', 'Die OAI-Quelle wurde gespeichert.'));

				// Anzeigen, ob die Quelle zum Neuindexierung markiert wurde
				if ($reindex) {
					$this->contentElement->appendChild($this->makeElementWithText('p', 'Die OAI-Quelle wird im nächsten Harvestvorgang komplett geharvested und mit den neuen Einstellungen indexiert. Die Änderungen im Index sind erst danach sichtbar.'));
				}

				// Für die Zeitangaben
				$german_months = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");

				// Anzeigen, ob Einträge aus dem Index entfernt wurden, bzw. dabei ein Fehler auftrat
				// Der Fall "0" wird nicht bearbeitet, in diesem Fall erscheint keine Meldung (ist ja nix passiert).
				if ($delete_count > -2) {
					// Es wurde eine Löschanfrage gestellt
					if ($delete_count == -1) {
						// Ein Fehler ist aufgetreten
						$p = $this->makeElementWithText('p', 'Beim Löschen der Indexeinträge ist ein Fehler aufgetreten. Bitte den Vorgang später noch einmal wiederholen (»Harvesten ab« um einen Tag verschieben).');
						$this->contentElement->appendChild($p);
						$p->setAttribute('class', 'error');
					}

					if ($delete_count > 0) {
						// Indexeinträge wurden gelöscht
						$date = date_create($this->parameters['from']);
						$p = $this->document->createElement('p');
						$this->contentElement->appendChild($p);
						$p->appendChild($this->makeElementWithText('em', $delete_count));
						$p->appendChild($this->document->createTextNode(' Einträge wurden aus dem Index entfernt. Es befinden sich jetzt nur noch Einträge ab dem ' . (date_format($date, 'j. ')) . $german_months[(date_format($date, 'n'))-1] . (date_format($date, ' Y')) . ' im Index. '));
					}
				}
				else if (strlen($this->parameters['from']) == 10
							&& array_key_exists('current_from_db', $this->parameters)
							&& strlen($this->parameters['current_from_db']) == 10
							&& $this->parameters['from'] < $this->parameters['current_from_db']) {
					// Ist ein altes und eine neues from-Datum gesetzt und
					// Liegt das neue "from" Datum vor dem alten, müssen Daten nachgeharvested werden
					// siehe auch oben...
					$date = date_create($this->parameters['from']);
					$this->contentElement->appendChild($this->makeElementWithText('p', 'Das neue »Harvesten ab«-Datum liegt vor dem alten, daher wird die Quelle ab dem ' . (date_format($date, 'j. ')) . $german_months[(date_format($date, 'n'))-1] . (date_format($date, ' Y')) . ' neu geharvestet.'));
				}

				if (strlen($this->parameters['from']) == 0
						&& array_key_exists('current_from_db', $this->parameters)
						&& strlen($this->parameters['current_from_db']) == 10) {
					// Es gibt ein altes from-Datum. Dies wurde beim Editieren gelöscht
					// Der Nutzer wurde darauf hingewiesen, dass durch diese Konstellation die Quelle
					// komplett neu geharvested wird und hat dies bestätigt.
					$this->contentElement->appendChild($this->makeElementWithText('p', 'Das »Harvesten ab«-Datum wurde gelöscht – die Quelle wird komplett neu geharvestet.'));
				}

			} else {
				// Die Session ist abgelaufen
				$this->contentElement->appendChild($this->makeElementWithText('p', 'Die Session ist abgelaufen und der Datensatz wurde in der Zwischenzeit zum Bearbeiten geöffnet.'));
				$p = $this->makeElementWithText('p', 'Die Änderungen wurden nicht gespeichert.');
				$this->contentElement->appendChild($p);
				$p->setAttribute('class', 'error');
			}


			$form = $this->makeForm();
			$formP = $this->document->createElement('p');
			$form->appendChild($formP);
			$formP->appendChild($this->makeInput('hidden', 'do', 'list_oai_sources'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_url'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_bool', 'AND'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'sortby', 'name'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'sorhow', 'ASC'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'id', 'none'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'start', 0));
			$formP->appendChild($this->makeInputForParameter('hidden', 'limit', 20));
			$formP->appendChild($this->makeInputForParameter('hidden', 'show_active', 0));
			$formP->appendChild($this->makeInputForParameter('hidden', 'show_status', 0));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
			$formP->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
			$button = $this->makeInput('submit', NULL, 'Zurück zur Trefferliste');
			$formP->appendChild($button);
			$button->setAttribute('onclick', 'document.forms[0].action="index.php#filter"');
		}
	}

}

?>
