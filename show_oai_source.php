<?php

/*
 * Zeigt Informationen zu einer OAI-Quelle an
 */


// Wird diese Seite von einer Editierseite aufgerufen, muss der entsprechende Datensatz wieder freigegeben werden.
if (isset($_POST['edit_id'])) {
	
	$sql = "DELETE FROM `oai_source_edit_sessions` 
			WHERE oai_source = ".$_POST['edit_id']." AND MD5(timestamp) = '".$_POST['edit_token']."'";
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}	
}


// MySQL-Abfragen
// Abfrage des Status
$sql = "SELECT MAX(harvest_status) + MAX(index_status) AS status
		FROM `oai_sets`
		WHERE oai_source = ".$_POST['id']." AND harvest = TRUE";
$result = mysql_query($sql, $db_link);
if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
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
				FROM (`oai_sources` INNER JOIN `countries` ON oai_sources.country_code = countries.code) INNER JOIN `oai_sets` ON oai_sources.id = oai_sets.oai_source
				WHERE oai_sources.id =".$_POST['id']."
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

$content .= "			<div style=\"display: none;\">\n";
$content .= "				<input type=\"hidden\" id=\"limit\" value=\"20\"/>\n";
$content .= "				<input type=\"hidden\" id=\"status\" value=\"-1\"/>\n";
$content .= "				<input type=\"hidden\" id=\"type\" value=\"-1\"/>\n";
$content .= "				<input type=\"hidden\" id=\"id\" value=\"".$_POST['id']."\"/>\n";
$content .= "			</div>\n";
$content .= "			<p style=\"text-align: right; margin-top: -20px;\"><input type=\"button\" value=\" Zur Startseite\" onclick=\"gotoStart()\"></input></p>\n";
$content .= "			<h2>OAI-Quelle anzeigen</h2>\n";
$content .= "			<h3>Allgemeine Informationen</h3>\n";
$content .= "			<table border=\"0\" width=\"100%\" cellpadding=\"2px\">\n";
$content .= "				<colgroup>\n";
$content .= "				    <col width=\"23%\" />\n";
$content .= "				    <col width=\"45%\" />\n";
$content .= "				    <col width=\"32%\" />\n";
$content .= "			 	</colgroup>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Name:</td>\n";
// Name
$content .= "					<td align=\"left\" class=\"table_field_data\">".( htmlentities($oai_source_data['name'], ENT_QUOTES, 'UTF-8') )."</td>\n";
// Fehlerzeichen
$content .= "					<td rowspan=\"16\" align=\"left\" style=\"vertical-align: middle;\">";
if ($oai_source_status > 0) {
	$content .= "<a href=\"#logs\"><img title=\"Es liegen Fehlermeldungen für diese OAI-Quelle vor. Bitte klicken um zu den Logs zu springen.\" alt=\"Es liegen Fehlermeldungen für diese OAI-Quelle vor. Bitte klicken um zu den Logs zu springen.\" src=\"./images/big_error.png\" /></a>";
}
$content .= "</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Request URL:</td>\n";
// URL
$content .= "					<td align=\"left\" class=\"table_field_data\"><a class=\"oai_set_link\" href=\"".$oai_source_data['url']."?verb=Identify\">".( htmlentities($oai_source_data['url'], ENT_QUOTES, 'UTF-8') )."</a></td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">ID:</td>\n";
// ID
$content .= "					<td align=\"left\" class=\"table_field_data\">".$oai_source_data['id']."</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Hinzugefügt:</td>\n";
// Added
$content .= "					<td align=\"left\" class=\"table_field_data\">".$oai_source_data['added']."</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Land:</td>\n";
// Land
$content .= "					<td align=\"left\" class=\"table_field_data\">".$oai_source_data['country_name']."</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td></td>\n";
$content .= "					<td></td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Aktiv:</td>\n";
// Harvest-Status
$content .= "					<td align=\"left\" class=\"table_field_data\">";
if ($oai_source_data['active']) {
	$content .= "<img title=\"OAI-Quelle wird geharvested\" alt=\"OAI-Quelle wird geharvested\" src=\"./images/ok.png\" />";
} else {
	$content .= "<img src=\"./images/not_ok.png\" alt=\"OAI-Quelle wird nicht geharvested\" title=\"OAI-Quelle wird nicht geharvested\" />";
}
$content .= "</td>\n";
$content .= "				</tr>\n";

