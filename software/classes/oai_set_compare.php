<?php

/*
 * Diese Klasse vergleicht die in der zu einer OAI-Quelle in der Datenbank gespeichertern
 * OAI-Sets mit denen einer aktuellen aktuellen Abfrage der OAI-Quelle.
 *
 * Die Klasse bietet verschiedene Ausgabemöglichkeiten.
 */

class oai_set_compare {

	private $compared_sets = array();
	private $pseudo_set = array();
	private $url = "";

	public function __construct ($current_sets, $oai_source_id, $db_link) {

		// Abfrage der in der Datenbank gespeicherten Sets
		$sql = "SELECT setSpec, setName, id, harvest, online
				FROM oai_sets
				WHERE oai_source = " . intval($oai_source_id) . "
				ORDER BY setName ASC";
		$result = mysql_query($sql, $db_link);
		if (!$result) {
			die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
		}

		// Neben den mehrdimensionalen Arrays mit setSpec und setName werden
		// flache Arrays mit dem setSpec für die Suche angelegt. Sie besitzen den gleichen Index.
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$db_sets[] = $row;
			$db_sets_setspec[] = $row['setSpec'];
		}
		// Erstellung eines Arrays mit den aktuellen setSpec
		foreach($current_sets as $set) {
			$current_sets_setspec[] = $set['setSpec'];
		}

		// compared_sets speichert das Ergebnis des Vergleichs
		$this->compared_sets = array("unchanged" => array(), "new" => array(), "deleted" => array());


		// Vergleich von in der Datenbank vorhanden mit aktuellen
		foreach($db_sets_setspec as $i => $db_setspec) {

			$search = array_search($db_setspec, $current_sets_setspec);
			if ($search !== FALSE) {
				// Gefunden, also unverändert
				$this->compared_sets['unchanged'][] = $db_sets[$i];

				// Wurde dieses Set bereits einmal entfernt (in der Quelle) und taucht jetzt wieder auf...?
				if(!$db_sets[$i]['online']) {
					// ...dann sollte der setName aktualisiert werden, da es wahrscheinlich ist, dass er sich geändert hat
					// setName aktualisieren
					$this->compared_sets['unchanged'][count($this->compared_sets['unchanged'])-1] = $current_sets[$search]['setName'];
				}

			} else {
				// Nicht gefunden, also gelöscht - mit Ausnahme des Pseudosets
				if($db_setspec != "allSets") {
					$this->compared_sets['deleted'][] = $db_sets[$i];
				} else {
					$this->pseudo_set = $db_sets[$i];
				}
			}
		}

		// Vergleich von aktuellem mit in der Datenbank vorhanden
		foreach($current_sets_setspec as $i => $current_setspec) {

			$search = array_search($current_setspec, $db_sets_setspec);
			if ($search === FALSE) {
				// Nicht gefunden, also ein neues Set
				$this->compared_sets['new'][] = $current_sets[$i];
			}
		}


		// Abfrage Request-url

		$sql = "SELECT url
				FROM oai_sources
				WHERE id = " . intval($oai_source_id);
		$result = mysql_query($sql, $db_link);
		if (!$result) {
			die(str_replace("%content%", ($mysq_error_message."<br /><br /><tt>".$sql."</tt><br /><br />führte zu<br /><br /><em>".mysql_error())."</em>", $output));
		}

		$url = mysql_fetch_array($result, MYSQL_ASSOC);
		$this->url = $url['url'];

