<?php

/*
 * Erstellt eine Liste der OAI-Quellen mit verschiedenen Angaben.
 */


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

$content .= "<p style=\"text-align: right; margin-top: -20px;\"><input type=\"button\" value=\" Zur Startseite\" onclick=\"gotoStart()\"></input></p>\n";
$content .= "<h2>OAI-Quellen</h2>\n";
$content .= "<p style=\"text-align:center; margin-top: 30px;\">";

// Abfrage der Anzahl der OAI-Quellen
$sql = "SELECT COUNT('id') FROM oai_sources";
$result = mysql_query($sql, $db_link);
if (!$result) {
	die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
}
$count_oai_sources = mysql_result($result, 0);

// Abfrage der Anzahl der Sets, die Pseudosets "allSets" und "noSetSupport" werden ignoriert)
$sql = "SELECT COUNT('id') AS count_oai_sets FROM oai_sets WHERE NOT setspec LIKE '%allSets%'";
$result = mysql_query($sql, $db_link);
if (!$result) {
	die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
}
$count_oai_sets = mysql_result($result, 0);

// Kein Mechanismus zur Unterscheidung von Plural und Singular - da sollten immer mehr als 1 Quelle, bzw. mehr als 1 Set drin sein...
$content .= "Zurzeit befinden sich insgesamt <em>".$count_oai_sources." OAI-Quellen</em> mit <em>".$count_oai_sets." Sets</em> in der Datenbank.<br />\n";



// Abfrage der Anzahl der aktiven OAI-Quellen
$sql = "SELECT COUNT('id') AS count_active_oai_sources FROM oai_sources WHERE active = 1";
$result = mysql_query($sql, $db_link);
if (!$result) {
	die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
}
$count_active_oai_sources = mysql_result($result, 0);

// Abfrage der Anzahl der geharvesteten Sets
$sql = "SELECT COUNT('id') AS count_oai_sets FROM oai_sets WHERE harvest = 1 AND NOT (setspec LIKE '%allSets%' OR setspec LIKE '%noSetSupport%')";
$result = mysql_query($sql, $db_link);
if (!$result) {
	die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
}
$count_harvested_oai_sets = mysql_result($result, 0);

$content .= "Aus den <em>".$count_active_oai_sources." aktiven OAI-Quellen</em> werden <em>".$count_harvested_oai_sets." Sets</em> geharvestet.\n</p>\n";


// Optionen für die Listenanzeige
$content .= "			<form method=\"post\" action=\"index.php\" id=\"show_oai_sources\" accept-charset=\"UTF-8\">\n";
$content .= "				<div>\n";
$content .= "					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n";

// filter_name
$content .= "					<input type=\"hidden\" name=\"filter_name\" value=\"";
$current_filter_name = isset($_POST['filter_name']) ? mysql_real_escape_string($_POST['filter_name']) : "";
$content .= $current_filter_name."\"></input>\n";

// filter_url
$content .= "					<input type=\"hidden\" name=\"filter_url\" value=\"";
$current_filter_url = isset($_POST['filter_url']) ? mysql_real_escape_string($_POST['filter_url']) : "";
$content .= $current_filter_url."\"></input>\n";

// filter_bool
$content .= "					<input type=\"hidden\" name=\"filter_bool\" value=\"";
$current_filter_bool = isset($_POST['filter_bool']) ? mysql_real_escape_string($_POST['filter_bool']) : "AND";
$content .= $current_filter_bool."\"></input>\n";

// sortby
$content .= "					<input type=\"hidden\" name=\"sortby\" value=\"";
$current_sortby = isset($_POST['sortby']) ? mysql_real_escape_string($_POST['sortby']) : "name";
$content .= $current_sortby."\"></input>\n";

// sorthow
$content .= "					<input type=\"hidden\" name=\"sorthow\" value=\"";
$current_sorthow = isset($_POST['sorthow']) ? mysql_real_escape_string($_POST['sorthow']) : "ASC";
$content .= $current_sorthow."\"></input>\n";

// id
$content .= "					<input type=\"hidden\" name=\"id\" value=\"";
$content .= isset($_POST['id']) ? mysql_real_escape_string($_POST['id']) : "none";
$content .= "\"></input>\n";

// start
$content .= "					<input type=\"hidden\" name=\"start\" value=\"";
$current_start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$content .= $current_start;
$content .= "\"></input>\n";

// limit
$content .= "					<input type=\"hidden\" name=\"limit\" value=\"";
$current_limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
$content .= $current_limit;
$content .= "\"></input>\n";

