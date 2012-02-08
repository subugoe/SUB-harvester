<?php

/*
 * Diese Klasse parsed die ListSets-Antwort einer OAI-Quelle.
 */

class oai_listsets_parser {
	
	private $sets = array();
	private $error = "";
	private $error_code = "";
	private $url = "";
	
	
	public function __construct ($url) {
		
		$this->url = $url;
		
		do {

			$oai_listsets_parser_ch = curl_init();
			curl_setopt($oai_listsets_parser_ch, CURLOPT_RETURNTRANSFER, 1);
			// Ignoriere SSL-Zertifikate bei HTTPS
			curl_setopt($oai_listsets_parser_ch, CURLOPT_SSL_VERIFYPEER, false);

			if (isset($resumptionToken)) {
				// Abfrage mit resumptionToken (selten...)
				curl_setopt($oai_listsets_parser_ch, CURLOPT_URL, $url."?verb=ListSets&resumptionToken=".$resumptionToken);
			} else {
				// Abfrage ohne resumptionToken (die Regel...)
				curl_setopt($oai_listsets_parser_ch, CURLOPT_URL, $url."?verb=ListSets");
			}
				
			$http_response = curl_exec($oai_listsets_parser_ch);
			
			// Ist der Server erreichbar und ist seine Antwort nicht leer?
			if ($http_response && curl_getinfo($oai_listsets_parser_ch, CURLINFO_HTTP_CODE) == 200) {
				
				if (!$this->parse_xml($http_response)) {
					break;
				}

				if ($this->getResumptionToken($http_response)) {
					$resumptionToken = $this->getResumptionToken($http_response);
					sleep(5); // 5 Sekunden warten vor der nächsten Abfrage.
				} else {
					unset($resumptionToken);
				}
				
			} else {
				$this->error = "Server ist nicht erreichbar.";
				break;		
			}
	
			curl_close($oai_listsets_parser_ch);
			unset($oai_listsets_parser_ch);
			
		} while (isset($resumptionToken));
	}
	
	// Extrahiert die Sets und speichert sie im Instanzarray $sets.
	// Gibt true bei vorhandenen Sets zurück, sonst false (keine Sets, bzw. Fehler beim Lesen des XML).
	private function parse_xml($listsets_xml) {
		
		/**********************************************
		 * DEBUG Code, kann später gelöscht werden.

		$file = fopen('listSets_keineh.xml', "r");
		$listsets_xml = "";
		while (!feof($file)) {
		        $listsets_xml .= fgets($file);
		}
		fclose($file);	
		
		**********************************************/
		
		$dom = new DOMDocument();
		
		if ($dom->loadXML($listsets_xml)) {		// Erzeugt im Fehlerfall eine Meldung, die nicht unterdrückt wird.
			
			// Gibt es einen Error?
			if ($dom->getElementsByTagName('error')->length != 0) {

				$error_node = $dom->getElementsByTagName('error');
				switch ($error_node->item(0)->getAttribute('code')) {
					
					// OAI-Quelle signalisiert, keine Sets zu unterstützen.
					case "noSetHierarchy":
						$this->error = "Diese OAI-Quelle unterstützt keine Sets und kann nur komplett geharvested werden.";
						$this->error_code = $error_node->item(0)->getAttribute('code');
						break;
					// Andere Fehlercodes werden in $error übernommen...
					default:
						$this->error = $error_node->item(0)->nodeValue;
						$this->error_code = $error_node->item(0)->nodeValue;
				}
				return false;
				
			} else {
			
				if ($dom->getElementsByTagName('set')->length == 0) {
				//if (true) { // Debug-Code
					$this->error = "Diese OAI-Quelle besitzt keine Sets und und kann nur komplett geharvested werden.";
					return false;
				} else {
					$sets = $dom->getElementsByTagName('set');
					
					foreach($sets as $set) {
						
						$setSpec = $set->getElementsByTagName('setSpec');
						$setName = $set->getElementsByTagName('setName');
						
						$this->sets[] = array("setSpec" => $setSpec->item(0)->nodeValue , "setName" => $setName->item(0)->nodeValue);
						unset($setSpec);
						unset($setName);
					}
					
					return true;
				}
			}
			
		} else {
			// Die Fehlermeldung des Parsers steht über dieser hier, zum Bugfixen.
			$this->error = "<span style=\"font-size: 35px;\">↑</span><br /><br />Setliste ist nicht valide (Grund s. Fehldermeldung). Die OAI-Quelle kann nur komplett geharvested werden.";
			return false;
		}
	}
	
	// Gibt den ResumptionToken zurück. Ist keiner vorhanden "false".
	private function getResumptionToken($listsets_xml) {	
		
		$dom = new DOMDocument();
		$dom->loadXML($listsets_xml);
		
		if ($dom) {
			
			if ($dom->getElementsByTagName('resumptionToken')->length == 0) {
				return false;
			} else {
				$element = $dom->getElementsByTagName('resumptionToken');
				return $element->item(0)->nodeValue;
			}
			
		} else {
			// Die Fehlermeldung des Parsers steht über dieser hier, zum Bugfixen.
			$this->error = "<span style=\"font-size: 35px;\">↑</span><br /><br />Setliste ist nicht valide (Grund s. Fehldermeldung). Die OAI-Quelle kann nur komplett geharvested werden.";
			return false;
		}
	}
	