		//print_r($db_sets);
		//print_r($current_sets);
		//print_r($db_sets_setspec);
		//print_r($current_sets_setspec);
		//print_r($this->compared_sets);

	}

	// Gibt die Sets als Tabellen aus.
	public function getTables() {

		$tables = "";
		$i = 1; // Für label
		$j = 0; // Für Arrays
		$even = false;

		// Pseudo-Set
		$tables .= "
				<table class='sets'>
					<tr>
						<td class='column1'>
							<input type='button' name='set_".$i."_preview' value='Vorschau' onclick='preview()'></input>
							<a href='".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc' onclick='window.open(this.href, \"_blank\"); return false;' class='OAILink'>OAI-XML anzeigen</a>
						</td>
						<td class='column2'>
							<input id='set".$i."' name='sets[unchanged][".$j."][harvest]' type='checkbox' " . ($this->pseudo_set['harvest'] ? "checked='checked' " : "" ) . "onclick='validateSets()'></input>
						</td>
						<td class='column3 table_field_description'>
							<label for='set".$i."'>Komplette OAI-Quelle harvesten</label>
							<input type='hidden' name='sets[unchanged][".$j."][setName]' value='". $this->pseudo_set['setName'] ."'></input>
							<input type='hidden' name='sets[unchanged][".$j."][setSpec]' value='". $this->pseudo_set['setSpec'] ."'></input>
							<input type='hidden' name='sets[unchanged][".$j."][id]' value='". $this->pseudo_set['id'] ."'></input>
						</td>
					</tr>
				</table>";

		$i++;


		$tables .= "
				<p class='filterField'>
					<label for='filterField'>Angezeigte Sets filtern: </label>
					<input id='filterField' type='search' placeholder='Filter' onsearch='var myTables = jQuery(\"table.realSets\"); jQuery.uiTableFilter(myTables, this.value);' onkeyup='var myTables = jQuery(\"table.realSets\"); jQuery.uiTableFilter(myTables, this.value, \"Name und ID\");'></input>
					Sortieren nach:
						<a href='#' onclick='jQuery(\"table.realSets\").tablesorter({sortList:[[2,0]]}); return false;'>Name</a>,
						<a href='#' onclick='jQuery(\"table.realSets\").tablesorter({sortList:[[2,0]], textExtraction: function (node) {
							var setSpecs = jQuery(\"span.setSpec\", node);
							if (setSpecs.length > 0) {
								return setSpecs[0].innerHTML;
							}
							return \"\";
						}}); return false;'>ID</a>
				</p>";


		// Neue Sets
		if (count($this->compared_sets['new']) > 0) {

			$j = 0;

			$tables .= "
				<h4>Neue Sets</h4>
				<table class='sets realSets'>
					<thead>
						<th>Info</th>
						<th></th>
						<th>Name und ID</th>
					</thead>
					<tbody>";

			foreach ($this->compared_sets['new'] as $set) {

				$tables .= "
						<tr>
							<td class='column1'>
								<input type='button' name='set_".$i."_preview' value='Vorschau' onclick=\"preview('". htmlentities(str_replace("'", "\'", $set['setSpec']), ENT_QUOTES, 'UTF-8') ."', '". htmlentities(str_replace("'", "\'", $set['setName']), ENT_QUOTES, 'UTF-8') ."')\"></input>
								<a href='".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc&amp;set=".$set['setSpec']."' onclick='window.open(this.href, \'_blank\'); return false;' class='OAILink'>OAI-XML anzeigen</a>
							</td>
							<td class='column2'>
								<input id='set".$i."' name='sets[new][".$j."][harvest]' type='checkbox' onclick='validateSets()'></input>
							</td>
							<td class='column3 table_field_description'>
								<label for='set".$i."'>".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )."
									<span class='setSpec'>(".$set['setSpec'].")</span>
								</label>
								<input type='hidden' name='sets[new][".$j."][setName]' value='".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )."'></input>
								<input type='hidden' name='sets[new][".$j."][setSpec]' value='".( htmlentities($set['setSpec'], ENT_QUOTES, 'UTF-8') )."'></input>
							</td>
						</tr>";

				$i++;
				$j++;
			}

			$tables .= "
					</tbody>
				</table>";
			$even = false;
		}


		// Gelöschte Sets
		if (count($this->compared_sets['deleted']) > 0) {

			$j = 0;

			$tables .= "
				<h4>Gelöschte Sets</h4>
				<table class='sets realSets'>
					<thead>
						<th>Info</th>
						<th></th>
						<th>Name und ID</th>
					</thead>
					<tbody>";

			foreach ($this->compared_sets['deleted'] as $set) {

				$tables .= "
						<tr>
							<td class='column1'>
								<input type='button' name='set_".$i."_preview' value='Vorschau' onclick=\"preview('". htmlentities(str_replace("'", "\'", $set['setSpec']), ENT_QUOTES, 'UTF-8') ."', '". htmlentities(str_replace("'", "\'", $set['setName']), ENT_QUOTES, 'UTF-8') ."')\"></input>
								<a href='".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc&amp;set=".$set['setSpec']."' onclick='window.open(this.href, \'_blank\'); return false;' class='OAILink'>OAI-XML anzeigen</a>
							</td>
							<td class='column2'>
								<input id='set".$i."' name='sets[deleted][".$j."][harvest]' type='checkbox' ".($set['harvest'] ? "checked='checked' " : "" ) . "onclick='validateSets()'></input>
							</td>
							<td class='column3 table_field_description'>
								<label for='set".$i."'>".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )."
									<span class='setSpec'>(".$set['setSpec'].")</span>
								</label>
								<input type='hidden' name='sets[deleted][".$j."][setName]' value='".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )."'></input>
								<input type='hidden' name='sets[deleted][".$j."][setSpec]' value='".( htmlentities($set['setSpec'], ENT_QUOTES, 'UTF-8') )."'></input>
								<input type='hidden' name='sets[deleted][".$j."][id]' value='".$set['id']."'></input>
							</td>
						</tr>";

				$i++;
				$j++;
			}

			$tables .= "
					</tbody>
				</table>";
			$even = false;
		}

		// Unveränderte Sets

		$j = 1; // Pseudoset hat 0

		$tables .= "
				<h4>Unveränderte Sets</h4>
				<table class='sets realSets'>
					<thead>
						<th>Info</th>
						<th></th>
						<th>Name und ID</th>
					</thead>
					<tbody>";

		foreach ($this->compared_sets['unchanged'] as $set) {

			$tables .= "
						<tr>
							<td class='column1'>
								<input type='button' name='set_".$i."_preview' value='Vorschau' onclick=\"preview('". htmlentities(str_replace("'", "\'", $set['setSpec']), ENT_QUOTES, 'UTF-8') ."', '". htmlentities(str_replace("'", "\'", $set['setName']), ENT_QUOTES, 'UTF-8') ."')\"></input>
									<a href='".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc&amp;set=".$set['setSpec']."' onclick='window.open(this.href, \'_blank\'); return false;' class='OAILink'>OAI-XML anzeigen</a>
							</td>
							<td class='column2'>
								<input id='set".$i."' name='sets[unchanged][".$j."][harvest]' type='checkbox' ".($set['harvest'] ? "checked='checked' " : "" )."onclick='validateSets()'></input>
							</td>
							<td class='column3 table_field_description'>
								<label for='set".$i."'>" . ( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') ).  "
									<span class='setSpec'>(".$set['setSpec'].")</span>
								</label>
								<input type='hidden' name='sets[unchanged][".$j."][setName]' value='".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )."'></input>
								<input type='hidden' name='sets[unchanged][".$j."][setSpec]' value='".( htmlentities($set['setSpec'], ENT_QUOTES, 'UTF-8') )."'></input>
								<input type='hidden' name='sets[unchanged][".$j."][id]' value='".$set['id']."'></input>
							</td>
						</tr>";

			$i++;
			$j++;
		}

		$tables .= "
				</tbody>
			</table>";

		return $tables;
	}
}

?>
