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



	// Gibt die Sets als Tabellen aus.
	public function makeTablesForComparisonWithID ($id) {
		$this->compareSetsForSourceID($id);

		$container = $this->owner->document->createElement('div');

		// Pseudo-Set
		$container->appendChild($this->makeTableForSets(Array($this->pseudo_set), 'unchanged', 'pseudo'));

		// Filter UI
		$container->appendChild($this->makeFilterP());

		// Neue Sets
		if (count($this->compared_sets['new']) > 0) {
			$container->appendChild($this->owner->makeElementWithText('h3', 'Neue Sets (seit dem letzten Besuch)'));
			$container->appendChild($this->makeTableForSets($this->compared_sets['new'], 'new', 'realSets'));
		}

		// Gelöschte Sets
		if (count($this->compared_sets['deleted']) > 0) {
			$container->appendChild($this->owner->makeElementWithText('h3', 'Gelöschte Sets (seit dem letzten Besuch)'));
			$container->appendChild($this->makeTableForSets($this->compared_sets['deleted'], 'deleted', 'realSets'));
		}

		// Unveränderte Sets: bei Index 1 beginnen, das Pseudo-Set hat bereits Index 0
		if (count($this->compared_sets['unchanged']) > 0) {
			$container->appendChild($this->owner->makeElementWithText('h3', 'Unveränderte Sets'));
			$container->appendChild($this->makeTableForSets($this->compared_sets['unchanged'], 'unchanged', 'realSets', TRUE, 1));
		}

		return $container;
	}



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



}

?>