// show_active
$content .= "					<input type=\"hidden\" name=\"show_active\" value=\"";
$current_show_active = isset($_POST['show_active']) ? intval($_POST['show_active']) : 0;
$content .= $current_show_active;
$content .= "\"></input>\n";

// show_status
$content .= "					<input type=\"hidden\" name=\"show_status\" value=\"";
$current_show_status = isset($_POST['show_status']) ? intval($_POST['show_status']) : 0;
$content .= $current_show_status;
$content .= "\"></input>\n";


// Die Optionen sind nur auf der ersten Seite aktive
$options_disabled = $current_start == 0 ? "" : " disabled=\"disabled\"";

$content .= "				</div>\n";
$content .= "				<h3 id=\"list_oai_sources_drilldown\" style=\"margin-top: 25px;\"><a name=\"filter\">Filter:</a></h3>\n";
$content .= "					<table id=\"filter_table\" border=\"0\" width=\"45%\" style=\"margin-left: auto; margin-right: auto; background-color: #B1D0B9; padding: 3px;\">\n";
$content .= "						<colgroup>\n";
$content .= "						    <col width=\"10%\" />\n";
$content .= "						    <col width=\"85%\" />\n";
$content .= "						    <col width=\"5%\" />\n";
$content .= "						 </colgroup>\n";
$content .= "						<tr>\n";
$content .= "							<td align=\"right\"><em>Name:</em></td>\n";

$content .= "							<td align=\"left\"><input name=\"filter_name_input\" type=\"text\" size=\"60\"".$options_disabled." value=\"".$current_filter_name."\"></input></td>\n";

$content .= "							<td align=\"left\">\n";
$content .= "								<select name=\"filter_bool_select\" size=\"1\"".$options_disabled.">\n";


// filter bool : select Dynamisierung
// mögliche Wertepaare - Für Änderungen hier im Array eintragen.
$filter_bool_options =
	array(
		0 => array('value' => "AND", 'label' => "und"),
		1 => array('value' => "OR", 'label' => "oder")
	);

// Select-elemente erstellen
foreach($filter_bool_options as $option) {
	$content .= "						<option value=\"".$option['value']."\" ";
	if($option['value'] == $current_filter_bool) {
		$content .= "selected=\"selected\"";
	}
	$content .= ">".$option['label']."</option>\n";
}


$content .= "								</select>\n";
$content .= "							</td>\n";
$content .= "						</tr>\n";
$content .= "						<tr>\n";
$content .= "							<td align=\"right\"><em>URL:</em></td>\n";

$content .= "							<td align=\"left\"><input name=\"filter_url_input\" type=\"text\" size=\"60\"".$options_disabled." value=\"".$current_filter_url."\"></input></td>\n";

$content .= "							<td align=\"left\"></td>\n";
$content .= "						</tr>\n";
$content .= "						<tr>\n";
$content .= "							<td align=\"center\" colspan=\"3\"><input type=\"button\" value=\" Filter\" onclick=\"filter()\"".$options_disabled."></input></td>\n";
$content .= "						</tr>\n";
$content .= "					</table>\n";
$content .= "				<p style=\"text-align: center; margin-top: 20px; margin-left: auto; margin-right: auto; color: #424242; background-color: #D8E6B6; width: 45%; padding: 3px;\">\n";
$content .= "					<em>Anzahl der Treffer:</em>\n";

$content .= "					<select id=\"max_hit_display\" name=\"limit_select\" size=\"1\" onchange=\"refresh()\"".$options_disabled.">\n";


// limit : select Dynamisierung
// mögliche Werte - Für Änderungen hier im Array eintragen.
$limit_options = array(5, 20, 50, 100, 150, 200);

// ausgewählten Wert ermitteln, ggf. Standardwert setzen
$current_limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;

// Select-elemente erstellen
foreach($limit_options as $option) {
	$content .= "						<option value=\"".$option."\" ";
	if($option == $current_limit) {
		$content .= "selected=\"selected\"";
	}
	$content .= ">".$option."</option>\n";
}


$content .= "					</select>\n";
$content .= "					&nbsp;&nbsp;\n";
$content .= "					<em>Aktiv:</em>";
$content .= "					<select name=\"show_active_select\" size=\"1\" onchange=\"refresh()\"".$options_disabled.">\n";


// show_active : select Dynamisierung
// mögliche Wertepaare - Für Änderungen hier im Array eintragen.
$show_active_options =
	array(
		0 => array('value' => 0, 'label' => "egal"),
		1 => array('value' => 1, 'label' => "aktiv"),
		2 => array('value' => 2, 'label' => "inaktiv")
	);

