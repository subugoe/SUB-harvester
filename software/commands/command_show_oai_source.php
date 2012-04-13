<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zur Anzeige der Informationen über eine OAI Quelle.
 */
class command_showOAISource extends command {

	public function appendContent () {
		$this->clearEditLock();

		$id = intval($this->parameters['id']);
		// MySQL-Abfragen
		// Abfrage des Status
		$sql = "SELECT MAX(harvest_status) + MAX(index_status) AS status
				FROM oai_sets
				WHERE oai_source = " . $id . " AND harvest = TRUE";
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}
		$oai_source_status = mysql_result($result, 0);

		// Abfrage der Informationen zur Quelle
		$sql = "SELECT 	oai_sources.id ,
						oai_sources.url ,
						oai_sources.name ,
						oai_sources.reindex ,
						oai_sources.view_creator AS 'v_dc:creator',
						oai_sources.view_contributor AS 'v_dc:contributor' ,
						oai_sources.view_publisher AS 'v_dc:publisher' ,
						oai_sources.view_date AS 'v_dc:date' ,
						oai_sources.view_identifier AS 'v_dc:identifier' ,
						oai_sources.index_relation AS 'i_dc:relation' ,
						oai_sources.index_creator AS 'i_dc:creator' ,
						oai_sources.index_contributor AS 'i_dc:contributor' ,
						oai_sources.index_publisher AS 'i_dc:publisher' ,
						oai_sources.index_date AS 'i_dc:date' ,
						oai_sources.index_identifier AS 'i_dc:identifier' ,
						oai_sources.index_subject AS 'i_dc:subject' ,
						oai_sources.index_description AS 'i_dc:description' ,
						oai_sources.index_source AS 'i_dc:source' ,
						oai_sources.identifier_filter ,
						oai_sources.identifier_resolver ,
						oai_sources.identifier_resolver_filter ,
						oai_sources.identifier_alternative ,
						oai_sources.dc_date_postproc AS dc_date_postproc,
						oai_sources.comment AS comment ,
						countries.name_german AS country_name ,
						oai_sources.active ,
						DATE_FORMAT(oai_sources.added, '%W, %e. %M %Y, %k:%i Uhr') AS added ,
						DATE_FORMAT(oai_sources.from, '%e. %M %Y') AS 'from' ,
						oai_sources.harvest_period ,
						DATE_FORMAT(MAX(oai_sets.last_harvested), '%W, %e. %M %Y, %k:%i Uhr') AS last_harvested ,
						DATE_FORMAT(MAX(oai_sets.last_indexed), '%W, %e. %M %Y, %k:%i Uhr') AS last_indexed ,
						DATE_FORMAT(oai_sources.last_harvest + INTERVAL oai_sources.harvest_period DAY + INTERVAL 1 DAY, '%W, %e. %M %Y') AS next_harvest
				FROM (oai_sources INNER JOIN countries ON oai_sources.country_code = countries.code)
						INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source
				WHERE oai_sources.id = " . $id ."
				GROUP BY oai_sources.id";
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}
		$oai_source_data = mysql_fetch_array($result, MYSQL_ASSOC);

		$form = $this->makeForm();
		$this->contentElement->appendChild($form);
		$form->appendChild($this->makeInput('hidden', 'limit', '20'));
		$form->appendChild($this->makeInput('hidden', 'status', -1));
		$form->appendChild($this->makeInput('hidden', 'type', -1));
		$form->appendChild($this->makeInput('hidden', 'id', $id));

		$this->contentElement->appendChild($this->makeElementWithText('h2', 'OAI-Quelle anzeigen'));

		$this->contentElement->appendChild($this->makeGeneralInformation($oai_source_data, $oai_source_status));


