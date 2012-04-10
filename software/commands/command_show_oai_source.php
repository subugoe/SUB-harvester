<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zur Anzeige der Informationen über eine OAI Quelle.
 */
class command_showOAISource extends command {

	public function getContent () {
		global $db_link;

		$output = "";

		// Wird diese Seite von einer Editierseite aufgerufen, muss der entsprechende Datensatz wieder freigegeben werden.
		if (isset($_POST['edit_id'])) {
			$sql = "DELETE FROM oai_source_edit_sessions
					WHERE oai_source = " . intval($_POST['edit_id']) . "
					AND MD5(timestamp) = '" . mysql_real_escape_string($_POST['edit_token']) . "'";
			$result = mysql_query($sql, $db_link);
			if (!$result) {
				die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
			}
		}


		// MySQL-Abfragen
		// Abfrage des Status
		$sql = "SELECT MAX(harvest_status) + MAX(index_status) AS status
				FROM oai_sets
				WHERE oai_source = " . intval($_POST['id']) . " AND harvest = TRUE";
		$result = mysql_query($sql, $db_link);
		if (!$result) {
			die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
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
				WHERE oai_sources.id = " . intval($_POST['id']) ."
				GROUP BY oai_sources.id";
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		$oai_source_data = mysql_fetch_array($result, MYSQL_ASSOC);


		// Aufbereitung der Daten (Indexierung und Anzeige wird extrahiert)
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

		$output .= "			<div style=\"display: none;\">\n";
		$output .= "				<input type=\"hidden\" id=\"limit\" value=\"20\"/>\n";
		$output .= "				<input type=\"hidden\" id=\"status\" value=\"-1\"/>\n";
		$output .= "				<input type=\"hidden\" id=\"type\" value=\"-1\"/>\n";
		$output .= "				<input type=\"hidden\" id=\"id\" value=\"" . intval($_POST['id']) . "\"/>\n";
		$output .= "			</div>\n";
		$output .= "			<p style=\"text-align: right; margin-top: -20px;\"><input type=\"button\" value=\" Zur Startseite\" onclick=\"gotoStart()\"></input></p>\n";
		$output .= "			<h2>OAI-Quelle anzeigen</h2>\n";
		$output .= "			<h3>Allgemeine Informationen</h3>\n";
		$output .= "			<table border=\"0\" width=\"100%\" cellpadding=\"2px\">\n";
		$output .= "				<colgroup>\n";
		$output .= "				    <col width=\"23%\" />\n";
		$output .= "				    <col width=\"45%\" />\n";
		$output .= "				    <col width=\"32%\" />\n";
		$output .= "			 	</colgroup>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Name:</td>\n";
		// Name
		$output .= "					<td align=\"left\" class=\"table_field_data\">".( htmlentities($oai_source_data['name'], ENT_QUOTES, 'UTF-8') )."</td>\n";
		// Fehlerzeichen
		$output .= "					<td rowspan=\"16\" align=\"left\" style=\"vertical-align: middle;\">";
		if ($oai_source_status > 0) {
			$output .= "<a href=\"#logs\"><img title=\"Es liegen Fehlermeldungen für diese OAI-Quelle vor. Bitte klicken um zu den Logs zu springen.\" alt=\"Es liegen Fehlermeldungen für diese OAI-Quelle vor. Bitte klicken um zu den Logs zu springen.\" src=\"resources/images/big_error.png\" /></a>";
		}
		$output .= "</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Request URL:</td>\n";
		// URL
		$output .= "					<td align=\"left\" class=\"table_field_data\"><a class=\"oai_set_link\" href=\"".$oai_source_data['url']."?verb=Identify\">".( htmlentities($oai_source_data['url'], ENT_QUOTES, 'UTF-8') )."</a></td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">ID:</td>\n";
		// ID
		$output .= "					<td align=\"left\" class=\"table_field_data\">".$oai_source_data['id']."</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Hinzugefügt:</td>\n";
		// Added
		$output .= "					<td align=\"left\" class=\"table_field_data\">".$oai_source_data['added']."</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Land:</td>\n";
		// Land
		$output .= "					<td align=\"left\" class=\"table_field_data\">".$oai_source_data['country_name']."</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td></td>\n";
		$output .= "					<td></td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Aktiv:</td>\n";
		// Harvest-Status
		$output .= "					<td align=\"left\" class=\"table_field_data\">";
		if ($oai_source_data['active']) {
			$output .= "<img title=\"OAI-Quelle wird geharvested\" alt=\"OAI-Quelle wird geharvested\" src=\"resources/images/ok.png\" />";
		} else {
			$output .= "<img src=\"resources/images/not_ok.png\" alt=\"OAI-Quelle wird nicht geharvested\" title=\"OAI-Quelle wird nicht geharvested\" />";
		}
		$output .= "</td>\n";
		$output .= "				</tr>\n";

		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Neuindexierung:</td>\n";
		// Neuindexierung
		$output .= "					<td align=\"left\" class=\"table_field_data\">";
		if ($oai_source_data['reindex']) {
			$output .= "<img title=\"OAI-Quelle wird beim nächsten Harvesten komplett neu indexiert\" alt=\"OAI-Quelle wird beim nächsten Harvesten komplett neu indexiert\" src=\"resources/images/ok.png\" />";
		} else {
			$output .= "<img src=\"resources/images/not_ok.png\" alt=\"OAI-Quelle ist nicht zur Neuindexierung markiert\" title=\"OAI-Quelle ist nicht zur Neuindexierung markiert\" />";
		}
		$output .= "</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td></td>\n";
		$output .= "					<td></td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Geharvestet ab:</td>\n";
		// From
		$output .= "					<td align=\"left\" class=\"table_field_data\">".( is_null($oai_source_data['from']) ? "Für diese Quelle ist kein Startzeitpunkt festgelegt." : $oai_source_data['from'] )."</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Harvest-Rhythmus:</td>\n";
		// Harvest-Rhytmus
		$output .= "					<td align=\"left\" class=\"table_field_data\">".( $oai_source_data['harvest_period'] > 1 ? "Alle ".$oai_source_data['harvest_period']." Tage" : "täglich")."</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Letztes erfolgreiches Harvesten:</td>\n";
		// Letztes erfolgreiches Harvesten
		$output .= "					<td align=\"left\" class=\"table_field_data\">";
		if (!empty($oai_source_data['last_harvested'])) {
			$output .= $oai_source_data['last_harvested'];
		} else {
			$output .= "Diese Quelle wurde noch nicht geharvested.";
		}
		$output .= "</td>\n";
		$output .= "				</tr>\n";

		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Letztes erfolgreiches Indexieren:</td>\n";
		// Letztes erfolgreiches Indexieren
		$output.= "					<td align=\"left\" class=\"table_field_data\">";
		if (!empty($oai_source_data['last_indexed'])) {
			$output .= $oai_source_data['last_indexed'];
		} else {
			$output .= "Diese Quelle wurde noch nicht indexiert.";
		}
		$output .= "</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Nächstes Harvesten:</td>\n";
		// Nächstes Harvesten
		$output .= "					<td align=\"left\" class=\"table_field_data\">".( $oai_source_data['next_harvest'] != NULL ? $oai_source_data['next_harvest'] : utf8_encode(strftime('%A, %d. %B %Y', time()+86400)) )."</td>\n";
		$output .= "				</tr>\n";
		$output .= "				<tr>\n";
		$output .= "					<td></td>\n";
		$output .= "					<td></td>\n";
		$output .= "				</tr>\n";
		$output .= "			<tr>\n";
		$output .= "					<td align=\"right\" class=\"table_field_description\">Anzahl der Indexeinträge:</td>\n";

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

		$output .= "					<td align=\"left\" class=\"table_field_data\">".( $index_entry_count >= 0 ? $index_entry_count : "<span style=\"color: red;\">Der Index ist zurzeit nicht erreichbar.</span>" )."</td>\n";
		$output .= "				</tr>\n";
		// Kommentar
		$output .= "				<tr>\n";
		$output .= "					<td align=\"right\" style=\"vertical-align: top;\" class=\"table_field_description\">Kommentar:</td>\n";
		$output .= "					<td align=\"left\" class=\"table_field_data\" colspan=\"2\">".( empty($oai_source_data['comment']) ? "Kein Kommentar vorhanden." : "<textarea name=\"comment\" cols=\"130\" rows=\"10\" disabled=\"disabled\">".( htmlentities($oai_source_data['comment'], ENT_QUOTES, 'UTF-8') )."</textarea>" )."</td>\n";
		$output .= "				</tr>\n";


		$output .= "			</table>\n";


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

		// Identifier Einstellungen
		$output .= "						<h3>Identifier Einstellungen</h3>\n";
		$output .= "						<table border=\"0\" width=\"100%\" cellpadding=\"2px\" style=\"margin-left: 4em;\">\n";
		$output .= "							<colgroup>\n";
		$output .= "							    <col width=\"15%\" />\n";
		$output .= "							    <col width=\"85%\" />\n";
		$output .= "							 </colgroup>\n";

		// Identfier Alternative
		$output .= "							<tr>\n";
		$output .= "								<td align=\"right\" class=\"table_field_description\">Alternative:</td>\n";
		$output .= "								<td align=\"left\" class=\"table_field_data\">";
		if ($oai_source_data['identifier_alternative'] == "") {
			$output .= "-";
		} else {
			$output .= $oai_source_data['identifier_alternative'];
		}
		$output .= "</td>\n";
		$output .= "							</tr>\n";

		// Filter
		$output .= "							<tr>\n";
		$output .= "								<td align=\"right\" class=\"table_field_description\">Filter:</td>\n";
		$output .= "								<td align=\"left\" class=\"table_field_data\">";
		if ($oai_source_data['identifier_filter'] == "") {
			$output .= "-";
		} else {
			$output .= $oai_source_data['identifier_filter'];
		}
		$output .= "</td>\n";
		$output .= "							</tr>\n";
		// Resolver
		$output .= "							<tr>\n";
		$output .= "								<td align=\"right\" class=\"table_field_description\">Resolver:</td>\n";
		$output .= "								<td align=\"left\" class=\"table_field_data\">";
		if ($oai_source_data['identifier_resolver'] == "") {
			$output .= "-";
		} else {
			$output .= $oai_source_data['identifier_resolver'];
		}
		$output .= "</td>\n";
		$output .= "							</tr>\n";

		// Resolver-Filter
		$output .= "							<tr>\n";
		$output .= "								<td align=\"right\" class=\"table_field_description\">Resolver-Filter:</td>\n";
		$output .= "								<td align=\"left\" class=\"table_field_data\">";
		if ($oai_source_data['identifier_resolver_filter'] == "") {
			$output .= "-";
		} else {
			$output .= $oai_source_data['identifier_resolver_filter'];
		}
		$output .= "</td>\n";
		$output .= "							</tr>\n";

		$output .= "						</table>\n";
		$output .= "					</td>\n";

		$output .= "				</tr>\n";
		$output .= "			</table>\n";


		// Sets

		// Abfrage des Pseudo-Sets
		$sql = "SELECT setname, harvest
				FROM oai_sets
				WHERE setspec LIKE '%allSets%' AND oai_source = " . intval($_POST['id']);
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		$oai_pseudoset_data = mysql_fetch_array($result, MYSQL_ASSOC);


		// Abfrage Anzahl der Sets einer OAI-Quelle
		$sql = "SELECT COUNT(id)
				FROM oai_sets
				WHERE setspec NOT LIKE '%allSets%' AND oai_source = " . intval($_POST['id']);
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		$total_set_count = mysql_result($result, 0);


		if (!$oai_pseudoset_data['harvest']) {
			// Es werden einzelne Sets, bzw. ein Set geharvested.

			// Abfrage der Sets
			$sql = "SELECT setname, setspec
					FROM oai_sets
					WHERE setspec NOT LIKE '%allSets%' AND harvest = TRUE AND oai_source = " . intval($_POST['id']);
			$result = mysql_query($sql, $db_link);
			if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}

			$harvested_set_count = mysql_num_rows($result);

			$output .= "			<h3>Geharvestete Sets (".$harvested_set_count." von ".$total_set_count.")</h3>\n";

			$output .= "			<table border=\"0\" width=\"100%\">\n";
			$output .= "				<colgroup>\n";
			$output .= "				    <col width=\"1\" />\n";
			$output .= "				    <col width=\"1\" />\n";
			$output .= "				 </colgroup>\n";
			$output .= "				<tr>\n";
			$output .= "					<td style=\"vertical-align: top;\">\n";
			$output .= "						<ul class=\"show_source_lists\">\n";

			$second_col = false;
			$i = 0.5;
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

				if ($i > $harvested_set_count / 2 && $i != 1 && !$second_col) {

					$second_col = true;

					$output .= "							</ul>\n";
					$output .= "						</td>\n";
					$output .= "						<td style=\"vertical-align: top;\">\n";
					$output .= "							<ul class=\"show_source_lists\">\n";

				}

				$output .="							<li><span style=\"color: #272ec6;\">".htmlspecialchars($row['setname'])." (".htmlspecialchars($row['setspec']).")</span></li>\n";
				$i++;
			}


			$output .= "						</ul>\n";
			$output .= "					</td>\n";
			$output .= "				</tr>\n";

		} else {

			switch ($oai_pseudoset_data['setname']) {

				case "allSets":
					$output .= "			<h3>Geharvestete Sets (<span style=\"font-weight: bold\">∞</span> von ".$total_set_count.")</h3>\n";
					$output .= "			<table border=\"0\" width=\"100%\">\n";
					$output .= "				<colgroup>\n";
					$output .= "				    <col width=\"1\" />\n";
					$output .= "				    <col width=\"1\" />\n";
					$output .= "				 </colgroup>\n";
					$output .= "				<tr>\n";
					$output .= "					<td colspan=\"2\" style=\"vertical-align: top;\">\n";
					$output .= "						<ul class=\"show_source_lists\">\n";
					$output .="							<li><span style=\"color: #272ec6;\">Alle Sets werden geharvested. Zur Ansicht der Sets bitte Quelle zum Bearbeiten öffnen.</span></li>\n";
					$output .= "						</ul>\n";
					$output .= "					</td>\n";
					break;

				case "noSetHierarchy":
					$output .= "			<h3>Geharvestete Sets</h3>\n";
					$output .= "			<table border=\"0\" width=\"100%\">\n";
					$output .= "				<colgroup>\n";
					$output .= "				    <col width=\"1\" />\n";
					$output .= "				    <col width=\"1\" />\n";
					$output .= "				 </colgroup>\n";
					$output .= "				<tr>\n";
					$output .= "					<td colspan=\"2\" style=\"vertical-align: top;\">";
					$output .= "						<ul class=\"show_source_lists\">\n";
					$output .="							<li><span style=\"color: #272ec6;\">Diese OAI-Quelle unterstützt keine Sets und wird komplett geharvested.</span></li>\n";
					$output .= "						</ul>\n";
					$output .= "					</td>\n";
					break;

				case "noSets":
					$output .= "			<h3>Geharvestete Sets</h3>\n";
					$output .= "			<table border=\"0\" width=\"100%\">\n";
					$output .= "				<colgroup>\n";
					$output .= "				    <col width=\"1\" />\n";
					$output .= "				    <col width=\"1\" />\n";
					$output .= "				 </colgroup>\n";
					$output .= "				<tr>\n";
					$output .= "					<td colspan=\"2\" style=\"vertical-align: top;\">";
					$output .= "						<ul class=\"show_source_lists\">\n";
					$output .="							<li><span style=\"color: #272ec6;\">Diese OAI-Quelle bietet keine Sets an wird komplett geharvested.</span></li>\n";
					$output .= "						</ul>\n";
					$output .= "					</td>\n";

					break;


				default:

			}

			$output .= "				</tr>\n";
		}