// ausgewählten Wert ermitteln, ggf. Standardwert setzen
$current_show_active = isset($_POST['show_active']) ? intval($_POST['show_active']) : 0;

// Select-elemente erstellen
foreach($show_active_options as $option) {
	$content .= "						<option value=\"".$option['value']."\" ";
	if($option['value'] == $current_show_active) {
		$content .= "selected=\"selected\"";
	}
	$content .= ">".$option['label']."</option>\n";
}


$content .= "					</select>\n";
$content .= "					&nbsp;&nbsp;\n";
$content .= "					<em>Status:</em>\n";
$content .= "					<select name=\"show_status_select\" size=\"1\" onchange=\"refresh()\"".$options_disabled.">\n";



// show_status : select Dynamisierung
// mögliche Wertepaare - Für Änderungen hier im Array eintragen.
$show_status_options =
	array(
		0 => array('value' => 0, 'label' => "egal"),
		1 => array('value' => 1, 'label' => "OK"),
		2 => array('value' => 2, 'label' => "Fehler")
	);

// ausgewählten Wert ermitteln, ggf. Standardwert setzen
$current_show_status = isset($_POST['show_status']) ? intval($_POST['show_status']) : 0;

// Select-elemente erstellen
foreach($show_status_options as $option) {
	$content .= "						<option value=\"".$option['value']."\" ";
	if($option['value'] == $current_show_status) {
		$content .= "selected=\"selected\"";
	}
	$content .= ">".$option['label']."</option>\n";
}


$content .= "					</select>\n";
$content .= "				</p>\n";

$goto_first_page_disabled = $current_start != 0 ? "" : " disabled=\"disabled\"";
$content .= "				<p style=\"text-align: center;\"><input type=\"button\" value=\"Zur 1. Seite\" onclick=\"gotoFirstPage()\"".$goto_first_page_disabled."></input></p>\n";

$content .= "				<hr style=\"width: 30%; text-align: center; margin-top: 15px;\"/>";


// Template für Abfrage der ausgewählten OAI-Quellen erstellen (wird zum Zählen und für die Daten benötigt).
$sql_query_select_oai_sources_where = "";
$where_set = false;

// Wird nach "aktiv" selektiert?
if($current_show_active > 0) {
	$sql_query_select_oai_sources_where .= " WHERE";
	$where_set = true;

	$condition = $current_show_active == 1 ? "TRUE" : "FALSE";
	$sql_query_select_oai_sources_where .= " oai_sources.active = ".$condition;
	unset($condition); // zur Sicherheit
}

// Wird nach "status" selektiert?
if ($current_show_status > 0) {
	$condition = $current_show_status == 1 ? "NOT IN" : "IN";
	$sql_query_select_oai_sources_where .= $where_set ? " AND" : " WHERE";
	$where_set = true;
	$sql_query_select_oai_sources_where .= " oai_sources.id " . $condition . "
					(SELECT DISTINCT oai_source
					FROM oai_sets
					WHERE harvest_status > 0
					OR index_status > 0)";

	unset($condition); // zur Sicherheit
}

// Gibt es Textfilter?

// Filter: Name
if (strlen($current_filter_name) >= 3) {

	$current_filter_name_single = explode(" ", $current_filter_name);
	$current_filter_name_parsed = "";

	foreach ($current_filter_name_single as $filter_name) {
		if (strlen($filter_name) >= 3) {
			$current_filter_name_parsed .= strlen($current_filter_name_parsed) == 0 ? "" : " ";
			$current_filter_name_parsed .= "+".$filter_name;
		}
	}
}

// Filter: URL
if (strlen($current_filter_url) >= 3) {

	$current_filter_url_single = explode(" ", $current_filter_url);
	$current_filter_url_parsed = "";

	foreach ($current_filter_url_single as $filter_url) {
		if (strlen($filter_url) >= 3) {
			$current_filter_url_parsed .= strlen($current_filter_url_parsed) == 0 ? "" : " AND ";
			$current_filter_url_parsed .= "url LIKE '%" . $filter_url . "%'";
		}
	}
}

// WHERE Bedingung aufbauen