$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Neuindexierung:</td>\n";
// Neuindexierung
$content .= "					<td align=\"left\" class=\"table_field_data\">";
if ($oai_source_data['reindex']) {
	$content .= "<img title=\"OAI-Quelle wird beim nächsten Harvesten komplett neu indexiert\" alt=\"OAI-Quelle wird beim nächsten Harvesten komplett neu indexiert\" src=\"./images/ok.png\" />";
} else {
	$content .= "<img src=\"./images/not_ok.png\" alt=\"OAI-Quelle ist nicht zur Neuindexierung markiert\" title=\"OAI-Quelle ist nicht zur Neuindexierung markiert\" />";
}
$content .= "</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td></td>\n";
$content .= "					<td></td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Geharvestet ab:</td>\n";
// From
$content .= "					<td align=\"left\" class=\"table_field_data\">".( is_null($oai_source_data['from']) ? "Für diese Quelle ist kein Startzeitpunkt festgelegt." : $oai_source_data['from'] )."</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Harvest-Rhythmus:</td>\n";
// Harvest-Rhytmus
$content .= "					<td align=\"left\" class=\"table_field_data\">".( $oai_source_data['harvest_period'] > 1 ? "Alle ".$oai_source_data['harvest_period']." Tage" : "täglich")."</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Letztes erfolgreiches Harvesten:</td>\n";
// Letztes erfolgreiches Harvesten
$content .= "					<td align=\"left\" class=\"table_field_data\">";
if (!empty($oai_source_data['last_harvested'])) {
	$content .= $oai_source_data['last_harvested'];
} else {
	$content .= "Diese Quelle wurde noch nicht geharvested.";
}
$content .= "</td>\n";
$content .= "				</tr>\n";

$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Letztes erfolgreiches Indexieren:</td>\n";
// Letztes erfolgreiches Indexieren
$content.= "					<td align=\"left\" class=\"table_field_data\">";
if (!empty($oai_source_data['last_indexed'])) {
	$content .= $oai_source_data['last_indexed'];
} else {
	$content .= "Diese Quelle wurde noch nicht indexiert.";
}
$content .= "</td>\n";		
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Nächstes Harvesten:</td>\n";
// Nächstes Harvesten
$content .= "					<td align=\"left\" class=\"table_field_data\">".( $oai_source_data['next_harvest'] != NULL ? $oai_source_data['next_harvest'] : utf8_encode(strftime('%A, %d. %B %Y', time()+86400)) )."</td>\n";
$content .= "				</tr>\n";
$content .= "				<tr>\n";
$content .= "					<td></td>\n";
$content .= "					<td></td>\n";
$content .= "				</tr>\n";
$content .= "			<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">Anzahl der Indexeinträge:</td>\n";

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

$content .= "					<td align=\"left\" class=\"table_field_data\">".( $index_entry_count >= 0 ? $index_entry_count : "<span style=\"color: red;\">Der Index ist zurzeit nicht erreichbar.</span>" )."</td>\n";
$content .= "				</tr>\n";
// Kommentar
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" style=\"vertical-align: top;\" class=\"table_field_description\">Kommentar:</td>\n";
$content .= "					<td align=\"left\" class=\"table_field_data\" colspan=\"2\">".( empty($oai_source_data['comment']) ? "Kein Kommentar vorhanden." : "<textarea name=\"comment\" cols=\"130\" rows=\"10\" disabled=\"disabled\">".( htmlentities($oai_source_data['comment'], ENT_QUOTES, 'UTF-8') )."</textarea>" )."</td>\n";
$content .= "				</tr>\n";