		$output .= "			</table>\n";


		// Formular mit den Daten einer evtl. vorgegangenen Suche um nach dort zurückzukehren
		$output .= "			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n";
		$output .= "				<div>\n";
		$output .= "					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n";

		// filter_name
		$output .= "					<input type=\"hidden\" name=\"filter_name\" value=\"";
		$current_filter_name = isset($_POST['filter_name']) ?  $_POST['filter_name'] : "";
		$output .= $current_filter_name."\"></input>\n";

		// filter_url
		$output .= "					<input type=\"hidden\" name=\"filter_url\" value=\"";
		$current_filter_url = isset($_POST['filter_url']) ? $_POST['filter_url'] : "";
		$output .= $current_filter_url."\"></input>\n";

		// filter_bool
		$output .= "					<input type=\"hidden\" name=\"filter_bool\" value=\"";
		$current_filter_bool = isset($_POST['filter_bool']) ? $_POST['filter_bool'] : "AND";
		$output .= $current_filter_bool."\"></input>\n";

		// sortby
		$output .= "					<input type=\"hidden\" name=\"sortby\" value=\"";
		$current_sortby = isset($_POST['sortby']) ? $_POST['sortby'] : "name";
		$output .= $current_sortby."\"></input>\n";

		// sorthow
		$output .= "					<input type=\"hidden\" name=\"sorthow\" value=\"";
		$current_sorthow = isset($_POST['sorthow']) ? $_POST['sorthow'] : "ASC";
		$output .= $current_sorthow."\"></input>\n";

