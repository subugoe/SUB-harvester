<?php

require_once(dirname(__FILE__) . '/oai_set_list.php');

/*
 * Diese Klasse vergleicht die in der zu einer OAI-Quelle in der Datenbank gespeichertern
 * OAI-Sets mit denen einer aktuellen aktuellen Abfrage der OAI-Quelle.
 *
 * Die Klasse bietet verschiedene Ausgabemöglichkeiten.
 */

class oai_set_list_compare extends oai_set_list {
	protected $compared_sets = array();
	protected $pseudo_set = array('setName' => 'allSets', 'setSpec' => 'allSets');

	private function compareSetsForSourceID ($oai_source_id) {
		// Abfrage der in der Datenbank gespeicherten Sets
		$sql = "SELECT setSpec, setName, id, harvest, online
				FROM oai_sets
				WHERE oai_source = " . intval($oai_source_id) . "
				ORDER BY setName ASC";
		$result = mysql_query($sql, $this->owner->db_link);
		if (!$result) {
			$error = new error($this->owner->document);
			$this->owner->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}

		// Neben den mehrdimensionalen Arrays mit setSpec und setName werden
		// flache Arrays mit dem setSpec für die Suche angelegt. Sie besitzen den gleichen Index.
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$db_sets[] = $row;
			$db_sets_setspec[] = $row['setSpec'];
		}
		// Erstellung eines Arrays mit den aktuellen setSpec
		foreach($this->sets as $set) {
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
					$this->compared_sets['unchanged'][count($this->compared_sets['unchanged'])-1] = $this->sets[$search]['setName'];
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
				$this->compared_sets['new'][] = $this->sets[$i];
			}
		}


		// Abfrage Request-url
		$sql = "SELECT url
				FROM oai_sources
				WHERE id = " . intval($oai_source_id);
		$result = mysql_query($sql, $this->owner->db_link);
		if (!$result) {
			$error = new error($this->owner->document);
			$this->owner->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}

		$url = mysql_fetch_array($result, MYSQL_ASSOC);
		$this->url = $url['url'];

		//print_r($db_sets);
		//print_r($this->sets);
		//print_r($db_sets_setspec);
		//print_r($current_sets_setspec);
		//print_r($this->compared_sets);
	}



	public function makeRowForSet ($set, $setType, $setIndex, $active = TRUE) {
		$tr = $this->owner->document->createElement('tr');
		$td = $this->owner->document->createElement('td');
		$tr->appendChild($td);
		$td->setAttribute('class', 'column1');

		// Preview button
		$previewButton = $this->owner->makeInput('button', 'set_' . $setType . '_' . $setIndex . '_preview', 'Vorschau');
		$td->appendChild($previewButton);
		$previewButton->setAttribute('onclick', 'preview("' . $set['setSpec'] . '", "' . $set['setName'] . '", validate_edit)');

		// Link to OAI ListRecords command.
		$a = $this->owner->makeElementWithText('a', 'OAI-XML anzeigen', 'OAILink');
		$td->appendChild($a);
		$a->setAttribute('onclick', 'window.open(this.href, "_blank"); return false;');
		$XMLURL = $this->url . '?verb=ListRecords&metadataPrefix=oai_dc';
		if (array_key_exists('setSpec', $set) && $set['setSpec'] !== 'allSets') {
			$XMLURL .= '&set=' . $set['setSpec'];
		}
		$a->setAttribute('href', $XMLURL);

		$basicValueName = 'sets[' . $setType . '][' . $setIndex . ']';

		// Checkbox
		$checkbox = $this->owner->makeInput('checkbox', $basicValueName . '[harvest]');
		$id = 'set-' . $setType . '-' . $setIndex;
		$checkbox->setAttribute('id', $id);
		$checkbox->setAttribute('onclick', 'validateSets()');
		if (array_key_exists('harvest', $set) && $set['harvest']) {
			$checkbox->setAttribute('checked', 'checked');
		}
		if (!$active) {
			$checkbox->setAttribute('disabled', 'disabled');
		}
		$tr->appendChild($this->owner->makeElementWithContent('td', $checkbox, 'column2'));

		// Set name and spec
		$label = $this->owner->makeLabel($id, $set['setName']);
		$label->appendChild($this->owner->document->createTextNode(' '));
		$specSpan = $this->owner->makeElementWithText('span', '(' . $set['setSpec'] . ')', 'setSpec');
		$label->appendChild($specSpan);

		// Hidden fields with Set information
		$td->appendChild($this->owner->makeInput('hidden', $basicValueName . '[setName]', $set['setName']));
		$td->appendChild($this->owner->makeInput('hidden', $basicValueName . '[setSpec]', $set['setSpec']));
		if (array_key_exists('id', $set)) {
			$td->appendChild($this->owner->makeInput('hidden', $basicValueName . '[id]', $set['id']));
		}
		$tr->appendChild($this->owner->makeElementWithContent('td', $label, 'column3 table_field_description'));

		return $tr;
	}



	public function makeTableForSets ($sets, $setType, $extraClass = '', $active = TRUE, $IDOffset = 0) {
		$table = $this->owner->document->createElement('table');
		$table->setAttribute('class', trim('sets ' . $setType . ' ' . $extraClass));

		if (count($sets) > 0) {
			$thead = $this->owner->document->createElement('thead');
			$table->appendChild($thead);
			$thead->appendChild($this->owner->makeElementWithText('th', 'Info'));
			$thead->appendChild($this->owner->document->createElement('th'));
			$thead->appendChild($this->owner->makeElementWithText('th', 'Name und ID'));

			$tbody = $this->owner->document->createElement('tbody');
			$table->appendChild($tbody);

			foreach ($sets as $setIndex => $set) {
				$tbody->appendChild($this->makeRowForSet($set, $setType, $setIndex + $IDOffset, $active));
			}
		}

		return $table;
	}



	public function makeFilterP () {
		$filterP = $this->owner->document->createElement('p');

		$filterP->appendChild($this->owner->makeLabel('filterField', 'Angezeigte Sets filtern:'));

		$input = $this->owner->makeInput('search');
		$filterP->appendChild($input);
		$input->setAttribute('id', 'filterField');
		$input->setAttribute('placeholder', 'Filter');
		$input->setAttribute('onsearch', 'var myTables = jQuery("table.realSets"); jQuery.uiTableFilter(myTables, this.value);');
		$input->setAttribute('onkeyup', 'var myTables = jQuery("table.realSets"); if (this.value.length >  2) { jQuery.uiTableFilter(myTables, this.value, "Name und ID");}');

		$filterP->appendChild($this->owner->document->createTextNode('Sortieren nach:'));
		$a = $this->owner->makeElementWithText('a', 'Name');
		$filterP->appendChild($a);
		$a->setAttribute('href', '#');
		$a->setAttribute('onclick', 'jQuery("table.realSets").tablesorter({sortList:[[2,0]]}); return false;');

		$filterP->appendChild($this->owner->document->createTextNode(', '));
		$a = $this->owner->makeElementWithText('a', 'ID');
		$filterP->appendChild($a);
		$a->setAttribute('href', '#');
		$a->setAttribute('onclick', 'jQuery("table.realSets").tablesorter({sortList:[[2,0]], textExtraction:function(node) {
			var setSpecs = jQuery("span.setSpec", node);
				if (setSpecs.length > 0) {
					return setSpecs[0].innerHTML;
				}
				return "";
			}}); return false;');

		return $filterP;
	}



	// Gibt die Sets als Tabellen aus.
	public function getTablesForID ($id) {
		$this->compareSetsForSourceID($id);

		$container = $this->owner->document->createElement('div');

		// Pseudo-Set
		$container->appendChild($this->makeTableForSets(Array($this->pseudo_set), 'unchanged'));

		// Filter UI
		$container->appendChild($this->makeFilterP());

		// Neue Sets
		$container->appendChild($this->owner->makeElementWithText('h3', 'Neue Sets (seit dem letzten Besuch)'));
		$container->appendChild($this->makeTableForSets($this->compared_sets['new'], 'new', 'realSets'));

		// Gelöschte Sets
		$container->appendChild($this->owner->makeElementWithText('h3', 'Gelöschte Sets (seit dem letzten Besuch)'));
		$container->appendChild($this->makeTableForSets($this->compared_sets['deleted'], 'deleted', 'realSets'));

		// Unveränderte Sets: bei Index 1 beginnen, das Pseudo-Set hat bereits Index 0
		$container->appendChild($this->owner->makeElementWithText('h3', 'Unveränderte Sets'));
		$container->appendChild($this->makeTableForSets($this->compared_sets['unchanged'], 'unchanged', 'realSets', TRUE, 1));

		return $container;
	}
}

?>
