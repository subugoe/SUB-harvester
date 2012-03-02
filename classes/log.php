<?php

/*
 * Diese Klasse stellt Logmeldungen aus der Datenbank dar.
 */


class log {

	private $output = "";

	// Der Konstruktor generiert gemäß der Parameter den Output, der aus einer
	// Tabelle besteht. Diese wird in die aufrufende Webseite eingebunden.
	// Ist eine Quellen-ID angegeben werden nur die Logmeldungen dieser Quelle
	// dargestellt und die Spalte Quelle entfällt.
	public function __construct($db_link, $status, $type, $limit, $start, $oai_source_id = NULL) {

		// Logmeldungen abfragen
		// SQL-Abfrage aufbauen
		$sql = "SELECT SQL_CALC_FOUND_ROWS
					oai_logs.status AS status,
					oai_logs.type AS type,
					DATE_FORMAT(oai_logs.time, '%d.%m.%Y, %k:%i') AS time,
					oai_logs.message AS message,
					oai_sets.setName AS set_name,
					oai_sets.setSpec AS set_spec,
					oai_sources.name AS source_name,
					oai_sources.id AS source_id
				FROM
					(oai_logs INNER JOIN oai_sets ON oai_logs.oai_set = oai_sets.id)
					INNER JOIN oai_sources ON oai_sets.oai_source = oai_sources.id ";

		// WHERE ermitteln
		if (!is_null($oai_source_id) || $status >= 0 || $type >= 0) {
			// WHERE-Klausel ist erforderlich
			$sql .= "WHERE ";

			if(!is_null($oai_source_id)) {
				// Nur Logmeldungen einer Quelle
				$sql .= "oai_sets.oai_source = " . intval($oai_source_id) . " ";
			}

			if($status >= 0) {
				// Wurde schon auf eine Quelle eingegrenzt?
				$sql .= is_null($oai_source_id) ? "" : "AND ";
				$sql .= "oai_logs.status = " . intval($status) . " ";
			}

			if($type >= 0) {
				// Wurde schon auf eine Quelle eingegrenzt?
				$sql .= !is_null($oai_source_id) || $status >= 0 ? "AND " : "";
				$sql .= "oai_logs.type = " . intval($type) . " ";
			}
		}

		$sql .= "ORDER BY oai_logs.time DESC ";
		$sql .=	"LIMIT " . intval($start) . ", " . intval($limit);

		$result = mysql_query($sql, $db_link);
		if (!$result) {
			$this->output = "<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error()."</em>";
		}


		$sql = "SELECT FOUND_ROWS()";
		$count = mysql_query($sql, $db_link);
		if (!$count) {
			$this->output = "<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error()."</em>";
		}
		$total_log_entries = mysql_result($count, 0);

		// Anzahl und Position ausgeben

		if ($total_log_entries == 0) {
			$this->output .= "<p style=\"text-align: center; color: #8F0006; font-size: 14px; font-weight: bold;\">Keine Logs gefunden.</p>\n";

		} else {

			$this->output .= "<p style=\"text-align: center; color: #8f0006; font-size: 14px;\"><em>".($start+1)."</em> bis <em>";
			if ($total_log_entries >= $start + $limit) {
				$this->output .= ($limit + $start);
			} else {
				$this->output .= $total_log_entries;
			}
			$this->output .= "</em> von <em>".$total_log_entries."</em></p>\n";



			// Tabelle bauen
			$this->output .= "			<table id=\"oai_log_list\" border=\"1\" cellpadding=\"3px\" width=\"100%\" rules=\"cols\" style=\"border-color: #b8b8b8; margin-top: 10px;\">\n";
			$this->output .= "				<colgroup>\n";
			$this->output .= "			    	<col width=\"3%\" />\n";
			$this->output .= "				    <col width=\"12%\" />\n";

			$this->output .= is_null($oai_source_id) ? "<col width=\"25%\" />\n" : "";

			$this->output .= "				    <col width=\"25%\" />\n";

			$this->output .= is_null($oai_source_id) ? "<col width=\"35%\" />\n" : "<col width=\"60%\" />\n";

			$this->output .= "				</colgroup>\n";
			// Tabellenkopf
			$this->output .= "				<tr style=\"background-color: #b8b8b8; border-bottom: 1px solid; border-top: 1px solid;\">\n";
			$this->output .= "				 	<th></th>\n";
			$this->output .= "				 	<th>Zeit</th>\n";
			$this->output .= is_null($oai_source_id) ? "<th>Quelle</th>\n" : "";
			$this->output .= "				 	<th>Set</th>\n";
			$this->output .= "				 	<th>Meldung</th>\n";
			$this->output .= "				</tr>\n";


			// Zeilen
			$even = TRUE;

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

				// Zeilenfarbwechsel & Status
				if ($even) {
					$this->output .= "<tr ".( $row['status'] == 1 ? "style=\"color: red;\"" : "" ).">\n";
					$even = FALSE;
				} else {
					$this->output .= "<tr style=\"background-color: #b9c8fe;".( $row['status'] == 1 ? " color: red;" : "" )."\">\n";
					$even = TRUE;
				}

				$this->output .= "					<td style=\"text-align: center;\"><img ".( $row['type'] == 0 ? "src=\"resource/images/harvester.png\" title=\"Harvester\" alt=\"Harvester\"" : "src=\"resources/images/indexer.png\" title=\"Indexer\" alt=\"Indexer\"" )."/></td>\n";
				$this->output .= "					<td style=\"text-align: center;\">".$row['time']."</td>\n";
				$this->output .= is_null($oai_source_id) ? "<td><a class=\"".( $row['status'] == 1 ? "oai_source_link_log_error" : "oai_source_link_log" )."\"href=\"javascript:void(0)\" onclick=\"show(".$row['source_id'].")\">".htmlspecialchars(($row['source_name']))."</td>\n" : "";
				$this->output .= "					<td style=\"text-align: center;\">".( $row['set_spec'] == 'allSets' ? "<span style=\"font-weight: bold\">∞</span>" : htmlspecialchars($row['set_name']) )."</td>\n";
				$this->output .= "					<td>".htmlspecialchars($row['message'])."</td>\n";
				$this->output .= "				</tr>\n";

			}

			$this->output .= "			</table>\n";

			// Navigation

			$this->output .= "			<div style=\"margin-top: 20px; margin-bottom: 75px; margin-left: auto; margin-right: auto; width: 95%;\">\n";
			if ($start != 0) {
				$this->output .= "				<div style=\"text-align: left; float:left;\"><input type=\"button\" value=\"Zurück\" onclick=\"navigate(".($start - $limit).")\"></input></div>\n";
			}

			if ($start + $limit < $total_log_entries) {
				$this->output .= "				<div style=\"text-align: right; float: right;\"><input type=\"button\" value=\"Weiter\" onclick=\"navigate(".($start + $limit).")\"></input></div>\n";
			}

			$this->output .= "			</div>\n";
		}
	}

	// Liefert den Output zurück, die Verarbeitung geschiet bereits im Konstruktor
	public function getOutput() {
		return $this->output;
	}
}
?>