		// id
		$output .= "					<input type=\"hidden\" name=\"id\" value=\"";
		$output .= isset($_POST['id']) ? $_POST['id'] : "none";
		$output .= "\"></input>\n";

		// start
		$output .= "					<input type=\"hidden\" name=\"start\" value=\"";
		$current_start = isset($_POST['start']) ? $_POST['start'] : "0";
		$output .= $current_start;
		$output .= "\"></input>\n";

		// limit
		$output .= "					<input type=\"hidden\" name=\"limit\" value=\"";
		$current_limit = isset($_POST['limit']) ? $_POST['limit'] : 20;
		$output .= $current_limit;
		$output .= "\"></input>\n";

		// show_active
		$output .= "					<input type=\"hidden\" name=\"show_active\" value=\"";
		$current_show_active = isset($_POST['show_active']) ? $_POST['show_active'] : 0;
		$output .= $current_show_active;
		$output .= "\"></input>\n";

		// show_status
		$output .= "					<input type=\"hidden\" name=\"show_status\" value=\"";
		$current_show_status = isset($_POST['show_status']) ? $_POST['show_status'] : 0;
		$output .= $current_show_status;
		$output .= "\"></input>\n";

		$output .= "				</div>\n";
		// Buttons
		$output .= "				<p style=\"text-align: center; margin-top: 25px;\">\n";
		$output .= "					<input type=\"submit\" value=\"Bearbeiten\" onclick=\"edit(".$oai_source_data['id'].")\"></input>&nbsp;\n";
		$output .= "					<input type=\"submit\" value=\"Löschen\" onclick=\"remove(".$oai_source_data['id'].")\"></input>&nbsp;\n";
		$output .= "					<input type=\"submit\" value=\"Zur Trefferliste\" onclick=\"document.forms[0].action = 'index.php#filter'\"></input>\n";
		$output .= "				</p>\n";
		$output .= "			</form>\n";
		$output .= "			<hr style=\"margin-top:30px; color:#8F0006; width: 50%;\" />\n";
		$output .= "			<h3 id=\"logs\" style=\"text-align: center; text-indent: 0;\">Logmeldungen der Quelle</h3>\n";
		$output .= "			<p style=\"text-align: center; margin-top: 10px; margin-left: auto; margin-right: auto; color: #424242; background-color: #D8E6B6; width: 45%; padding: 3px;\">\n";
		$output .= "				<em>Anzahl der Meldungen:</em>\n";
		$output .= "				<select id=\"max_hit_display\" name=\"limit_select\" size=\"1\" onchange=\"navigate(0)\">\n";
		$output .= "				<option value=\"5\" >5</option>\n";
		$output .= "					<option value=\"20\" selected=\"selected\">20</option>\n";
		$output .= "					<option value=\"50\" >50</option>\n";
		$output .= "					<option value=\"100\" >100</option>\n";
		$output .= "					<option value=\"150\" >150</option>\n";
		$output .= "					<option value=\"200\" >200</option>\n";
		$output .= "				</select>\n";
		$output .= "				&nbsp;&nbsp;\n";
		$output .= "				<em>Status:</em>\n";
		$output .= "				<select id=\"show_status_select\" size=\"1\" onchange=\"navigate(0)\">\n";
		$output .= "					<option value=\"-1\" selected=\"selected\">egal</option>\n";
		$output .= "					<option value=\"0\" >OK</option>\n";
		$output .= "					<option value=\"1\" >Fehler</option>\n";
		$output .= "				</select>\n";
		$output .= "				&nbsp;&nbsp;\n";
		$output .= "				<em>Typ:</em>\n";
		$output .= "				<select id=\"show_type_select\" size=\"1\" onchange=\"navigate(0)\">\n";
		$output .= "					<option value=\"-1\" selected=\"selected\">egal</option>\n";
		$output .= "					<option value=\"0\" >Harvester</option>\n";
		$output .= "					<option value=\"1\" >Indexer</option>\n";
		$output .= "				</select>\n";
		$output .= "			</p>\n";
		$output .= "			<div id=\"log_display\">";

		require_once(dirname(__FILE__) . "/../classes/log.php");
		$log = new log($db_link, -1, -1, 20, 0, $_POST['id']);
		$output .= $log->getOutput();
		$output .=	"</div>";

		return $output;
	}


}

?>
