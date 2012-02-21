<?php

/*
 * Erstellt das Eingabeformular für eine OAI-Quelle und 
 * fragt dabei Name, Indexierung, Anzeige und Sets der Quelle ab.
 */

require_once("./classes/button_creator.php");


if ($_POST['add_oai_source'] != "") {
	
	$url = trim($_POST['add_oai_source']);
	
	// Ist die OAI URL bereits vorhanden?
	$urlEscaped = mysql_real_escape_string($url);
	$sql = "SELECT `id` FROM `oai_sources` WHERE url=\"$urlEscaped\"";
	$results = mysql_query($sql, $db_link);
	if ($results && mysql_num_rows($results) > 0) {
		$match = mysql_fetch_array($results, MYSQL_ASSOC);
		$content .= "<p>
				Diese OAI-Quelle existiert bereits: Weiterleitung zur Bearbeitungsseite.
			</p>
			<form method=\"post\" id=\"forwarding\" action=\"index.php\" accept-charset=\"UTF-8\">
				<input type=\"hidden\" name=\"id\" value=\"" . $match['id'] . "\">
				<input type=\"hidden\" name=\"do\" value=\"edit_oai_source\">
			</form>
			<script type=\"text/javascript\">
				jQuery(\"form#forwarding\").submit();
			</script>
			";
		//$_POST['id'] = $match['id'];
		//include("./show_oai_source.php");
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
		
			require_once("./classes/oai_identify_parser.php");
			
			$oai_identify = new oai_identify_parser($http_response);
			
			if ($oai_identify->isResponseValid()) {
				
				$content .= "<p style=\"text-align: right; margin-top: -20px;\"><input type=\"button\" value=\" Zur Startseite\" onclick=\"gotoStart()\"></input></p>\n";
				$content .= "<h2>OAI-Quelle hinzufügen</h2>";		 
				$content .= "			<form method=\"post\" action=\"index.php\" onsubmit=\"return validate()\" accept-charset=\"UTF-8\">\n";
	
				// Allgemeine Einstellungen
				
				$content .= "				<h3>Allgemeine Einstellungen</h3>\n";
				$content .= "				<table border=\"0\" width=\"100%\">\n";
				$content .= "					<colgroup>\n";
				$content .= "					    <col width=\"15%\" />\n";
				$content .= "					    <col width=\"85%\" />\n";
				$content .= "					 </colgroup>";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_name\" >Name:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"name\" type=\"text\" id=\"config_name\" size=\"100\" maxlength=\"250\" value=\"".$oai_identify->getRepositoryName()."\" /></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\" >URL:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"url\" type=\"text\" size=\"100\" maxlength=\"250\" readonly=\"readonly\" value=\"".$_POST['add_oai_source']."\"/></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_country\">Land:</td>\n";
				$content .= "						<td align=\"left\">\n";
				
				require_once("./classes/country_parser.php");
				$countries = new country_parser($db_link);
				$content .= $countries->getSelect($tld);
				
				$content .= "						</td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_from\">Harvesten ab:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"from\" id=\"config_from\" type=\"text\" size=\"10\" maxlength=\"10\" /></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_harvest\">Harvest-Rhythmus:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"harvest_period\" id=\"config_harvest\" type=\"text\" size=\"3\" maxlength=\"3\" value=\"7\" /> (Tage)</td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>";
				$content .= "						<td align=\"right\" valign=\"middle\" class=\"table_field_description\"><label for=\"harvest\">Aktiv:</label></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\"><input id=\"harvest\" name=\"active\" type=\"checkbox\" checked=\"checked\"/></td>\n";
				$content .= "					</tr>";
				$content .= "					<tr>";
				$content .= "						<td align=\"right\" valign=\"middle\" class=\"table_field_description\"><label for=\"harvest\">Kommentar:</label></td>\n";
				$content .= "						<td align=\"left\" valign=\"middle\"><textarea name=\"comment\" cols=\"75\" rows=\"10\"></textarea></td>\n";
				$content .= "					</tr>";
				$content .= "				</table>\n";
				
				
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
				$content .= "						<td><input type=\"hidden\" name=\"do\" value=\"save_oai_source\"></input></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td></td>\n";
				$content .= "					</tr>\n";
				$content .= "				</table>\n";			
				
				
				// Einstellungen zur Verlinkung
				
				$content .= "				<h3>Verlinkungseinstellungen</h3>\n";
				$content .= "				<table border=\"0\" width=\"100%\">\n";
				$content .= "					<colgroup>\n";
				$content .= "					    <col width=\"20%\" />\n";
				$content .= "					    <col width=\"80%\" />\n";
				$content .= "					 </colgroup>\n";
				$content .= "					<tr>\n";
				$content .= "						<td id=\"label_alternative\" align=\"right\" class=\"table_field_description\">Alternativer Link:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"identifier_alternative\" id=\"config_alternative\" type=\"text\" size=\"100\" maxlength=\"150\" value=\"" . preg_replace("/(.*\/\/[^\/]*\/).*/", "$1", $_POST['add_oai_source']) . "\"></input></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td id=\"label_filter\" align=\"right\" class=\"table_field_description\">Identifier-Filter:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"identifier_filter\" id=\"config_filter\" type=\"text\" size=\"100\" maxlength=\"100\" value=\"/^http.*/\"></input></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\">Identifier-Resolver:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"identifier_resolver\" id=\"config_resolver\" type=\"text\" size=\"100\" maxlength=\"100\"></input></td>\n";
				$content .= "					</tr>\n";
				$content .= "					<tr>\n";
				$content .= "						<td align=\"right\" class=\"table_field_description\">Identifier-Resolver-Filter:</td>\n";
				$content .= "						<td align=\"left\"><input name=\"identifier_resolver_filter\" id=\"config_identifier_resolver\" type=\"text\" size=\"100\" maxlength=\"100\"></input></td>\n";
				$content .= "					</tr>\n";
				$content .= "				</table>\n";
				
				
				// Geharvestete Sets
				
				$content .= "				<h3>Zu harvestende Sets</h3>\n";
				$content .= "				<table border=\"0\" width=\"auto\" cellpadding=\"4\" rules=\"none\">\n";
				$content .= "					<colgroup>\n";
				$content .= "					    <col width=\"13%\"/>\n";
				$content .= "					    <col width=\"2%\" />\n";
				$content .= "					    <col width=\"auto\"/>\n";
				$content .= "					 </colgroup>\n";
				
				require_once("./classes/oai_listsets_parser.php");
				$sets = new oai_listsets_parser($_POST['add_oai_source']);
				
				if ($sets->listSetsSuccessful()) {		
					$content .= $sets->getSetTableRows();
				} else {
					$content .= $sets->getSetTableRows();
					$content .= "<th colspan=\"3\" align=\"left\" style=\"text-indent: 3em; color: #272EC6;\">".$sets->getErrorMessage()."</th>";
				}
				
				$content .= "				</table>\n";
	
				
				// Speichern- & Abbrechen-Button
				
				$content .= "			<p style=\"text-align: center; margin-top: 25px;\"><input type=\"button\" value=\" Abbrechen\" onclick=\"gotoStart()\"></input>&nbsp;&nbsp;<input type=\"submit\" value=\" Speichern\"></input></p>";
				$content .= "			</form>";
				
				
			} else {
				$button_creator = new button_creator();
				$content .= "			<p class=\"errormsg\">Die OAI-Quelle liefert eine nicht valide Antwort. Sie kann nicht hinzugefügt werden.</p>\n";
				$content .= $button_creator->createButton("Zurück");		
			}
			
		} else {
			$button_creator = new button_creator();
			$content .= "			<p class=\"errormsg\">Die URL ist ungültig oder der Sever nicht erreichbar.</p>\n";
			$content .= $button_creator->createButton("Zurück");
		}
		
		curl_close($ch);
	}
} else {
	$content .= "<meta http-equiv=\"refresh\" content=\"0; URL=./index.php\">";
}

?>