/*
 *
 * 		// Aufbereitung der Daten (Indexierung und Anzeige wird extrahiert)
		// Dadurch kann die Erstellung der Listen flexibler gestaltet werden und ist erweiterbar
		$indexed_elements = array('dc:title (fest)');
		$displayed_elements = array('dc:title (fest)');

		foreach ($oai_source_data as $key => $value) {

			if (substr($key, 0, 2) == "v_" && $value) {
				$displayed_elements[] = substr($key, 2);
			}

			if (substr($key, 0, 2) == "i_" && $value) {
				$indexed_elements[] = substr($key, 2);
			}
		}

		sort($indexed_elements);
		sort($displayed_elements);


		// Nachbearbeitung
		$output .= "			<h3>Nachbearbeitung</h3>\n";
		$output .= "			<table border=\"0\" width=\"100%\" cellpadding=\"2px\">\n";
		$output .= "				<colgroup>\n";
		$output .= "				    <col width=\"14%\" />\n";
		$output .= "				    <col width=\"86%\" />\n";
		$output .= "			 	</colgroup>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">dc:date:</td>\n";
		// dc:date
		$current_dc_date_postproc_value = '';
		switch($oai_source_data['dc_date_postproc']) {
			case 0:
				$current_dc_date_postproc_value = 'Deaktiviert';
				break;
			case 1:
				$current_dc_date_postproc_value = 'Jahr';
				break;
			case 2:
				$current_dc_date_postproc_value = 'Jahr-Monat';
				break;
			case 3:
				$current_dc_date_postproc_value = 'Jahr-Monat-Tag';
				break;
			default:
				$current_dc_date_postproc_value = 'Fehler!';
				break;
		}
		$output .= "					<td align=\"left\" class=\"table_field_data\">$current_dc_date_postproc_value</td>\n";
		$output .= "				</tr>\n";
		$output .= "			</table>\n";



		$output .= "			<table border=\"0\" width=\"100%\">\n";
		$output .= "				<colgroup>\n";
		$output .= "				    <col width=\"30%\" />\n";
		$output .= "				    <col width=\"30%\" />\n";
		$output .= "				   	<col width=\"30%\" />\n";
		$output .= "				</colgroup>\n";
		$output .= "				<tr>\n";

		// Indexierte Elemente
		$output .= "					<td align=\"left\" style=\"vertical-align: top;\">\n";
		$output .= "						<h3>Indexierte Elemente</h3>\n";
		$output .= "						<ul class=\"show_source_lists\">\n";

		// Ausgabe der indexierten Elemente in einer Schleife (Erstellung des Arrays s. o.)
		foreach($indexed_elements as $element) {
			$output .= "							<li><span style=\"color: #272ec6;\">".$element."</span></li>\n";
		}

		$output .= "							</ul>\n";
		$output .= "						</td>\n";

		// Angezeigte Elemente
		$output .= "					<td align=\"left\" style=\"vertical-align: top;\">\n";
		$output .= "						<h3>Angezeigte Elemente</h3>\n";
		$output .= "						<ul class=\"show_source_lists\">\n";

		// Ausgabe der angezeigten Elemente in einer Schleife (Erstellung des Arrays s. o.)
		foreach($displayed_elements as $element) {
			$output .= "							<li><span style=\"color: #272ec6;\">".$element."</span></li>\n";
		}

		$output .= "							</ul>\n";
		$output .= "						</td>\n";
		$output .= "						<td align=\"left\" style=\"vertical-align: top;\">\n";
*/


		$this->contentElement->appendChild($this->makeIdentifierInformation($oai_source_data));

		// Sets

		// Abfrage des Pseudo-Sets
		$sql = "SELECT setname, harvest
				FROM oai_sets
				WHERE setspec LIKE '%allSets%' AND oai_source = " . $id;
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}
		$oai_pseudoset_data = mysql_fetch_array($result, MYSQL_ASSOC);


		// Abfrage Anzahl der Sets einer OAI-Quelle
		$sql = "SELECT COUNT(id)
				FROM oai_sets
				WHERE setspec NOT LIKE '%allSets%' AND oai_source = " . $id;
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}
		$total_set_count = mysql_result($result, 0);

		$headingText = 'Geharvestete Sets';
		$setsUL = $this->document->createElement('ul');
		$setsUL->setAttribute('class', 'show_source_lists');

		if (!$oai_pseudoset_data['harvest']) {
			// Es werden einzelne Sets, bzw. ein Set geharvested.

			// Abfrage der Sets
			$sql = "SELECT setname, setspec
					FROM oai_sets
					WHERE setspec NOT LIKE '%allSets%' AND harvest = TRUE AND oai_source = " . $id;
			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
				return;
			}

			$harvested_set_count = mysql_num_rows($result);
			$headingText .= ' (' . $harvested_set_count . ' von ' . $total_set_count . ')';

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$setDescription = $row['setname'] . ' (' . $row['setspec'] . ')';
				$li = $this->makeElementWithText('li', $setDescription);
				$setsUL->appendChild($li);
			}
		}
		else {
			switch ($oai_pseudoset_data['setname']) {

				case "allSets":
					$headingText .= '(∞ von ' . $total_set_count . ')';
					$li = $this->makeElementWithText('li', 'Alle Sets werden geharvested. Zur Ansicht der Sets bitte Quelle zum Bearbeiten öffnen.');
					$setsUL->appendChild($li);
					break;

				case "noSetHierarchy":
					$li = $this->makeElementWithText('li', 'Diese OAI-Quelle unterstützt keine Sets und wird vollständig geharvestet.');
					$setsUL->appendChild($li);
					break;

				case "noSets":
					$li = $this->makeElementWithText('li', 'Diese OAI-Quelle bietet keine Sets an wird vollständig geharvestet.');
					$setsUL->appendChild($li);
					break;
			}
		}

		$this->contentElement->appendChild($this->makeElementWithText('h3', $headingText));
		$this->contentElement->appendChild($setsUL);

		$this->contentElement->appendChild($this->makeButtons($oai_source_data));

		$this->contentElement->appendChild($this->logSection($id));
	}



	private function makeGeneralInformation ($oai_source_data, $oai_source_status) {
		$container = $this->document->createElement('div');
		$container->appendChild($this->makeElementWithText('h3', 'Allgemeine Informationen'));

		$dl = $this->document->createElement('dl');
		$container->appendChild($dl);
		$this->appendDTDDWithTextTo('Name:', $oai_source_data['name'], $dl);

		if ($oai_source_status > 0) {
			$lastDD = $dl->lastChild;
			$a = $this->makeElementWithText('a', 'Es liegen Fehlermeldungen für diese OAI-Quelle vor. Bitte klicken um zu den Logs zu springen.', 'errorIcon');
			$dl->lastChild->appendChild($a);
			$a->setAttribute('href', '#logs');
		}

		$this->appendDTDDWithTextTo('Request URL:', $oai_source_data['url'], $dl);

		$this->appendDTDDWithTextTo('Interne ID:', $oai_source_data['id'], $dl);

		$this->appendDTDDWithTextTo('Hinzugefügt:', $oai_source_data['added'], $dl);

		$this->appendDTDDWithTextTo('Land:', $oai_source_data['country_name'], $dl);

		$img = $this->document->createElement('img');
		if ($oai_source_data['active']) {
			$img->setAttribute('src', 'resources/images/ok.png');
			$img->setAttribute('alt', 'OAI-Quelle wird geharvestet.');
		} else {
			$img->setAttribute('src', 'resources/images/not_ok.png');
			$img->setAttribute('alt', 'OAI-Quelle wird nicht geharvestet.');
		}
		$this->appendDTDDWithContentTo($this->document->createTextNode('Aktiv:'), $img, $dl);

		$img = $this->document->createElement('img');
		if ($oai_source_data['active']) {
			$img->setAttribute('src', 'resources/images/ok.png');
			$img->setAttribute('alt', 'OAI-Quelle wird beim nächsten Harvesten komplett neu indexiert.');
		} else {
			$img->setAttribute('src', 'resources/images/not_ok.png');
			$img->setAttribute('alt', 'OAI-Quelle ist nicht zur Neuindexierung markiert.');
		}
		$this->appendDTDDWithContentTo($this->document->createTextNode('Neuindexierung:'), $img, $dl);

		$fromText = 'Für diese Quelle ist kein Startzeitpunkt festgelegt.';
		if (!is_null($oai_source_data['from'])) {
			$fromText = $oai_source_data['from'];
		}
		$this->appendDTDDWithTextTo('Request URL:', $fromText, $dl);

		$intervalText = 'täglich';
		if ($oai_source_data['harvest_period'] > 1) {
			$intervalText = 'Alle ' . $oai_source_data['harvest_period'] . ' Tage';
		}
		$this->appendDTDDWithTextTo('Harvest-Rhythmus:', $intervalText, $dl);

		$lastHarvestDate = 'Diese Quelle wurde noch nicht geharvested.';
		if (!empty($oai_source_data['last_harvested'])) {
			$lastHarvestDate = $oai_source_data['last_harvested'];
		}
		$this->appendDTDDWithTextTo('Letztes erfolgreiches Harvesten:', $lastHarvestDate, $dl);

		$lastIndexDate = 'Diese Quelle wurde noch nicht indexiert.';
		if (!empty($oai_source_data['last_indexed'])) {
			$lastIndexDate = $oai_source_data['last_indexed'];
		}
		$this->appendDTDDWithTextTo('Letztes erfolgreiches Indizieren:', $lastIndexDate, $dl);

		$nextHarvestDate = $oai_source_data['next_harvest'];
		if ($nextHarvestDate === NULL) {
			$nextHarvestDate = strftime('%A, %d. %B %Y', time() + 86400);
		}
		$this->appendDTDDWithTextTo('Nächstes Harvesten:', $nextHarvestDate, $dl);

		$index_entry_count = $this->getIndexEntryCount($oai_source_data);
		$class = '';
		if ($index_entry_count === -1) {
			$index_entry_count = 'Der Index ist zurzeit nicht erreichbar.';
			$class = 'warning';
		}
		$this->appendDTDDWithTextTo('Anzahl der Indexeinträge:', $index_entry_count, $dl, $class);

		$this->appendDTDDWithTextTo('Kommentar:', $oai_source_data['comment'], $dl);

		$container->appendChild($this->makeClear());

		return $container;
	}



	private function getIndexEntryCount ($oai_source_data) {
		$index_entry_count = 0;

		// Index abfragen
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, SOLR . '/select?version=2.2&rows=0&q=oai_repository_id%3A' . $oai_source_data['id']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

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

		return $index_entry_count;
	}



	private function makeIdentifierInformation ($oai_source_data) {
		$container = $this->document->createElement('div');
		$container->appendChild($this->makeElementWithText('h3', 'Allgemeine Informationen'));

		$dl = $this->document->createElement('dl');
		$container->appendChild($dl);

		$text = $oai_source_data['identifier_alternative'];
		if ($text === '') {
			$text = '-';
		}
		$this->appendDTDDWithTextTo('Alternative:', $text, $dl);

		$text = $oai_source_data['identifier_filter'];
		if ($text === '') {
			$text = '-';
		}
		$this->appendDTDDWithTextTo('Filter:', $text, $dl);

		$text = $oai_source_data['identifier_resolver'];
		if ($text === '') {
			$text = '-';
		}
		$this->appendDTDDWithTextTo('Resolver:', $text, $dl);

		$text = $oai_source_data['identifier_resolver_filter'];
		if ($text === '') {
			$text = '-';
		}
		$this->appendDTDDWithTextTo('Resolver-Filter:', $text, $dl);

		$container->appendChild($this->makeClear());

		return $container;
	}



	private function makeButtons ($oai_source_data) {
		$form = $this->makeForm();
		$form->setAttribute('id', 'command');

		$form->appendChild($this->makeInput('hidden', 'do', 'list_oai_sources'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_url'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_bool', 'AND'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_sortby', 'name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_sorthow', 'ASC'));
		$form->appendChild($this->makeInputForParameter('hidden', 'id'));
		$form->appendChild($this->makeInputForParameter('hidden', 'start', 0));
		$form->appendChild($this->makeInputForParameter('hidden', 'limit', 20));
		$form->appendChild($this->makeInputForParameter('hidden', 'show_active', 0));
		$form->appendChild($this->makeInputForParameter('hidden', 'show_status', 0));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));
		$form->appendChild($this->makeInputForParameter('hidden', 'filter_name'));

		$p = $this->document->createElement('p');
		$form->appendChild($p);
		$p->setAttribute('class', 'buttons');

		$button = $this->makeInput('submit', NULL, 'Bearbeiten');
		$p->appendChild($button);
		$button->setAttribute('onclick', 'edit(' . $oai_source_data['id'] . ')');

		$button = $this->makeInput('submit', NULL, 'Löschen');
		$p->appendChild($button);
		$button->setAttribute('onclick', 'remove(' . $oai_source_data['id'] . ')');

		$button = $this->makeInput('submit', NULL, 'Zurück zur Quellenliste');
		$p->appendChild($button);

		return $form;
	}

}

?>
