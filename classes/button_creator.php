<?php

/*
 * Diese Klasse erstellt Klickbuttons. Sie besitzt nur eine Funktion die den
 * HTML-Code für einen Button generiert.
 */

class button_creator {
	
	public function __construct() {
		
	}
	
	// Generiert den Button, 1. Parameter der Text, 2. Parameter das Ziel.
	
	public function createButton($text, $target = "index.php") {
		
		$html = "";
		$html .= "<form method=\"post\" action=\"".$target."\" accept-charset=\"UTF-8\">\n";
		$html .= "	<p><input type=\"submit\" value=\"".$text."\"></input></p>\n";
		$html .= "</form>\n";
		
		return $html;
	}
}

?>