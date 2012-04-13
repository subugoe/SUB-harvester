<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zum Bearbeiten einer OAI Quelle, die sich bereits in der Datenbank befindet.
 */
class command_editOAISource extends command {

	public function appendContent () {
		if ($this->parameters['id'] != "") {
			$id = intval($this->parameters['id']);

			/*
			 * Prüfung ob der Datensatz nicht gesperrt ist.
			 * Dazu dient die Datenbanktabelle oai_source_edit_sessions
			 */
			// Wurde das Formular bereits mit einem Token aufgerufen (z. B. Zurück bei Löschen)
			// diesen Token übernehmen.
			$token;
			if(array_key_exists('edit_id', $this->parameters)) {
				// Es gibt einen Token
				$token = $this->parameters['edit_id'];
			} else {
				// Es gibt keinen Token, Datenbank prüfen
				// Abfrage der Tabelle
				$sql = "SELECT CAST((NOW() - timestamp) AS SIGNED) AS seconds_alive, MD5(timestamp) as token
						FROM oai_source_edit_sessions
						WHERE oai_source = " . $id;
				$result = mysql_query($sql, $this->db_link);
				if (!$result) {
					$error = new error($this->document);
					$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
					return;
				}

				if (mysql_num_rows($result) == 0) {
					// Es gibt keine Session zu diesem Datensatz
					// Ein Sperreintrag wird erstellt
					$sql = "INSERT INTO oai_source_edit_sessions (
								oai_source , timestamp
							)
							VALUES (
								" . $id . ", NOW()
							)";
					$result = mysql_query($sql, $this->db_link);
					if (!$result) {
						$error = new error($this->document);
						$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
						return;
					}

					// Token abfragen
					$sql = "SELECT MD5(timestamp) as token
							FROM oai_source_edit_sessions
							WHERE oai_source = " . $id;
					$result = mysql_query($sql, $this->db_link);
					if (!$result) {
						$error = new error($this->document);
						$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
						return;
					}
					$session_data = mysql_fetch_array($result, MYSQL_ASSOC);

					$token = $session_data['token'];

				} else {
					// Es gibt bereits eine Session

					$session_data = mysql_fetch_array($result, MYSQL_ASSOC);

					if ($session_data['seconds_alive'] > 3600) {
						// Die Session ist aber abgelaufen

						// Den Timestamp der Session aktualiseren
						$sql = "UPDATE oai_source_edit_sessions
								SET timestamp = NOW()
								WHERE oai_source = " . $id;
						$result = mysql_query($sql, $this->db_link);
						if (!$result) {
							$error = new error($this->document);
							$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
							return;
						}

						// Token abfragen
						$sql = "SELECT MD5(timestamp) as token
								FROM oai_source_edit_sessions
								WHERE oai_source = " . $id;
						$result = mysql_query($sql, $this->db_link);
						if (!$result) {
							$error = new error($this->document);
							$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
							return;
						}
						$session_data = mysql_fetch_array($result, MYSQL_ASSOC);

						$token = $session_data['token'];

					} else {
						// Der Datensatz ist gesperrt
						$token = false;
					}
				}
			}

			$this->contentElement->appendChild($this->makeElementWithText('h2', 'OAI-Quelle bearbeiten'));

			$form = $this->makeForm();
			$this->contentElement->appendChild($form);
			$form->setAttribute('class', 'edit');
			$form->setAttribute('onsubmit', 'return validate_edit()');

			$form->appendChild($this->makeInput('hidden', 'do', 'update_oai_source'));
			$form->appendChild($this->makeInput('hidden', 'edit_id', $this->parameters['id']));
			$form->appendChild($this->makeInput('hidden', 'edit_token', $token));
			$form->appendChild($this->makeInput('hidden', 'edit_abort', 0));


			// Kann der Datensatz bearbeitet werden?
			if ($token) {
				// Datensatz ist nicht gesperrt.
				// Abfrage der Informationen zur Quelle aus der Datenbank (es werden fast alle Felder gebraucht => "*")
				$sql = "SELECT *
						FROM oai_sources
						WHERE id = " . $id;
				$result = mysql_query($sql, $this->db_link);
				if (!$result) {
					$error = new error($this->document);
					$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
					return;
				}
				$oai_source_data = mysql_fetch_array($result, MYSQL_ASSOC);


				// Hinweis, bzw. Anzeigen einer vollständigen Neuindexierung
				$note;
				if($oai_source_data['reindex']) {
					$note = $this->makeElementWithText('p', 'Diese Quelle wird augrund von Änderungen an den Einstellungen beim nächsten Harvesten komplett neu indexiert.', 'red');
				} else {
					$note = $this->makeElementWithText('p', 'Achtung: Änderungen an der Indexierung (auch Land) führen zu einer kompletten Neuindexierung der OAI-Quelle.');
				}
				$form->appendChild($note);

				$form->appendChild($this->makeElementWithText('h3', 'Allgemeine Einstellungen'));

				$form->appendChild($this->makeInputWithLabel('text', 'name', 'Name:', $oai_source_data['name']));

				$form->appendChild($this->makeInputWithLabel('text', 'url', 'URL:', $oai_source_data['url']));

				$countrySpan = $this->document->createElement('span');
				$form->appendChild($countrySpan);
				$countrySpan->setAttribute('class', 'inputContainer');
				$label = $this->makeLabel('country', 'Land:');
				$countrySpan->appendChild($label);
				require_once(dirname(__FILE__) . '/../classes/country_parser.php');
				$countries = new country_parser($this);
				$countrySelect = $countries->makeCountriesSelect($oai_source_data['country_code']);
				$countrySpan->appendChild($countrySelect);
				$countrySelect->setAttribute('id', 'country');

				$fromValue = '';
				if ($oai_source_data['from'] === '0000-00-00') {
					$fromValue = $oai_source_data['from'];
				}
				$form->appendChild($this->makeInputWithLabel('text', 'from', 'Harvesten ab:', $fromValue));

				$rhythmus = $this->makeInputWithLabel('text', 'harvest_period', 'Harvest-Rhythmus:', $oai_source_data['harvest_period']);
				$form->appendChild($rhythmus);

				$checkboxAndLabel = $this->makeInputWithLabel('checkbox', 'active', 'Aktiv:');
				$form->appendChild($checkboxAndLabel);
				$checkbox = $checkboxAndLabel->lastChild;
				if ($oai_source_data['active']) {
					$checkbox->setAttribute('checked', 'checked');
				}

				$commentSpan = $this->document->createElement('span');
				$form->appendChild($commentSpan);
				$commentSpan->setAttribute('class', 'inputContainer');
				$commentSpan->appendChild($this->makeLabel('comment', 'Kommentare:'));
				$textarea = $this->makeElementWithText('textarea', $oai_source_data['comment']);
				$textarea->setAttribute('name', 'comment');
				$commentSpan->appendChild($textarea);

				$form->appendChild($this->makeClear());

/*

				// Nachbearbeitung
				$content .= "				<h3>Nachbearbeitung</h3>\n";
				$content .= "				<table border=\"0\" width=\"100%\">\n";
				$content .= "					<colgroup>\n";
				$content .= "					    <col width=\"15%\" />\n";
				$content .= "					    <col width=\"85%\" />\n";
				$content .= "					 </colgroup>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\" >dc:date:</td>\n";
				$content .= "						<td align=\"left\">\n";
				$content .= "							<select id=\"config_dc_date_postproc\" size=\"1\" name=\"dc_date_postproc\">\n";
				$content .= "								<option value=\"0\"".( $oai_source_data['dc_date_postproc'] == 0 ? " selected=\"selected\"" : "").">Deaktiviert</option>\n";
				$content .= "								<option value=\"1\"".( $oai_source_data['dc_date_postproc'] == 1 ? " selected=\"selected\"" : "").">Jahr</option>\n";
				$content .= "								<option value=\"2\"".( $oai_source_data['dc_date_postproc'] == 2 ? " selected=\"selected\"" : "").">Jahr-Monat</option>\n";
				$content .= "								<option value=\"3\"".( $oai_source_data['dc_date_postproc'] == 3 ? " selected=\"selected\"" : "").">Jahr-Monat-Tag</option>\n";
				$content .= "							</select>\n";
				$content .= "						</td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td></td>\n";
				$content .= "						<td></td>\n";
				$content .= "					</tr>\n";
				$content .= "				</table>\n";


				// Indexierte Elemente
				$content .= "				<h3>Indexierte Elemente</h3>\n";
				$content .= "				<table border=\"0\" width=\"100%\">\n";
				$content .= "					<colgroup>\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "					    <col width=\"12.5%\" />\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "					    <col width=\"12.5%\" />\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "						<col width=\"12.5%\" />\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "						<col width=\"12.5%\" />\n";
				$content .= "					 </colgroup>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dccreator\" name=\"index_creator\" type=\"checkbox\" ".($oai_source_data['index_creator'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dccreator\">dc:creator</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcdate\" name=\"index_date\" type=\"checkbox\" ".($oai_source_data['index_date'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcdate\">dc:date</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcrelation\" name=\"index_relation\" type=\"checkbox\" ".($oai_source_data['index_relation'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcrelation\">dc:relation</label></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dccontributor\" name=\"index_contributor\" type=\"checkbox\" ".($oai_source_data['index_contributor'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dccontributor\">dc:contributor</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcidentifier\" name=\"index_identifier\" type=\"checkbox\" ".($oai_source_data['index_identifier'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcidentifier\">dc:identifier</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcsubject\" name=\"index_subject\" type=\"checkbox\" ".($oai_source_data['index_subject'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcsubject\">dc:subject</label></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcpublisher\" name=\"index_publisher\" type=\"checkbox\" ".($oai_source_data['index_publisher'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcpublisher\">dc:publisher</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcsource\" name=\"index_source\" type=\"checkbox\" ".($oai_source_data['index_source'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcsource\">dc:source</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcdescription\" name=\"index_description\" type=\"checkbox\" ".($oai_source_data['index_description'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcdescription\">dc:description</label></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td></td>\n";
				$content .= "					</tr>\n";
				$content .= "				</table>\n";


				// Angezeigte Elemente

				$content .= "				<h3>Angezeigte Elemente</h3>\n";
				$content .= "				<table border=\"0\" width=\"100%\">\n";
				$content .= "					<colgroup>\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "					    <col width=\"12.5%\" />\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "					    <col width=\"12.5%\" />\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "						<col width=\"12.5%\" />\n";
				$content .= "					    <col width=\"10%\" />\n";
				$content .= "					    <col width=\"12.5%\" />\n";
				$content .= "					 </colgroup>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dccreator\" name=\"view_creator\" type=\"checkbox\" ".($oai_source_data['view_creator'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dccreator\">dc:creator</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dcdate\" name=\"view_date\" type=\"checkbox\" ".($oai_source_data['view_date'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dcdate\">dc:date</label></td>\n";
				$content .= "						<td></td>\n";
				$content .= "						<td></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dccontributor\" name=\"view_contributor\" type=\"checkbox\" ".($oai_source_data['view_contributor'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dccontributor\">dc:contributor</label></td>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dcidentifier\" name=\"view_identifier\" type=\"checkbox\" ".($oai_source_data['view_identifier'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dcidentifier\">dc:identifier</label></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dcpublisher\" name=\"view_publisher\" type=\"checkbox\" ".($oai_source_data['view_publisher'] ? "checked=\"checked\" " : "" )."/></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dcpublisher\">dc:publisher</label></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td></td>\n";
				$content .= "					</tr>\n";
				$content .= "				</table>\n";

*/


				$form->appendChild($this->makeElementWithText('h3', 'Verlinkungseinstellungen'));

				$form->appendChild($this->makeInputWithLabel('text', 'identifier_alternative', 'Alternativer Link:', $oai_source_data['identifier_alternative']));
				$form->appendChild($this->makeInputWithLabel('text', 'identifier_filter', 'Identifier-Filter:', $oai_source_data['identifier_filter']));
				$form->appendChild($this->makeInputWithLabel('text', 'identifier_resolver', 'Identifier-Resolver:', $oai_source_data['identifier_resolver']));
				$form->appendChild($this->makeInputWithLabel('text', 'identifier_resolver_filter', 'Identifier-Resolver-Filter:', $oai_source_data['identifier_resolver_filter']));
				$form->appendChild($this->makeClear());

				$form->appendChild($this->makeElementWithText('h3', 'Zu harvestende Sets'));

				require_once(dirname(__FILE__) . "/../classes/oai_set_list_compare.php");
				$OAISetList = new oai_set_list_compare($this, $oai_source_data['url']);

				$OAISetList->listSets();
				if ($OAISetList->listSetsSuccessful()) {
					$form->appendChild($OAISetList->getTablesForID($id));
				} else {
					$p = $this->makeElementWithText('p', $OAISetList->getErrorMessage() . ' Es sind keine Änderungen an den Set-Einstellungen möglich.', 'error');
					if ($OAISetList->getErrorCode() === 'noSetHierarchy') {
						$p->setAttribute('id', 'noSetHierarchy');
					}
					$form->appendChild($p);
					$form->appendChild($OAISetList->makeInactiveTables());
				}

				$p = $this->document->createElement('p');
				$p->setAttribute('class', 'buttons');
				$button = $this->makeInput('submit', NULL, 'Löschen');
				$button->setAttribute('onclick', 'remove(' . $oai_source_data['id'] . ')');
				$p->appendChild($button);
				$button = $this->makeInput('submit', NULL, 'Abbrechen');
				$button->setAttribute('onclick', 'document.forms[0].elements["edit_abort"].value = 1');
				$p->appendChild($button);
				$button = $this->makeInput('submit', NULL, 'Speichern');
				$button->setAttribute('class', 'default');
				if (!$OAISetList->listSetsSuccessful() && $OAISetList->getErrorCode() !== 'noSetHierarchy') {
					$button->setAttribute('disabled', 'disabled');
				}
				$p->appendChild($button);

			} else {
				// Datensatz ist gesperrt.
				$form->appendChild($this->makeElementWithText('p', 'Der Datensatz wird gerade bearbeitet und ist deshalb gesperrt.'));
			}

			$form->appendChild($this->makeInput('hidden', 'new_from_day_before'));
			$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
			$form->appendChild($this->makeInputForParameter('hidden', 'filter_url'));
			$form->appendChild($this->makeInputForParameter('hidden', 'filter_bool', 'AND'));
			$form->appendChild($this->makeInputForParameter('hidden', 'sortby', 'name'));
			$form->appendChild($this->makeInputForParameter('hidden', 'sorthow', 'ASC'));
			$form->appendChild($this->makeInputForParameter('hidden', 'id'));
			$form->appendChild($this->makeInputForParameter('hidden', 'start', 0));
			$form->appendChild($this->makeInputForParameter('hidden', 'limit', 20));
			$form->appendChild($this->makeInputForParameter('hidden', 'show_active', 0));
			$form->appendChild($this->makeInputForParameter('hidden', 'show_status', 0));

			if ($token) {
				$form->appendChild($this->makeInput('hidden', 'current_from_db', $oai_source_data['from']));
				$form->appendChild($this->makeInput('hidden', 'edit_id', $id));
				$form->appendChild($this->makeInput('hidden', 'edit_token', $token));
			}

			$p = $this->document->createElement('p');
			$button = $this->makeInput('submit', NULL, 'Zurück zur Quellenliste');
			$p->appendChild($button);
			$button->setAttribute('onclick', 'document.forms[0].action = "index.php#filter"; document.forms[0].elements["do"].value = "list_oai_sources"');

		} else {
			$this->headElement->appendChild($this->makeRedirect());
		}
	}

}

?>
