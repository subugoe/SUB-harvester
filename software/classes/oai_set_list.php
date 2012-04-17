<?php

/*
 * Diese Klasse parsed die ListSets-Antwort einer OAI-Quelle.
 */

class oai_set_list {
	protected $owner;
	protected $url;

	protected $sets = array();
	protected $pseudo_set = array('setName' => 'allSets', 'setSpec' => 'allSets');
	protected $error = "";
	protected $error_code = "";



	public function __construct ($owner, $url) {
		$this->owner = $owner;
		$this->url = $url;
	}



	public function makeTables ($noSetHierarchy = FALSE) {
		$container = $this->owner->document->createElement('div');

		// Pseudo-Set
		$container->appendChild($this->makeTableForSets(Array($this->pseudo_set), 'unchanged', 'pseudo', ($noSetHierarchy === FALSE)));

		// Filter UI
		$container->appendChild($this->makeFilterP());

		// Sets
		if (count($this->sets) > 0) {
			$container->appendChild($this->owner->makeElementWithText('h3', 'Sets'));
			$container->appendChild($this->makeTableForSets($this->sets, 'unchanged', 'realSets'));
		}

		return $container;
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



	public function listSets () {
		do {
			$oai_listsets_parser_ch = curl_init();
			curl_setopt($oai_listsets_parser_ch, CURLOPT_RETURNTRANSFER, 1);
			// Ignoriere SSL-Zertifikate bei HTTPS
			curl_setopt($oai_listsets_parser_ch, CURLOPT_SSL_VERIFYPEER, false);

			if (isset($resumptionToken)) {
				// Abfrage mit resumptionToken (selten...)
				curl_setopt($oai_listsets_parser_ch, CURLOPT_URL, $this->url."?verb=ListSets&resumptionToken=".$resumptionToken);
			} else {
				// Abfrage ohne resumptionToken (die Regel...)
				curl_setopt($oai_listsets_parser_ch, CURLOPT_URL, $this->url."?verb=ListSets");
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
			$this->error = "<span style='font-size: 35px;'>↑</span><br /><br />Setliste ist nicht valide (Grund s. Fehldermeldung). Die OAI-Quelle kann nur komplett geharvested werden.";
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
			$this->error = "<span style='font-size: 35px;'>↑</span><br /><br />Setliste ist nicht valide (Grund s. Fehldermeldung). Die OAI-Quelle kann nur komplett geharvested werden.";
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

}

?>