$content .= "			</table>\n";


// Nachbearbeitung
$content .= "			<h3>Nachbearbeitung</h3>\n";
$content .= "			<table border=\"0\" width=\"100%\" cellpadding=\"2px\">\n";
$content .= "				<colgroup>\n";
$content .= "				    <col width=\"14%\" />\n";
$content .= "				    <col width=\"86%\" />\n";
$content .= "			 	</colgroup>\n";
$content .= "				<tr>\n";
$content .= "					<td align=\"right\" class=\"table_field_description\">dc:date:</td>\n";
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
$content .= "					<td align=\"left\" class=\"table_field_data\">$current_dc_date_postproc_value</td>\n";
$content .= "				</tr>\n";
$content .= "			</table>\n";



$content .= "			<table border=\"0\" width=\"100%\">\n";
$content .= "				<colgroup>\n";
$content .= "				    <col width=\"30%\" />\n";
$content .= "				    <col width=\"30%\" />\n";
$content .= "				   	<col width=\"30%\" />\n";
$content .= "				</colgroup>\n";
$content .= "				<tr>\n";

// Indexierte Elemente
$content .= "					<td align=\"left\" style=\"vertical-align: top;\">\n";
$content .= "						<h3>Indexierte Elemente</h3>\n";
$content .= "						<ul class=\"show_source_lists\">\n";

// Ausgabe der indexierten Elemente in einer Schleife (Erstellung des Arrays s. o.)
foreach($indexed_elements as $element) {
	$content .= "							<li><span style=\"color: #272ec6;\">".$element."</span></li>\n";
}

$content .= "							</ul>\n";
$content .= "						</td>\n";

// Angezeigte Elemente
$content .= "					<td align=\"left\" style=\"vertical-align: top;\">\n";
$content .= "						<h3>Angezeigte Elemente</h3>\n";
$content .= "						<ul class=\"show_source_lists\">\n";

// Ausgabe der angezeigten Elemente in einer Schleife (Erstellung des Arrays s. o.)
foreach($displayed_elements as $element) {
	$content .= "							<li><span style=\"color: #272ec6;\">".$element."</span></li>\n";
}

$content .= "							</ul>\n";
$content .= "						</td>\n";
$content .= "						<td align=\"left\" style=\"vertical-align: top;\">\n";

// Identifier Einstellungen
$content .= "						<h3>Identifier Einstellungen</h3>\n";
$content .= "						<table border=\"0\" width=\"100%\" cellpadding=\"2px\" style=\"margin-left: 4em;\">\n";
$content .= "							<colgroup>\n";
$content .= "							    <col width=\"15%\" />\n";
$content .= "							    <col width=\"85%\" />\n";
$content .= "							 </colgroup>\n";

// Identfier Alternative
$content .= "							<tr>\n";
$content .= "								<td align=\"right\" class=\"table_field_description\">Alternative:</td>\n";
$content .= "								<td align=\"left\" class=\"table_field_data\">";
if ($oai_source_data['identifier_alternative'] == "") {
	$content .= "-";
} else {
	$content .= $oai_source_data['identifier_alternative'];
}
$content .= "</td>\n";
$content .= "							</tr>\n";

// Filter
$content .= "							<tr>\n";
$content .= "								<td align=\"right\" class=\"table_field_description\">Filter:</td>\n";
$content .= "								<td align=\"left\" class=\"table_field_data\">";
if ($oai_source_data['identifier_filter'] == "") {
	$content .= "-";
} else {
	$content .= $oai_source_data['identifier_filter'];
}
$content .= "</td>\n";
$content .= "							</tr>\n";
// Resolver
$content .= "							<tr>\n";
$content .= "								<td align=\"right\" class=\"table_field_description\">Resolver:</td>\n";
$content .= "								<td align=\"left\" class=\"table_field_data\">";
if ($oai_source_data['identifier_resolver'] == "") {
	$content .= "-";
} else {
	$content .= $oai_source_data['identifier_resolver'];
}
$content .= "</td>\n";
$content .= "							</tr>\n";

