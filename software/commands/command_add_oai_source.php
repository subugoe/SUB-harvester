<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zum Anlegen einer OAI Quelle.
 */
class command_addOAISource extends command {

	public function appendContent () {
		if ($this->parameters['add_oai_source'] != "") {

			$url = trim($this->parameters['add_oai_source']);

			// Ist die OAI URL bereits vorhanden?
			$sql = "SELECT id FROM oai_sources WHERE url = '" . mysql_real_escape_string($url) . "'";
			$results = mysql_query($sql, $this->db_link);
			if ($results && mysql_num_rows($results) > 0) {
				$match = mysql_fetch_array($results, MYSQL_ASSOC);
				$this->contentElement->appendChild($this->makeElementWithText('p', 'Diese OAI-Quelle existiert bereits: Weiterleitung zur vorhandenen Seite.'));
				$this->headElement->appendChild($this->makeRedirect('0; URL=./index.php?do=show_oai_source&id=' . $match['id']));
			}
			else {
				// Versuchen, den Ländercode anhand der TLD zu erraten.
				$hostname = parse_url($url, PHP_URL_HOST);
				$tld = "";
				if ($hostname) {
					$nameComponents = explode('.', $hostname);
					$tld = $nameComponents[count($nameComponents) - 1];
				}

				$ch = curl_init();

				// Überprüfen ob die URL einen Port enthält
				/*
				$pattern = '?:\d+?';
				preg_match($pattern, $url, $matches);
				print_r($matches);

				echo str_replace(":", "", $matches[0]);

				if (count($matches) > 0) {
					// Es gibt einen Port, Port setzen
					curl_setopt($ch, CURLOPT_PORT, str_replace(":", "", $matches[0]));
					// Port aus URL entfernen
					$url = str_replace($matches[0], "", $url);
				}
				*/

				curl_setopt($ch, CURLOPT_URL, $url."?verb=Identify");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				// Ignoriere SSL-Zertifikate
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

				$http_response = curl_exec($ch);

				// Ist der Server erreichbar und ist seine Antwort nicht leer?
				if ($http_response && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {

					require_once(dirname(__FILE__) . "/../classes/oai_identify_parser.php");

					$oai_identify = new oai_identify_parser($http_response);

					if ($oai_identify->isResponseValid()) {
						$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zur Startseite', 'gotoStart()'));
						$this->contentElement->appendChild($this->makeElementWithText('h2', 'OAI-Quelle hinzufügen'));

						$form = $this->makeForm();
						$this->contentElement->appendChild($form);
						$form->setAttribute('class', 'edit new');
						$form->setAttribute('onsubmit', 'return validate()');

						$form->appendChild($this->makeInput('hidden', 'do', 'save_oai_source'));

						// Allgemeine Einstellungen
						$form->appendChild($this->makeElementWithText('h3', 'Allgemeine Eisntellungen'));

						$form->appendChild($this->makeInputWithLabel('text', 'name', 'Name:', $oai_identify->getRepositoryName()));

						$urlFieldSpan = $this->makeInputWithLabel('text', 'url', 'URL:', $this->parameters['add_oai_source']);
						$form->appendChild($urlFieldSpan);
						$urlFieldSpan->firstChild->nextSibling->setAttribute('readonly', 'readonly');

						$countrySpan = $this->document->createElement('span');
						$form->appendChild($countrySpan);
						$countrySpan->setAttribute('class', 'inputContainer');
						$label = $this->makeLabel('country', 'Land:');
						$countrySpan->appendChild($label);
						require_once(dirname(__FILE__) . '/../classes/country_parser.php');
						$countries = new country_parser($this);
						$countrySelect = $countries->makeCountriesSelect($tld);
						$countrySpan->appendChild($countrySelect);
						$countrySelect->setAttribute('id', 'country');

						$form->appendChild($this->makeInputWithLabel('text', 'from', 'Harvesten ab:'));

						$rhythmus = $this->makeInputWithLabel('text', 'harvest_period', 'Harvest-Rhythmus:', 7);
						$form->appendChild($rhythmus);

						$checkboxAndLabel = $this->makeInputWithLabel('checkbox', 'active', 'Aktiv:');
						$form->appendChild($checkboxAndLabel);
						$checkbox = $checkboxAndLabel->lastChild;
						$checkbox->setAttribute('checked', 'checked');

						$commentSpan = $this->document->createElement('span');
						$form->appendChild($commentSpan);
						$commentSpan->setAttribute('class', 'inputContainer');
						$commentSpan->appendChild($this->makeLabel('comment', 'Kommentare:'));
						$textarea = $this->makeElementWithText('textarea', '');
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
						$content .= "								<option value=\"0\" selected=\"selected\">Deaktiviert</option>\n";
						$content .= "								<option value=\"1\">Jahr</option>\n";
						$content .= "								<option value=\"2\">Jahr-Monat</option>\n";
						$content .= "								<option value=\"3\">Jahr-Monat-Tag</option>\n";
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
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dccreator\" name=\"index_creator\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dccreator\">dc:creator</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcdate\" name=\"index_date\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcdate\">dc:date</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcrelation\" name=\"index_relation\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcrelation\">dc:relation</label></td>\n";
						$content .= "					</tr>\n";
						$content .= "					<tr>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dccontributor\" name=\"index_contributor\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dccontributor\">dc:contributor</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcidentifier\" name=\"index_identifier\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcidentifier\">dc:identifier</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcsubject\" name=\"index_subject\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcsubject\">dc:subject</label></td>\n";
						$content .= "					</tr>\n";
						$content .= "					<tr>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcpublisher\" name=\"index_publisher\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcpublisher\">dc:publisher</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcsource\" name=\"index_source\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"index_dcsource\">dc:source</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"index_dcdescription\" name=\"index_description\" type=\"checkbox\" checked=\"checked\"/></td>\n";
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
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dccreator\" name=\"view_creator\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dccreator\">dc:creator</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dcdate\" name=\"view_date\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dcdate\">dc:date</label></td>\n";
						$content .= "						<td></td>\n";
						$content .= "						<td></td>\n";
						$content .= "					</tr>\n";
						$content .= "					<tr>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dccontributor\" name=\"view_contributor\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dccontributor\">dc:contributor</label></td>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dcidentifier\" name=\"view_identifier\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dcidentifier\">dc:identifier</label></td>\n";
						$content .= "					</tr>\n";
						$content .= "					<tr>\n";
						$content .= "						<td align=\"right\" valign=\"middle\"><input id=\"view_dcpublisher\" name=\"view_publisher\" type=\"checkbox\" checked=\"checked\"/></td>\n";
						$content .= "						<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"view_dcpublisher\">dc:publisher</label></td>\n";
						$content .= "
						$content .= "					</tr>\n";
						$content .= "					<tr>\n";
						$content .= "						<td></td>\n";
						$content .= "					</tr>\n";
						$content .= "				</table>\n";

*/
						// Einstellungen zur Verlinkung
						$form->appendChild($this->makeElementWithText('h3', 'Verlinkungseinstellungen'));

						$form->appendChild($this->makeInputWithLabel('text', 'identifier_alternative', 'Alternativer Link:', preg_replace("/(.*\/\/[^\/]*\/).*/", "$1", $this->parameters['add_oai_source'])));
						$form->appendChild($this->makeInputWithLabel('text', 'identifier_filter', 'Identifier-Filter:', '/^http.*/'));
						$form->appendChild($this->makeInputWithLabel('text', 'identifier_resolver', 'Identifier-Resolver:'));
						$form->appendChild($this->makeInputWithLabel('text', 'identifier_resolver_filter', 'Identifier-Resolver-Filter:'));
						$form->appendChild($this->makeClear());


						// Geharvestete Sets
						$form->appendChild($this->makeElementWithText('h3', 'Zu harvestende Sets'));

						require_once(dirname(__FILE__) . "/../classes/oai_set_list.php");
						$OAISetList = new oai_set_list($this, $this->parameters['add_oai_source']);

						$OAISetList->listSets();
						if ($OAISetList->listSetsSuccessful()) {
							$form->appendChild($OAISetList->makeTables());
						} else {
							$p = $this->makeElementWithText('p', $OAISetList->getErrorMessage(), 'error');
							$form->appendChild($p);
							$form->appendChild($OAISetList->makeTables($OAISetList->getErrorCode() === 'noSetHierarchy'));
						}

						// Abbrechen und Speichern Knöpfe
						$p = $this->document->createElement('p');
						$form->appendChild($p);
						$p->setAttribute('class', 'buttons');

						$button = $this->makeInput('button', NULL, 'Abbrechen');
						$p->appendChild($button);
						$button->setAttribute('onclick', 'gotoStart()');

						$p->appendChild($this->makeInput('submit', NULL, 'Speichern'));

					}
					else {
						$this->contentElement->appendChild($this->makeElementWithText('p', 'Die OAI-Quelle liefert eine nicht valide Antwort. Sie kann nicht hinzugefügt werden.', 'error'));
						$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zurück'));
					}

				} else {
					$this->contentElement->appendChild($this->makeElementWithText('p', 'Die URL ist ungültig oder der Sever nicht erreichbar.', 'error'));
					$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zurück'));
				}

				curl_close($ch);
			}
		}
		else {
			$this->headElement->appendChild($this->makeRedirect());
		}
	}

}

?>
