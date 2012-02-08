<?php

/*
 * Diese Klasse parsed die die Länder in der Datenbank.
 */

class country_parser {

	private $db_link;
	
	public function __construct($db_link) {
		
		$this->db_link = $db_link;
	}

	
	// Gibt den HTML-Code für eine Auswahlliste zurück - die erste Option ist leer.
	public function getSelect($selected = "") {
		
		$select = "<select name=\"country\" id=\"config_country\" size=\"1\">\n";
		
		if($selected == "") {
			$select .= "<option value=\"\" disabled=\"disabled\" selected=\"selected\">Bitte auswählen</option>\n";
		}
		
		$sql = "SELECT `name_german`, `code` FROM `countries` ORDER BY `name_german` ASC";
		$results = mysql_query($sql, $this->db_link);
		
		while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			
			$select .= "<option ".($selected == $row['code'] ? "selected=\"selected\" " : "" )."value=\"".$row['code']."\">".$row['name_german']."</option>\n";
		}

		$select .= "</select>\n";
		
		return $select;
	}
}


?>