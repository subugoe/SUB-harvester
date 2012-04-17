<?php

/*
 * Diese Klasse parsed die die Länder in der Datenbank.
 */

class country_parser {

	private $owner;

	public function __construct($owner) {
		$this->owner = $owner;
	}


	// Gibt Markup für eine Auswahlliste mit Ländern zurück - die erste Option ist leer.
	public function makeCountriesSelect($selected = "") {
		$sql = "SELECT name_german, code FROM countries ORDER BY name_german ASC";
		$results = mysql_query($sql, $this->owner->db_link);

		$hasSelectedItem = false;
		$options = Array();

		while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			$option = Array('value' => $row['code'], 'label' => $row['name_german']);
			if (strtoupper($selected) == $row['code']) {
				$option['defaultSelection'] = TRUE;
				$hasSelectedItem = true;
			}
			$options[] = $option;
		}

		if (!$hasSelectedItem) {
			array_splice($options, 0, 0, Array(Array('value' => '', 'label' => 'Bitte auswählen', 'defaultSelection' => TRUE)));
		}

		$select = $this->owner->makeSelectWithOptions('country', $options);

		if (!$hasSelectedItem) {
			$select->firstChild->setAttribute('disabled', 'disabled');
		}

		return $select;
	}
}


?>