// Resolver-Filter
$content .= "							<tr>\n";
$content .= "								<td align=\"right\" class=\"table_field_description\">Resolver-Filter:</td>\n";
$content .= "								<td align=\"left\" class=\"table_field_data\">";
if ($oai_source_data['identifier_resolver_filter'] == "") {
	$content .= "-";
} else {
	$content .= $oai_source_data['identifier_resolver_filter'];
}
$content .= "</td>\n";
$content .= "							</tr>\n";

$content .= "						</table>\n";
$content .= "					</td>\n";

$content .= "				</tr>\n";
$content .= "			</table>\n";


// Sets

// Abfrage des Pseudo-Sets
$sql = "SELECT setname, harvest 
		FROM `oai_sets` 
		WHERE setspec LIKE '%allSets%' AND oai_source = ".$_POST['id'];
$result = mysql_query($sql, $db_link);
if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
$oai_pseudoset_data = mysql_fetch_array($result, MYSQL_ASSOC);


// Abfrage Anzahl der Sets einer OAI-Quelle
$sql = "SELECT COUNT(id) 
		FROM `oai_sets` 
		WHERE setspec NOT LIKE '%allSets%' AND oai_source = ".$_POST['id'];
$result = mysql_query($sql, $db_link);
if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
$total_set_count = mysql_result($result, 0);


if (!$oai_pseudoset_data['harvest']) {
	// Es werden einzelne Sets, bzw. ein Set geharvested.
	
	// Abfrage der Sets
	$sql = "SELECT setname, setspec 
			FROM `oai_sets` 
			WHERE setspec NOT LIKE '%allSets%' AND harvest = TRUE AND oai_source = ".$_POST['id'];
	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
	
	$harvested_set_count = mysql_num_rows($result);
	
	$content .= "			<h3>Geharvestete Sets (".$harvested_set_count." von ".$total_set_count.")</h3>\n";

	$content .= "			<table border=\"0\" width=\"100%\">\n";
	$content .= "				<colgroup>\n";
	$content .= "				    <col width=\"1\" />\n";
	$content .= "				    <col width=\"1\" />\n";
	$content .= "				 </colgroup>\n";
	$content .= "				<tr>\n";
	$content .= "					<td style=\"vertical-align: top;\">\n";
	$content .= "						<ul class=\"show_source_lists\">\n";
	
	$second_col = false;
	$i = 0.5;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

		if ($i > $harvested_set_count / 2 && $i != 1 && !$second_col) {
			
			$second_col = true;
			
			$content .= "							</ul>\n";
			$content .= "						</td>\n";
			$content .= "						<td style=\"vertical-align: top;\">\n";	
			$content .= "							<ul class=\"show_source_lists\">\n";
			
		}
		
		$content .="							<li><span style=\"color: #272ec6;\">".htmlspecialchars($row['setname'])." (".htmlspecialchars($row['setspec']).")</span></li>\n";
		$i++;
	}
	
	
	$content .= "						</ul>\n";
	$content .= "					</td>\n";
	$content .= "				</tr>\n";
	
} else {
	
	switch ($oai_pseudoset_data['setname']) {
		
		case "allSets":
			$content .= "			<h3>Geharvestete Sets (<span style=\"font-weight: bold\">∞</span> von ".$total_set_count.")</h3>\n";
			$content .= "			<table border=\"0\" width=\"100%\">\n";
			$content .= "				<colgroup>\n";
			$content .= "				    <col width=\"1\" />\n";
			$content .= "				    <col width=\"1\" />\n";
			$content .= "				 </colgroup>\n";
			$content .= "				<tr>\n";
			$content .= "					<td colspan=\"2\" style=\"vertical-align: top;\">\n";
			$content .= "						<ul class=\"show_source_lists\">\n";
			$content .="							<li><span style=\"color: #272ec6;\">Alle Sets werden geharvested. Zur Ansicht der Sets bitte Quelle zum Editieren öffnen.</span></li>\n";
			$content .= "						</ul>\n";
			$content .= "					</td>\n";
			break;
			
		case "noSetHierarchy":
			$content .= "			<h3>Geharvestete Sets</h3>\n";
			$content .= "			<table border=\"0\" width=\"100%\">\n";
			$content .= "				<colgroup>\n";
			$content .= "				    <col width=\"1\" />\n";
			$content .= "				    <col width=\"1\" />\n";
			$content .= "				 </colgroup>\n";
			$content .= "				<tr>\n";
			$content .= "					<td colspan=\"2\" style=\"vertical-align: top;\">";
			$content .= "						<ul class=\"show_source_lists\">\n";
			$content .="							<li><span style=\"color: #272ec6;\">Diese OAI-Quelle unterstützt keine Sets und wird komplett geharvested.</span></li>\n";
			$content .= "						</ul>\n";
			$content .= "					</td>\n";
			break;
			
		case "noSets":
			$content .= "			<h3>Geharvestete Sets</h3>\n";
			$content .= "			<table border=\"0\" width=\"100%\">\n";
			$content .= "				<colgroup>\n";
			$content .= "				    <col width=\"1\" />\n";
			$content .= "				    <col width=\"1\" />\n";
			$content .= "				 </colgroup>\n";
			$content .= "				<tr>\n";
			$content .= "					<td colspan=\"2\" style=\"vertical-align: top;\">";
			$content .= "						<ul class=\"show_source_lists\">\n";
			$content .="							<li><span style=\"color: #272ec6;\">Diese OAI-Quelle bietet keine Sets an wird komplett geharvested.</span></li>\n";
			$content .= "						</ul>\n";
			$content .= "					</td>\n";
			
			break;
			
			
		default:

	}

	$content .= "				</tr>\n";
}

