<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zum Löschen einer OAI Quelle.
 */
class command_deleteOAISource extends command {

	/*
	 * Löscht eine OAI-Quelle, geht dabei in folgender Reihenfolge vor:
	 * 1. Index
	 * 2. Datenbank
	 *    2.1 Sessions (oai_source_edit_sessions)
	 *    2.2 Logs (oai_logs)
	 *    2.3 Sets (oai_sets)
	 *    2.4 Quelle (oai_sources)
	 */
	public function appendContent () {

		$this->contentElement->appendChild($this->makeElementWithText('h2', 'OAI-Quelle löschen'));

		if (array_key_exists('confirmed', $this->parameters)) {
			// Wird auf false gesetzt, falls irgendwo was schiefgeht
			$delete_successful = true;

			// Indexeinträge entfernen
			// Anzahl der Indexeinträge zur Anzeige direkt vom dem Löschen abfragen
			$index_entry_count = 0;
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, SOLR."/select?version=2.2&rows=0&q=oai_repository_id%3A".$this->parameters['id']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$http_response = curl_exec($ch);

			if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
				$dom = new DOMDocument();
				$dom->loadXML($http_response);

				$XPath = new DOMXPath($dom);
				$XPath_count_query = $XPath->query('/response/result/@numFound');

				$index_entry_count = $XPath_count_query->item(0)->nodeValue;
			}
			else {
				// Der Server ist nicht erreichbar
				$index_entry_count = -1;
			}

			$delete_xml = "";
			// Löschanweisung laden
			$file = fopen(dirname(__FILE__) . '/../templates/remove_oai_source.xml', "r");
			while (!feof($file)) {
				$delete_xml .= fgets($file);
			}
			fclose($file);

			// ID eintragen
			$delete_xml = str_replace("%id%", $this->parameters['id'], $delete_xml);

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
					curl_setopt($ch, CURLOPT_URL, SOLR."/select?version=2.2&rows=0&q=oai_repository_id%3A".$this->parameters['id']);

					$http_response = curl_exec($ch);

					if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
						$dom = new DOMDocument();
						$dom->loadXML($http_response);

						$XPath = new DOMXPath($dom);
						$XPath_count_query = $XPath->query('/response/result/@numFound');

						$index_entry_count_check = $XPath_count_query->item(0)->nodeValue;
					}
					else {
						// Der Server ist nicht erreichbar
						$index_entry_count_check = -1;
					}

					// Alle Indexeinträge müsste jetzt entfernt sein
					if ($index_entry_count_check == 0) {
						// Das Löschen war erfolgreich
						$message = $index_entry_count . ' Indexeintr';
						if ($index_entry_count != 1) {
							$message .= 'äge gelöscht.';
						}
						else {
							$message .= 'ag gelöscht.';
						}
						$this->contentElement->appendChild($this->makeElementWithText('p', $message));
					}
					else {
						// Indexeinträge gibt es noch
						$message = 'Fehler beim Löschen der Indexeinträge. Es befinden sich noch ' . $index_entry_count_check . ' Einträge im Index. Bitte manuell alle Einträge der ID »' . $this->parameters['id'] . '« löschen.';
						$this->contentElement->appendChild($this->makeElementWithText('p', $message, 'error'));
					}

				}
				else {
					$message = 'Der Index ist nicht erreichbar. Die Einträge konnten nicht gelöscht werden. Bitte manuell alle Einträge der ID »' . $this->parameters['id'] . '« löschen.';
					$this->contentElement->appendChild($this->makeElementWithText('p', $message, 'error'));
				}
			}
			else {
				$message = 'Fehler beim Erzeugen der temporären Datei. Die Einträge konnten nicht gelöscht werden. Bitte manuell alle inträge der ID »' . $this->parameters['id'] . '« löschen.';
			}


			// evtl. geharvestete Daten löschen?????? TODO

			// Falls noch eine Session gespeichert ist...
			// Falls der Datensatz gerade editiert wird - Pech für den Editierenden... dies wird nicht geprüft.
			$sql = "DELETE FROM oai_source_edit_sessions
					WHERE oai_source = " . intval($this->parameters['id']);
			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return $container;
			}

			$sql = "DELETE FROM oai_logs
					WHERE oai_set IN (
						SELECT id FROM oai_sets
						WHERE oai_source = " . intval($this->parameters['id']) . "
					)";
			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return $container;
			}

			$message = mysql_affected_rows($this->db_link) . ' Logeinträge gelöscht (Tabelle »oai_logs«)';
			$this->contentElement->appendChild($this->makeElementWithText('p', $message));

			// Sets der OAI-Quelle löschen
			$sql = "DELETE FROM oai_sets
					WHERE oai_source = " . intval($this->parameters['id']);
			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return $container;
			}

			if (mysql_affected_rows($this->db_link) < 1) {
				$delete_successful = false;
			}

			$message = mysql_affected_rows($this->db_link) . ' Sets gelöscht (Tabelle »oai_sets«)';
			$this->contentElement->appendChild($this->makeElementWithText('p', $message));

			// OAi-Quelle löchen
			$sql = "DELETE FROM oai_sources
					WHERE id = " . intval($this->parameters['id']);
			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return $container;
			}

			if (mysql_affected_rows($this->db_link) < 1) {
				$delete_successful = false;
			}
			$message = mysql_affected_rows($this->db_link) . ' OAI-Quelle gelöscht (Tabelle »oai_sources«)';
			$this->contentElement->appendChild($this->makeElementWithText('p', $message));


			if ($delete_successful) {
				$this->contentElement->appendChild($this->makeElementWithText('p', 'Die OAI-Quelle wurde erfolgreich gelöscht.'));
			}
			else {
				$this->contentElement->appendChild($this->makeElementWithText('p', 'Beim Löschen der OAI-Quelle sind Probleme aufgetreten, bitte Datenbank und Index prüfen.', 'error'));
			}

		}
		else {
			// Bestätigungsformular erzeugen

			// Daten abfragen
			// Abfrage der Informationen zur Quelle
			$sql = "SELECT
						oai_sources.id AS id,
						oai_sources.url AS url,
						oai_sources.name AS name,
						DATE_FORMAT(oai_sources.added, '%W, %e. %M %Y, %k:%i Uhr') AS added,
						COUNT(oai_sets.id) - 1 AS sets
					FROM oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source
					WHERE oai_sources.id = " . intval($this->parameters['id']) . "
					GROUP BY oai_sources.id";
			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return $container;
			}
			$oai_source_data = mysql_fetch_array($result, MYSQL_ASSOC);



			$this->contentElement->appendChild($this->makeGeneralInformation($oai_source_data));

			$this->contentElement->appendChild($this->makeElementWithText('p', 'Soll diese OAI-Quelle endgültig aus der Datenbank und dem Index gelöscht werden?'));

			$form = $this->makeButtons($this->parameters);
			$this->contentElement->appendChild($form);
			$form->appendChild($this->makeInput('hidden', 'confirmed', 1));
		}
	}

}

?>
