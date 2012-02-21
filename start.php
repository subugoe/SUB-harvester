<?php

/*
 * Erstellt die Startseite der Konfiguration des OAI-Harvesters
 */


$content .= "
			<div style=\"display: none;\">
				<input type=\"hidden\" id=\"limit\" value=\"20\"/>
				<input type=\"hidden\" id=\"status\" value=\"-1\"/>
				<input type=\"hidden\" id=\"type\" value=\"-1\"/>
					
				<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n
					<input type=\"hidden\" name=\"id\" value=\"\"></input>\n
					<input type=\"hidden\" name=\"do\" value=\"show_oai_source\"></input>\n
				</form>\n
			</div>
			<form method=\"post\" action=\"../index.php\" accept-charset=\"UTF-8\">\n
				<p style=\"text-align: right; margin-top: -20px;\">\n		
					<input type=\"submit\" value=\"EROMM Web Search Startseite\"></input>\n
				</p>\n
			</form>\n
			<h2>OAI-Quellen anzeigen / editieren</h2>
			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n
				<p style=\"text-align: center; margin-top:10px;\">\n
					<input type=\"submit\" value=\"Alle OAI-Quellen anzeigen\"></input>\n
					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n
				</p>\n
			</form>\n
			<form id=\"form_search\" method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\" onsubmit=\"setValues()\">\n
				<div>
					<input type=\"hidden\" name=\"do\" value=\"list_oai_sources\"></input>\n
					<input type=\"hidden\" name=\"filter_url\" value=\"-1\"></input>\n
					<input type=\"hidden\" name=\"filter_name\" value=\"-1\"></input>\n
					<input type=\"hidden\" name=\"filter_bool\" value=\"AND\"></input>\n
				</div>
				<table id=\"filter_table\" border=\"0\" width=\"45%\" style=\"margin-left: auto; margin-right: auto; background-color: #B1D0B9; padding: 3px;\">
					<colgroup>
					    <col width=\"10%\" />
					    <col width=\"85%\" />
					    <col width=\"5%\" />
					 </colgroup>
					<tr>
						<td align=\"right\"><em>Name:</em></td>
						<td align=\"left\"><input name=\"filter_name_input\" type=\"text\" size=\"60\"></input></td>
						<td align=\"left\">
							<select name=\"filter_bool_select\" size=\"1\">
								<option value=\"AND\" selected=\"selected\">und</option>
								<option value=\"OR\">oder</option>
							</select>	
						</td>
					</tr>
					<tr>
						<td align=\"right\"><em>URL:</em></td>
						<td align=\"left\"><input name=\"filter_url_input\" type=\"text\" size=\"60\"></input></td>
						<td align=\"left\"></td>
					</tr>
					<tr>
						<td align=\"center\" colspan=\"3\"><input type=\"submit\" value=\" Suchen\"></input></td>
					</tr>
				</table>
			</form>
			<hr style=\"margin-top:30px; color:#8F0006; width: 50%;\" />\n
			<h2>OAI-Quelle hinzufügen</h2>
			<form method=\"post\" action=\"index.php\" accept-charset=\"UTF-8\">\n
				<p style=\"width: 61%; text-align: center; margin-top:10px; background-color: #B9C8FE; padding: 3px; margin-left: auto; margin-right: auto;\"><em>Request URL: </em><input name=\"add_oai_source\" type=\"text\" size=\"65\"/>\n
					<input type=\"submit\" value=\" OAI-Quelle hinzufügen\"></input>\n
					<input type=\"hidden\" name=\"do\" value=\"add_oai_source\"></input>\n
				</p>\n
			</form>\n
			<hr style=\"margin-top:30px; color:#8F0006; width: 50%;\" />\n
			<h2>Logs</h2>
			<p style=\"text-align: center; margin-top: 10px; margin-left: auto; margin-right: auto; color: #424242; background-color: #D8E6B6; width: 45%; padding: 3px;\">
				<em>Anzahl der Meldungen:</em>
				<select id=\"max_hit_display\" name=\"limit_select\" size=\"1\" onchange=\"navigate(0)\">
					<option value=\"5\" >5</option>
					<option value=\"20\" selected=\"selected\">20</option>

					<option value=\"50\" >50</option>
					<option value=\"100\" >100</option>
					<option value=\"150\" >150</option>
					<option value=\"200\" >200</option>
				</select>
				&nbsp;&nbsp;
				<em>Status:</em>
				<select id=\"show_status_select\" size=\"1\" onchange=\"navigate(0)\">

					<option value=\"-1\" selected=\"selected\">egal</option>
					<option value=\"0\" >OK</option>
					<option value=\"1\" >Fehler</option>
				</select>
				&nbsp;&nbsp;
				<em>Typ:</em>
				<select id=\"show_type_select\" size=\"1\" onchange=\"navigate(0)\">

					<option value=\"-1\" selected=\"selected\">egal</option>
					<option value=\"0\" >Harvester</option>
					<option value=\"1\" >Indexer</option>
				</select>
			</p>
			<p style=\"text-align: center;\"><input id=\"goto_first_page\" type=\"button\" value=\"Zur 1. Seite\" onclick=\"navigate(0)\" disabled=\"disabled\"></input></p>
			<hr style=\"width: 30%; text-align: center; margin-top: 15px;\"/>
			<div id=\"log_display\">";

require_once(dirname(__FILE__) . '/classes/log.php');

$log = new log($db_link, -1, -1, 20, 0);
$content .= $log->getOutput();
			
$content .=	"</div>";

?>