$content .= "			</table>\n";


// Formular mit den Daten einer evtl. vorgegangenen Suche um nach dort zurückzukehren
$content .= "			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n";
$content .= "				<div>\n";
$content .= "					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n";

// filter_name
$content .= "					<input type=\"hidden\" name=\"filter_name\" value=\"";
$current_filter_name = isset($_POST['filter_name']) ? $_POST['filter_name'] : "";
$content .= $current_filter_name."\"></input>\n";

// filter_url
$content .= "					<input type=\"hidden\" name=\"filter_url\" value=\"";
$current_filter_url = isset($_POST['filter_url']) ? $_POST['filter_url'] : "";
$content .= $current_filter_url."\"></input>\n";

// filter_bool
$content .= "					<input type=\"hidden\" name=\"filter_bool\" value=\"";
$current_filter_bool = isset($_POST['filter_bool']) ? $_POST['filter_bool'] : "AND";
$content .= $current_filter_bool."\"></input>\n";

// sortby
$content .= "					<input type=\"hidden\" name=\"sortby\" value=\"";
$current_sortby = isset($_POST['sortby']) ? $_POST['sortby'] : "name";
$content .= $current_sortby."\"></input>\n";

// sorthow
$content .= "					<input type=\"hidden\" name=\"sorthow\" value=\"";
$current_sorthow = isset($_POST['sorthow']) ? $_POST['sorthow'] : "ASC";
$content .= $current_sorthow."\"></input>\n";

// id
$content .= "					<input type=\"hidden\" name=\"id\" value=\"";
$content .= isset($_POST['id']) ? $_POST['id'] : "none";
$content .= "\"></input>\n";

// start
$content .= "					<input type=\"hidden\" name=\"start\" value=\"";
$current_start = isset($_POST['start']) ? $_POST['start'] : "0";
$content .= $current_start;
$content .= "\"></input>\n";