if ((isset($current_filter_name_parsed) ? strlen($current_filter_name_parsed) >= 3 : false) || (isset($current_filter_url_parsed) ? strlen($current_filter_url_parsed) >= 3 : false)) {

	$sql_query_select_oai_sources_where .= $where_set ? " AND (" : " WHERE (";

	// Zum "where" string hinzufügen
	$sql_query_select_oai_sources_where .= (isset($current_filter_name_parsed) ? strlen($current_filter_name_parsed) > 0 : false) ?
		"MATCH (name) AGAINST ('" . $current_filter_name_parsed . "' IN BOOLEAN MODE)" : "";
	$sql_query_select_oai_sources_where .= (isset($current_filter_name_parsed) && isset($current_filter_url_parsed) ? strlen($current_filter_name_parsed) > 0 && strlen($current_filter_url_parsed) > 0 : false) ? " " . $current_filter_bool . " " : "";
	$sql_query_select_oai_sources_where .= (isset($current_filter_url_parsed) ? strlen($current_filter_url_parsed) > 0 : false) ? $current_filter_url_parsed : "";
	$sql_query_select_oai_sources_where .= ")";
}


// Abfrage der Anzahl der ausgewählten OAI-Quellen, JOIN wird wenn möglich übergangen
if($current_show_status > 0) {
	$sql = "SELECT COUNT( DISTINCT oai_sources.id )
			FROM oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source" .
			$sql_query_select_oai_sources_where;
} else {
	$sql = "SELECT COUNT( DISTINCT oai_sources.id )
			FROM oai_sources" .
			$sql_query_select_oai_sources_where;
}
$result = mysql_query($sql, $db_link);
if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}
$count_selected_oai_sources = mysql_result($result, 0);