	// War die Setabfrage erfolgreich?
	public function listSetsSuccessful() {
		if ($this->error == "") {
			return true;
		} else {
			return false;
		}
	}
	
	// Gibt die Fehlermeldung zurück, leerer String, falls kein Fehler.
	public function getErrorMessage() {
		return $this->error;
	}
	
	// Gibt den Fehlercode zurück, leerer String, falls kein Fehler.
	public function getErrorCode() {
		return $this->error_code;
	}
	
	// Gibt die Sets als Tabellezeilen für das Konfigurationsformular aus.
	public function getSetTableRows() {
			
		$rows = "";
		$i = 1;
		
		// Pseudo-Set für komplettes Harvesten der Quelle				
		
		if ($this->error_code == "noSetHierarchy" || count($this->sets) == 0) {
			
			$case = $this->error_code == "noSetHierarchy" ? "noSetHierarchy" : "noSets";
			
			$rows .= "<tr style=\"background-color: #B1D0B9;\">\n";
			$rows .= "<td><input type=\"button\" name=\"set_".$i."_preview\" value=\"Preview\" onclick=\"preview()\"></input>&nbsp;<a href=\"".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc\" onclick=\"window.open(this.href, '_blank'); return false;\"><img style=\"vertical-align: top;\" src=\"images/xml.png\" alt=\"OAI-XML anzeigen\" title=\"OAI-XML anzeigen\" /></a></td>\n";
			$rows .= "<td align=\"right\" valign=\"middle\"><input id=\"set1\" name=\"set1\" type=\"checkbox\" checked=\"checked\" disabled=\"disabled\"></td>\n";
			$rows .= "<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"set".$i."\">Komplette OAI-Quelle harvesten</label></input><input style=\"visibility: hidden;\" name=\"sets[".$i."][harvest]\" type=\"checkbox\" checked=\"checked\"></input>\n";
			$rows .= "<input type=\"hidden\" name=\"sets[".$i."][setName]\" value=\"".$case."\"></input>\n";
			$rows .= "<input type=\"hidden\" name=\"sets[".$i."][setSpec]\" value=\"allSets\"></input>\n";
			$rows .= "</td>\n";
			$rows .= "</tr>\n";
			
			
		} else {
			
			$rows .= "<tr style=\"background-color: #B1D0B9;\">\n";
			$rows .= "<td valign=\"middle\"><input type=\"button\" name=\"set_".$i."_preview\" value=\"Preview\" onclick=\"preview()\"></input>&nbsp;<a href=\"".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc\" onclick=\"window.open(this.href, '_blank'); return false;\"><img style=\"vertical-align: top;\" src=\"images/xml.png\" alt=\"OAI-XML anzeigen\" title=\"OAI-XML anzeigen\" /></a></td>\n";
			$rows .= "<td align=\"right\" valign=\"middle\"><input id=\"set".$i."\" name=\"sets[".$i."][harvest]\" type=\"checkbox\" onclick=\"validateSets()\"></input></td>\n";
			$rows .= "<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"set".$i."\">Komplette OAI-Quelle harvesten</label>\n";
			$rows .= "<input type=\"hidden\" name=\"sets[".$i."][setName]\" value=\"allSets\"></input>\n";
			$rows .= "<input type=\"hidden\" name=\"sets[".$i."][setSpec]\" value=\"allSets\"></input>\n";
			$rows .= "</td>\n";
			$rows .= "</tr>\n";
			
			$i++;
			
			foreach ($this->sets as $set) {
	
				if ($i%2 == 0) {			
					$rows .= "<tr>\n";
				} else {
					$rows .= "<tr style=\"background-color: #b9c8fe;\">\n";
				}
	
				$rows .= "<td><input type=\"button\" name=\"set_".$i."_preview\" value=\"Preview\" onclick=\"preview('". str_replace("'", "\'", $set['setSpec']) ."', '". str_replace("'", "\'", $set['setName']) ."')\"></input>&nbsp;<a href=\"".$this->url."?verb=ListRecords&amp;metadataPrefix=oai_dc&amp;set=".$set['setSpec']."\" onclick=\"window.open(this.href, '_blank'); return false;\"><img style=\"vertical-align: top;\" src=\"images/xml.png\" alt=\"OAI-XML anzeigen\" title=\"OAI-XML anzeigen\" /></a></td>\n";
				$rows .= "<td align=\"right\" valign=\"middle\"><input id=\"set".$i."\" name=\"sets[".$i."][harvest]\" type=\"checkbox\" onclick=\"validateSets()\"></input></td>\n";
				$rows .= "<td align=\"left\" valign=\"middle\" class=\"table_field_description\"><label for=\"set".$i."\">".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )." <span style=\"font-weight: normal;\">(".( htmlentities($set['setSpec'], ENT_QUOTES, 'UTF-8') ).")</span></label>\n";
				$rows .= "<input type=\"hidden\" name=\"sets[".$i."][setName]\" value=\"".( htmlentities($set['setName'], ENT_QUOTES, 'UTF-8') )."\"></input>\n";
				$rows .= "<input type=\"hidden\" name=\"sets[".$i."][setSpec]\" value=\"".( htmlentities($set['setSpec'], ENT_QUOTES, 'UTF-8') )."\"></input>\n";
				$rows .= "</td>\n";
				$rows .= "</tr>\n";
				
				$i++;
			}
		}
		
		return $rows;
	}
	
	// Gibt das Array mit den Sets zurück
	public function getSets() {
		return $this->sets;
	}
}

?>