// limit
$content .= "					<input type=\"hidden\" name=\"limit\" value=\"";
$current_limit = isset($_POST['limit']) ? $_POST['limit'] : 20;
$content .= $current_limit;
$content .= "\"></input>\n";

// show_active
$content .= "					<input type=\"hidden\" name=\"show_active\" value=\"";
$current_show_active = isset($_POST['show_active']) ? $_POST['show_active'] : 0;
$content .= $current_show_active;
$content .= "\"></input>\n";

// show_status
$content .= "					<input type=\"hidden\" name=\"show_status\" value=\"";
$current_show_status = isset($_POST['show_status']) ? $_POST['show_status'] : 0;
$content .= $current_show_status;
$content .= "\"></input>\n";

$content .= "				</div>\n";
// Buttons
$content .= "				<p style=\"text-align: center; margin-top: 25px;\">\n";
$content .= "					<input type=\"submit\" value=\" Editieren\" onclick=\"edit(".$oai_source_data['id'].")\"></input>&nbsp;\n";
$content .= "					<input type=\"submit\" value=\" Löschen\" onclick=\"remove(".$oai_source_data['id'].")\"></input>&nbsp;\n";
$content .= "					<input type=\"submit\" value=\" Zur Trefferliste\" onclick=\"document.forms[0].action = 'index.php#filter'\"></input>\n";
$content .= "				</p>\n";		
$content .= "			</form>\n";
$content .= "			<hr style=\"margin-top:30px; color:#8F0006; width: 50%;\" />\n";
$content .= "			<h3 id=\"logs\" style=\"text-align: center; text-indent: 0;\">Logmeldungen der Quelle</h3>\n";
$content .= "			<p style=\"text-align: center; margin-top: 10px; margin-left: auto; margin-right: auto; color: #424242; background-color: #D8E6B6; width: 45%; padding: 3px;\">\n";
$content .= "				<em>Anzahl der Meldungen:</em>\n";
$content .= "				<select id=\"max_hit_display\" name=\"limit_select\" size=\"1\" onchange=\"navigate(0)\">\n";
$content .= "				<option value=\"5\" >5</option>\n";
$content .= "					<option value=\"20\" selected=\"selected\">20</option>\n";
$content .= "					<option value=\"50\" >50</option>\n";
$content .= "					<option value=\"100\" >100</option>\n";
$content .= "					<option value=\"150\" >150</option>\n";
$content .= "					<option value=\"200\" >200</option>\n";
$content .= "				</select>\n";
$content .= "				&nbsp;&nbsp;\n";
$content .= "				<em>Status:</em>\n";
$content .= "				<select id=\"show_status_select\" size=\"1\" onchange=\"navigate(0)\">\n";
$content .= "					<option value=\"-1\" selected=\"selected\">egal</option>\n";
$content .= "					<option value=\"0\" >OK</option>\n";
$content .= "					<option value=\"1\" >Fehler</option>\n";
$content .= "				</select>\n";
$content .= "				&nbsp;&nbsp;\n";
$content .= "				<em>Typ:</em>\n";
$content .= "				<select id=\"show_type_select\" size=\"1\" onchange=\"navigate(0)\">\n";
$content .= "					<option value=\"-1\" selected=\"selected\">egal</option>\n";
$content .= "					<option value=\"0\" >Harvester</option>\n";
$content .= "					<option value=\"1\" >Indexer</option>\n";
$content .= "				</select>\n";
$content .= "			</p>\n";
$content .= "			<p style=\"text-align: center;\"><input id=\"goto_first_page\" type=\"button\" value=\"Zur 1. Seite\" onclick=\"navigate(0)\" disabled=\"disabled\"></input></p>\n";
$content .= "			<hr style=\"width: 30%; text-align: center; margin-top: 15px;\"/>\n";
$content .= "			<div id=\"log_display\">";

require_once("./classes/log.php");
$log = new log($db_link, -1, -1, 20, 0, $_POST['id']);
$content .= $log->getOutput();	
$content .=	"</div>";

?>