if($count_selected_oai_sources > 0) {
	// Anzeige der Position
	$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
	$content .= "				<p style=\"text-align: center; color: #8f0006; font-size: 14px;\"><em>".($start+1)."</em> bis <em>";
	if ($count_selected_oai_sources >= $start + $current_limit) {
		$content .= ($current_limit+$start);
	} else {
		$content .= $count_selected_oai_sources;
	}

	$content .= "</em> von <em>".$count_selected_oai_sources."</em></p>\n";

	// Beginn der Listentabelle
	$content .= "				<table id=\"oai_sources_list\" border=\"1\" cellpadding=\"3px\" width=\"100%\" rules=\"cols\" style=\"border-color: #b8b8b8; margin-top: 10px;\">\n";
	$content .= "					<colgroup>\n";
	$content .= "				    <col width=\"375px\" />\n";
	$content .= "				    <col width=\"370px\" />\n";
	$content .= "					    <col width=\"135px\" />\n";
	$content .= "					    <col width=\"90px\" />\n";
	$content .= "					    <col width=\"40px\" />\n";
	$content .= "					    <col width=\"40px\" />\n";
	$content .= "						<col width=\"50px\" />\n";
	$content .= "					 </colgroup>\n";


	// Tabellekopf mit den dynamischen Sortierungen
	$content .= "					<tr style=\"background-color: #b8b8b8; border-top: solid 1px; border-bottom: solid 1px;\">";


	$table_head_sortfield_color = "#ff9242";
	$set_sort = $current_sorthow == "DESC" ? "ASC" : "DESC";

	// name
	if($current_sortby == "name") {
		$content .= "						<th style=\"background-color: ".$table_head_sortfield_color.";\"><a class=\"sort_link\" href=\"javascript:void(0)\" onclick=\"changeSort('name', '".$set_sort."')\">Name</a></th>\n";
	} else {
		$content .= "						<th><a class=\"sort_link\" href=\"javascript:void(0)\" onclick=\"changeSort('name', 'ASC')\">Name</a></th>\n";
	}

	// url
	if($current_sortby == "url") {
		$content .= "						<th style=\"background-color: ".$table_head_sortfield_color."\"><a class=\"sort_link\" href=\"javascript:void(0)\" onclick=\"changeSort('url', '".$set_sort."')\">URL</a></th>\n";
	} else {
		$content .= "						<th><a class=\"sort_link\" href=\"javascript:void(0)\" onclick=\"changeSort('url', 'ASC')\">URL</a></th>\n";
	}

	// Hinzugefügt
	if($current_sortby == "added") {
		$content .= "						<th style=\"background-color: ".$table_head_sortfield_color."\"><a class=\"sort_link\" href=\"javascript:void(0)\" onclick=\"changeSort('added', '".$set_sort."')\">Hinzg.</a></th>\n";
	} else {
		$content .= "						<th><a class=\"sort_link\" href=\"javascript:void(0)\" onclick=\"changeSort('added', 'DESC')\">Hinzg.</a></th>\n";
	}

	$content .= "						<th>Sets</th>\n";
	$content .= "						<th>Akt.</th>\n";
	$content .= "						<th>Sta.</th>\n";
	$content .= "						<th>Edit.</th>\n";
	$content .= "					</tr>\n";


	// Erstellung der einzelnen Tabellenzeilen
	$sql = "";

	$sql = "SELECT
				oai_sources.id AS id,
				oai_sources.url AS url,
				oai_sources.name AS name,
				oai_sources.active AS active,
				DATE_FORMAT(oai_sources.added, '%e.%c.%Y - %k:%i') AS added_view,
				GREATEST(MAX(oai_sets.index_status), MAX(oai_sets.harvest_status)) AS status,
				COUNT(oai_sets.id) AS total_sets,
				SUM(oai_sets.harvest) AS active_sets
			FROM
				oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source"
		.	$sql_query_select_oai_sources_where
		."	GROUP BY oai_sources.id, oai_sources.name, oai_sources.url, oai_sources.active, oai_sources.added"
		."	ORDER BY " . $current_sortby . " " . $current_sorthow
		."	LIMIT " . $current_start . ", " . $current_limit;

	// echo $sql;

	$result = mysql_query($sql, $db_link);
	if (!$result) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}

	$even = TRUE;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

		// Prüfen, die Quelle komplett geharvestet wird.
		$allsets = false;
		// Ist nur nötig, wenn es nur ein "aktives" set gibt.
		if ($row['active_sets'] == 1) {

			$sql = "SELECT setSpec FROM oai_sets WHERE oai_source = " . $row['id'] . " AND harvest = 1";
			$result_allsets = mysql_query($sql, $db_link);
			if (!$result_allsets) { die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));}

			$setSpec = mysql_fetch_array($result_allsets, MYSQL_ASSOC);

			if ($setSpec['setSpec']== 'allSets') {
				$allsets = true;
			}
		}

		// Zeilenfarbwechsel
		if ($even) {
			$content .= "					<tr>\n";
			$even = FALSE;
		} else {
			$content .= "					<tr style=\"background-color: #b9c8fe;\">\n";
			$even = TRUE;
		}

		$content .= "						<td style=\"text-align: left;\"><a class=\"oai_source_link\" href=\"javascript:void(0)\" onclick=\"show(".$row['id'].")\">".htmlspecialchars($row['name'])."</a></td>\n";
		$content .= "						<td style=\"text-align: left;\">".htmlspecialchars($row['url'])."</td>\n";
		$content .= "						<td style=\"text-align: center;\">".$row['added_view']."</td>\n";
		$content .= "						<td style=\"text-align: center;\">".($allsets ? "<span style=\"font-weight: bold\">∞</span>" : $row['active_sets'])." (".(($row['total_sets'])-1).")</td>\n";

		if($row['active']) {
			$content .= "						<td style=\"text-align: center;\"><img src=\"resources/images/ok.png\" alt=\"OAI-Quelle wird geharvested\" title=\"OAI-Quelle wird geharvested\" /></td>\n";
		} else {
			$content .= "						<td style=\"text-align: center;\"><img src=\"resources/images/not_ok.png\" alt=\"OAI-Quelle wird nicht geharvested\" title=\"OAI-Quelle wird nicht geharvested\" /></td>\n";
		}

		if($row['status'] > 0 ) {
			$content .= "						<td style=\"text-align: center;\"><img src=\"resources/images/error.png\" alt=\"Fehler!\" title=\"Fehler!\" /></td>\n";
		} else {
			$content .= "						<td></td>\n";
		}

		$content .= "						<td style=\"text-align: center;\"><input type=\"button\" value=\"Edit\" onclick=\"edit(".$row['id'].")\"></input></td>\n";
		$content .= "					</tr>\n";
	}

	$content .= "				</table>";
	$content .= "			</form>";
	$content .= "			<div style=\"margin-top: 20px; margin-bottom: 75px; margin-left: auto; margin-right: auto; width: 95%;\">\n";

	if ($current_start != 0) {
		$content .= "				<div style=\"text-align: left; float:left;\"><input type=\"button\" value=\"Zurück\" onclick=\"previous()\"></input></div>\n";
	}

	if ($current_start + $current_limit < $count_selected_oai_sources) {
		$content .= "				<div style=\"text-align: right; float: right;\"><input type=\"button\" value=\"Weiter\" onclick=\"next()\"></input></div>\n";
	}

	$content .= "			</div>\n";

} else {
	$content .= "				<p style=\"text-align: center; color: #8f0006; font-size: 14px;\">Keine OAI-Quellen gefunden.</p>\n";
	$content .= "			</form>";
}

?>
