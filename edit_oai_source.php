<?php

/*
 * Erstellt ein Formular zum Editieren einer OAI-Quelle,
 * die sich bereits in der Datenbank befindet.
 */


if ($_POST['id'] != "") {
	
	/*
	 * Prüfung ob der Datensatz nicht gesperrt ist.
	 * Dazu dient die Datenbanktabelle oai_source_edit_sessions
	 */
	
	// Wurde das Formular bereits mit einem Token aufgerufen (z. B. Zurück bei Löschen)
	// diesen Token übernehmen.
	if(array_key_exists('edit_id', $_POST)) {
		// Es gibt einen Token
		$token = $_POST['edit_id'];
	} else {
		// Es gibt keinen Token, Datenbank prüfen
		// Abfrage der Tabelle
		$sql = "SELECT CAST((NOW() - timestamp) AS SIGNED) AS seconds_alive , MD5(timestamp) as token 
				FROM `oai_source_edit_sessions` 
				WHERE oai_source = ".$_POST['id'];
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		
		if (mysql_num_rows($result) == 0) {
			// Es gibt keine Session zu diesem Datensatz
			// Ein Sperreintrag wird erstellt
			$sql = "INSERT INTO `oai_source_edit_sessions` (
						`oai_source` , `timestamp`
					)
					VALUES (
						'".$_POST['id']."',	NOW()
					)";
			$result = mysql_query($sql, $db_link);
			if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		
			// Token abfragen
			$sql = "SELECT MD5(timestamp) as token 
					FROM `oai_source_edit_sessions` 
					WHERE oai_source = ".$_POST['id'];
			$result = mysql_query($sql, $db_link);
			if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
			$session_data = mysql_fetch_array($result, MYSQL_ASSOC);
			
			$token = $session_data['token'];
		
		} else {
			// Es gibt bereits eine Session
			
			$session_data = mysql_fetch_array($result, MYSQL_ASSOC);
			
			if ($session_data['seconds_alive'] > 3600) {
				// Die Session ist aber abgelaufen
				
				// Den Timestamp der Session aktualiseren
				$sql = "UPDATE `oai_source_edit_sessions` 
						SET timestamp = NOW() 
						WHERE oai_source = ".$_POST['id'];
				$result = mysql_query($sql, $db_link);
				if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
				
				// Token abfragen
				$sql = "SELECT MD5(timestamp) as token 
						FROM `oai_source_edit_sessions` 
						WHERE oai_source = ".$_POST['id'];
				$result = mysql_query($sql, $db_link);
				if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
				$session_data = mysql_fetch_array($result, MYSQL_ASSOC);
				
				$token = $session_data['token'];	
			
			} else {
				// Der Datensatz ist gesperrt
				$token = false;
			}
		}
	}
	
	
	
	// Header
	$content .= "<form method=\"post\" action=\"index.php\" onsubmit=\"return validate()\" accept-charset=\"UTF-8\">\n";
	$content .= "	<div>";
	
	
	// do
	$content .= "		<input type=\"hidden\" name=\"do\" value=\"update_oai_source\"></input>\n";
	// edit_id
	$content .= "		<input type=\"hidden\" name=\"edit_id\" value=\"".$_POST['id']."\"></input>\n";
	// edit_token
	$content .= "		<input type=\"hidden\" name=\"edit_token\" value=\"".$token."\"></input>\n";
	// edito_abort
	$content .= "		<input type=\"hidden\" name=\"edit_abort\" value=\"0\"></input>\n";
	$content .= "	</div>";
	
	$content .= "	<p style=\"text-align: right; margin-top: -20px;\"><input type=\"submit\" value=\" Zur Startseite\" onclick=\"document.forms[0].elements['edit_abort'].value = 1;\"></input></p>\n";
	$content .= "	<h2>OAI-Quelle editieren</h2>";
	
	// Ausgabe generieren
	// Kann der Datensatz editiert werden?
	
	if ($token) {
		// Datensatz ist nicht gesperrt.
		// Abfrage der Informationen zur Quelle aus der Datenbank (es werden fast alle Felder gebraucht => "*")
		$sql = "SELECT * 
				FROM oai_sources 
				WHERE id =".$_POST['id']; 
		$result = mysql_query($sql, $db_link);
		if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
		$oai_source_data = mysql_fetch_array($result, MYSQL_ASSOC);
		
		$content .= "<p style=\"text-align: center;\">";
		// Hinweis, bzw. Anzeigen einer vollständigen Neuindexierung
		if($oai_source_data['reindex']) {
			$content .= "<span style=\"color: red;\">Diese Quelle wird augrund von Änderungen an den Einstellungen beim nächsten Harvesten komplett neu indexiert</span>";
		} else {
			$content .= "Achtung: Änderungen an der Indexierung (auch Land) führen zu einer kompletten Neuindexierung der OAI-Quelle.";
		}
		$content .= "</p>";		 
	
		// Allgemeine Einstellungen
		
		$content .= "				<h3>Allgemeine Einstellungen</h3>\n";
		$content .= "				<table border=\"0\" width=\"100%\">\n";
		$content .= "					<colgroup>\n";
		$content .= "					    <col width=\"15%\" />\n";
		$content .= "					    <col width=\"85%\" />\n";
		$content .= "					 </colgroup>";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_name\" >Name:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"name\" type=\"text\" id=\"config_name\" size=\"100\" maxlength=\"250\" value=\"".( htmlentities($oai_source_data['name'], ENT_QUOTES, 'UTF-8') )."\" /></td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\" >URL:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"url\" type=\"text\" size=\"100\" maxlength=\"250\" readonly=\"readonly\" value=\"".( htmlentities($oai_source_data['url'], ENT_QUOTES, 'UTF-8') )."\"/></td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_country\">Land:</td>\n";
		$content .= "						<td align=\"left\">\n";
		
		require_once("./classes/country_parser.php");
		$countries = new country_parser($db_link);
		$content .= $countries->getSelect($oai_source_data['country_code']);			
		
		$content .= "						</td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_from\">Harvesten ab:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"from\" id=\"config_from\" type=\"text\" size=\"10\" maxlength=\"10\" value=\"".( $oai_source_data['from'] == "0000-00-00" ? "" : $oai_source_data['from'] )."\"/></td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\" id=\"label_harvest\">Harvest-Rhythmus:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"harvest_period\" id=\"config_harvest\" type=\"text\" size=\"3\" maxlength=\"3\" value=\"".$oai_source_data['harvest_period']."\"/> (Tage)</td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>";
		$content .= "						<td align=\"right\" valign=\"middle\" class=\"table_field_description\"><label for=\"harvest\">Aktiv:</label></td>\n";
		$content .= "						<td align=\"left\" valign=\"middle\"><input id=\"harvest\" name=\"active\" type=\"checkbox\" ".($oai_source_data['active'] ? "checked=\"checked\" " : "" )."/></td>\n";
		$content .= "					</tr>";
		$content .= "					<tr>";
		$content .= "						<td align=\"right\" valign=\"middle\" class=\"table_field_description\"><label for=\"harvest\">Kommentar:</label></td>\n";
		$content .= "						<td align=\"left\" valign=\"middle\"><textarea name=\"comment\" cols=\"75\" rows=\"10\">".( htmlentities($oai_source_data['comment'], ENT_QUOTES, 'UTF-8') )."</textarea></td>\n";
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
		
		
		
		// Einstellungen zur Verlinkung
		
		$content .= "				<h3>Verlinkungseinstellungen</h3>\n";
		$content .= "				<table border=\"0\" width=\"100%\">\n";
		$content .= "					<colgroup>\n";
		$content .= "					    <col width=\"20%\" />\n";
		$content .= "					    <col width=\"80%\" />\n";
		$content .= "					 </colgroup>\n";
		$content .= "					<tr>\n";
		$content .= "						<td id=\"label_alternative\" align=\"right\" class=\"table_field_description\">Alternativer Link:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"identifier_alternative\" id=\"config_alternative\" type=\"text\" size=\"100\" maxlength=\"150\" ".($oai_source_data['identifier_alternative'] != "" ? "value=\"".$oai_source_data['identifier_alternative']."\" " : "" )."></input></td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td id=\"label_filter\" align=\"right\" class=\"table_field_description\">Identifier-Filter:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"identifier_filter\" id=\"config_filter\" type=\"text\" size=\"100\" maxlength=\"100\" ".($oai_source_data['identifier_filter'] != "" ? "value=\"".$oai_source_data['identifier_filter']."\" " : "" )."></input></td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\">Identifier-Resolver:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"identifier_resolver\" id=\"config_resolver\" type=\"text\" size=\"100\" maxlength=\"100\" ".($oai_source_data['identifier_resolver'] != "" ? "value=\"".$oai_source_data['identifier_resolver']."\" " : "" )."></input></td>\n";
		$content .= "					</tr>\n";
		$content .= "					<tr>\n";
		$content .= "						<td align=\"right\" class=\"table_field_description\">Identifier-Resolver-Filter:</td>\n";
		$content .= "						<td align=\"left\"><input name=\"identifier_resolver_filter\" id=\"config_identifier_resolver\" type=\"text\" size=\"100\" maxlength=\"100\" ".($oai_source_data['identifier_resolver_filter'] != "" ? "value=\"".$oai_source_data['identifier_resolver_filter']."\" " : "" )."></input></td>\n";
		$content .= "					</tr>\n";
		$content .= "				</table>\n";
		
		
		// Geharvestete Sets
		
		$content .= "				<h3>Zu harvestende Sets</h3>\n";
		
		require_once("./classes/oai_listsets_parser.php");
		$sets = new oai_listsets_parser($oai_source_data['url']);
		
		if ($sets->listSetsSuccessful()) {		
			$current_sets = $sets->getSets();
			require_once("./classes/oai_set_compare.php");
			$set_compare = new oai_set_compare($current_sets, $_POST['id'], $db_link);
			$content .= $set_compare->getTables();
		} else {
			$content .= "<p ".( $sets->getErrorCode() == 'noSetHierarchy' ? " id=\"noSetHierarchy\" " : "" )."style=\"color: red;\">".$sets->getErrorMessage()." Es sind keine Änderungen an den Set-Einstellungen möglich.</p>";
			$content .= $sets->getSetTableRows();
		}	
		
		
		// Speichern- & Abbrechen-Button
		
		$content .= "			<p style=\"text-align: center; margin-top: 25px;\"><input type=\"submit\" value=\" Abbrechen\" onclick=\"document.forms[0].elements['edit_abort'].value = 1;\"></input>&nbsp;&nbsp;<input ".( $sets->listSetsSuccessful() | $sets->getErrorCode() == 'noSetHierarchy' ? "" : "disabled=\"disabled\""  )." type=\"submit\" value=\" Speichern\"></input></p>";
		//$content .= "		</form>\n";
		
	} else {
		// Datensatz ist gesperrt.
		
		$content .= "			<p>Der Datensatz wird gerade von einem anderen Benutzer editiert und ist deshalb gesperrt.</p>";
	}

	// Zurück zur Trefferliste
	//$content .= "			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n";
	$content .= "				<div>\n";
	//$content .= "					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n";
	
	// from
	if ($token) {
		$content .= "				<input type=\"hidden\" id=\"current_from\" name=\"current_from_db\" value=\"".$oai_source_data['from']."\" />";
	}
	
	// new_from_day_before
	$content .= "				<input type=\"hidden\" id=\"new_from_day_before_id\" name=\"new_from_day_before\" value=\"\" />";

	// filter_name
	$content .= "				<input type=\"hidden\" name=\"filter_name\" value=\"";
	$current_filter_name = isset($_POST['filter_name']) ? $_POST['filter_name'] : "";
	$content .= $current_filter_name."\"></input>\n";
	
	// filter_url
	$content .= "				<input type=\"hidden\" name=\"filter_url\" value=\"";
	$current_filter_url = isset($_POST['filter_url']) ? $_POST['filter_url'] : "";
	$content .= $current_filter_url."\"></input>\n";
	
	// filter_bool
	$content .= "				<input type=\"hidden\" name=\"filter_bool\" value=\"";
	$current_filter_bool = isset($_POST['filter_bool']) ? $_POST['filter_bool'] : "AND";
	$content .= $current_filter_bool."\"></input>\n";
	
	// sortby
	$content .= "				<input type=\"hidden\" name=\"sortby\" value=\"";
	$current_sortby = isset($_POST['sortby']) ? $_POST['sortby'] : "name";
	$content .= $current_sortby."\"></input>\n";
	
	// sorthow
	$content .= "				<input type=\"hidden\" name=\"sorthow\" value=\"";
	$current_sorthow = isset($_POST['sorthow']) ? $_POST['sorthow'] : "ASC";
	$content .= $current_sorthow."\"></input>\n";
	
	// id
	$content .= "				<input type=\"hidden\" id=\"oai_repository_id\" name=\"id\" value=\"";
	$content .= isset($_POST['id']) ? $_POST['id'] : "none";
	$content .= "\"></input>\n";
	
	// start
	$content .= "				<input type=\"hidden\" name=\"start\" value=\"";
	$current_start = isset($_POST['start']) ? $_POST['start'] : "0";
	$content .= $current_start;
	$content .= "\"></input>\n";
	
	// limit
	$content .= "				<input type=\"hidden\" name=\"limit\" value=\"";
	$current_limit = isset($_POST['limit']) ? $_POST['limit'] : 20;
	$content .= $current_limit;
	$content .= "\"></input>\n";
	
	// show_active
	$content .= "				<input type=\"hidden\" name=\"show_active\" value=\"";
	$current_show_active = isset($_POST['show_active']) ? $_POST['show_active'] : 0;
	$content .= $current_show_active;
	$content .= "\"></input>\n";
	
	// show_status
	$content .= "				<input type=\"hidden\" name=\"show_status\" value=\"";
	$current_show_status = isset($_POST['show_status']) ? $_POST['show_status'] : 0;
	$content .= $current_show_status;
	$content .= "\"></input>\n";
	
	if ($token) {
		// edit_id
		$content .= "				<input type=\"hidden\" name=\"edit_id\" value=\"".$_POST['id']."\"></input>\n";
		// token
		$content .= "				<input type=\"hidden\" name=\"edit_token\" value=\"".$token."\"></input>\n";
	}
	
	$content .= "			</div>\n";
	$content .= "				<p style=\"text-align: center; margin-top: 5px;\">\n";
	$content .= "					<input type=\"submit\" value=\" Zur Trefferliste\" onclick=\"document.forms[0].action = 'index.php#filter';document.forms[0].elements['do'].value = 'list_oai_sources'\"></input>&nbsp;\n";
	if ($token) {
		$content .= "					<input type=\"submit\" value=\" Löschen\" onclick=\"remove(".$oai_source_data['id'].")\"></input>\n";
	}
	$content .= "				</p>\n";		
	$content .= "			</form>\n";
	
	
	
} else {
	$content .= "<meta http-equiv=\"refresh\" content=\"0; URL=./index.php\" />";
}

?>