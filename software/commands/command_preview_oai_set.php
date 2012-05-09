<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zum Anlegen einer OAI Quelle.
 */
class command_previewOAISet extends command {

	public function appendContent () {

		/*
		 * Erzeugt ein Preview einer Suchergebnisliste eines Sets anhand
		 * der vorgenommen Einstellungen auf der "Hinzüfgen"- bzw. "Editierseite".
		 *
		 * Dabei werden nur die Records des Sets dargestellt, die bei der ersten Anfrage
		 * geliefert werden. Die Anzahl ist damit von der OAI-Quelle abhängig.
		 */

		set_time_limit(120);

		$this->contentElement->appendChild($this->makeFormWithSubmitButton('Vorschaufenster schließen', 'window.close()'));
		$this->contentElement->appendChild($this->makeElementWithText('h2', 'Vorschau'));

		// Harvesten
		$oai_listrecords_ch = curl_init();
		curl_setopt($oai_listrecords_ch, CURLOPT_RETURNTRANSFER, 1);

		// Mindestgeschwindigkeit um zu verhindern, dass das PHP-Skript die maximale Laufzeit
		// bei extrem langsamen Servern erreicht und abbricht.
		curl_setopt($oai_listrecords_ch, CURLOPT_LOW_SPEED_LIMIT, 1024);
		curl_setopt($oai_listrecords_ch, CURLOPT_LOW_SPEED_TIME, 90);

		// Ignoriere ungültige SSL-Zertifikate bei HTTPS
		curl_setopt($oai_listrecords_ch, CURLOPT_SSL_VERIFYPEER, false);

		$url = $this->parameters['url'] . '?verb=ListRecords&metadataPrefix=oai_dc';
		// Set oder alles?
		$url .= isset($this->parameters['setSpec']) ? '&set=' . $this->parameters['setSpec'] : "";

		curl_setopt($oai_listrecords_ch, CURLOPT_URL, $url);

		$http_response = curl_exec($oai_listrecords_ch);

		// Ist der Server erreichbar und ist seine Antwort nicht leer?
		if ($http_response && curl_getinfo($oai_listrecords_ch, CURLINFO_HTTP_CODE) == 200) {

			$dom = new DOMDocument();

			// DOM-Element erzeugen
			if ($dom->loadXML($http_response)) {
				// Gibt es einen Error?
				$error_node = $dom->getElementsByTagName('error');

				if ($error_node->length != 0) {

					$error_node = $dom->getElementsByTagName('error');

					if ($error_node->item(0)->getAttribute('code') == "noRecordsMatch") {
						// Fehlercode für keine Records in der Selektierten Menge
						if (isset($this->parameters['setSpec'])) {
							$this->contentElement->appendChild($this->makeElementWithText('p', 'Dieses Set enthält keine Datensätze.'));
						}
						else {
							$this->contentElement->appendChild($this->makeElementWithText('p', 'Diese OAI-Quelle enthält keine Datensätze.'));
						}
					}
					else {
						$this->contentElement->appendChild($this->makeElementWithText('p', 'Fehler beim Harvesten – ' . $error_node->item(0)->getAttribute('code') . ': ' . $error_node->item(0)->nodeValue), 'error');
					}
				}
				else {
					// Alle Records ermitteln
					$record_nodes = $dom->getElementsByTagName('record');

					if ($record_nodes->length < 1) {
						// Es gibt keine Records
						// Dieser Fall sollte gemäß Protokoll nicht eintreten, da es dafür einen
						// Errorcode gibt... aber man weiß ja nie...
						if (isset($this->parameters['setSpec'])) {
							$this->contentElement->appendChild($this->makeElementWithText('p', 'Dieses Set enthält keine Datensätze.'));
						}
						else {
							$this->contentElement->appendChild($this->makeElementWithText('p', 'Diese OAI-Quelle enthält keine Datensätze.'));
						}

					}
					else {
						// Wenn keine oai_dc-Elemente gefunden werden, wird es auf false gesetzt
						// und das Indexieren abgebrochen
						$oai_dc = true;

						// Records durchgehen, nach Verlinkung suchen
						foreach($record_nodes as $record_node) {

							// oai_dc:dc-Element ermitteln
							$oai_dc_node = $record_node->getElementsByTagNameNS('http://www.openarchives.org/OAI/2.0/oai_dc/', 'dc');

							/* Handelt es sich beim record um das Anzeigen einer gelöschten Resource, gibt es kein oai_dc:dc-Element
							 * und ein Link kann nicht ermittelt werden
							 * Zusätztlich wird geprüft, ob das Header-Elemente ein Attribut "status='deleted' enthält.
							 * Diese doppelte Prüfung ist robuster. */

							if ($oai_dc_node->length > 0) {

								// Alle dc:identifier-Element ermitteln und ...
								$dc_identifier_nodes = $record_node->getElementsByTagNameNS('http://purl.org/dc/elements/1.1/', 'identifier');

								// Prüfen, ob Elemente gefunden wurden
								if ($dc_identifier_nodes->length > 0) {
									// ... Filter anwenden
									$link_found = false;
									foreach($dc_identifier_nodes as $dc_identifier) {

										if (preg_match($this->parameters['identifier_filter'], $dc_identifier->nodeValue)) {

											// Passendes Element gefunden
											$link = $dc_identifier->nodeValue;
											$link_found = true;

											if ($this->parameters['identifier_resolver'] != "") {
												if ($this->parameters['identifier_resolver_filter'] != "") {

													// Resolver-Filter ist gesetzt
													// Resolver nur hinzufügen, wenn der Filter erfolgreich ist
													if (preg_match($this->parameters['identifier_resolver_filter'], $link)) {
														$link = $this->parameters['identifier_resolver'].$link;
													}

												} else {
													// Kein Resolver-Filter gesetzt, Resolver einfach hinzufügen
													$link = $this->parameters['identifier_resolver'].$link;
												}
											}

											// Neues oai_url-Element erzeugen
											$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
											// Wert auf den link setzten
											$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);;
											// und einhängen
											$oai_dc_node->item(0)->appendChild($node);
										}
									}

									if (!$link_found) {
										// Aus keinem dc:identifier-Element konnte eine URL gebildet werden
										// default setzen
										$link = $this->parameters['identifier_alternative'];
										// Neues oai_url-Element erzeugen
										$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
										// Wert auf den link setzten
										$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
										// und einhängen
										$oai_dc_node->item(0)->appendChild($node);
									}
								}
								else {
									// Es kann keine URL gebildet werden, default setzen
									$link = $this->parameters['identifier_alternative'];
									// Neues oai_url-Element erzeugen
									$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
									// Wert auf den link setzten
									$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
									// und einhängen
									$oai_dc_node->item(0)->appendChild($node);
								}
							} //Ende if $oai_dc_node->length > 0
							else {
								// Prüfen ob es eine Update-Meldung gibt
								$header_node = $record_node->getElementsByTagName('header');

								if ($header_node->item(0)->getAttribute('status') == "deleted") {
									// Ja
									continue;
								} else {
									// Nein
									// Kann nur passieren, wenn der Namespace falsch benannt ist
									// Sollte eigentlich nicht vorkommen, aber in Praxis leider schon gesehen (SBB)
									// Damit sollte das Indexieren dieser Quelle abgebrochen werden
									$a = $this->makeElementWithText('a', 'OAI-XML anzeigen', 'OAILink');
									$a->setAttribute('onclick', 'window.open(this.href, "_blank"); return false;');
									$XMLURL = $this->url . '?verb=ListRecords&metadataPrefix=oai_dc';
									if (array_key_exists('setSpec', $this->parameters) && $this->parameters['setSpec'] !== 'allSets') {
										$XMLURL .= '&set=' . $set['setSpec'];
									}
									$a->setAttribute('href', $XMLURL);

									$this->contentElement->appendChild($this->makeElementWithContent('p', $a));
									$this->contentElement->appendChild($this->makeElementWithText('p', 'Keine »oai_dc-Elemente« gefunden! Bitte XML prüfen.'));

									$oai_dc = false;
									break;
								}
							}
						}

						if ($oai_dc) {

							// XSL importieren
							$oai2index_xml = new DOMDocument();
							$oai2index_xml->load(dirname(__FILE__) . "/../xsl/oai2index.xsl");

							$oai2index_xsl = new XSLTProcessor();
							$oai2index_xsl->importStylesheet($oai2index_xml);

							// Parameter setzen
							$xsl_parameters = array(
							    'timestamp' 			=> date('Y.m.d, H:i:s'),
							    'country_code' 			=> $this->parameters['country'],
								'oai_repository_id'		=> 'unset',
								'i_cre' 				=> $this->parameters['i_cre'],
								'i_con' 				=> $this->parameters['i_con'],
								'i_pub' 				=> $this->parameters['i_pub'],
								'i_dat' 				=> $this->parameters['i_dat'],
								'i_ide' 				=> $this->parameters['i_ide'],
								'i_rel' 				=> $this->parameters['i_rel'],
								'i_sub' 				=> $this->parameters['i_sub'],
								'i_des' 				=> $this->parameters['i_des'],
								'i_sou' 				=> $this->parameters['i_sou'],
								'v_cre' 				=> $this->parameters['v_cre'],
								'v_con' 				=> $this->parameters['v_con'],
								'v_pub' 				=> $this->parameters['v_pub'],
								'v_dat' 				=> $this->parameters['v_dat'],
								'v_ide' 				=> $this->parameters['v_ide']
							);

							$oai2index_xsl->setParameter('', $xsl_parameters);

							$dom->loadXML($dom->saveXML());

							// XSLT
							$solr_add_string = $oai2index_xsl->transformToXML($dom);

							if ($solr_add_string) {

								// Entfernen von "doppelten" Deskriptionszeichen und "verkorksten" Auslassungspunkten
								// Nicht schön, diese Dinge hier zu fixen, aber es ist in XSTL zu komplex und zeitaufwendig zu programmieren
								// Ggf. um weitere Ersetzungen ergänzen
								$search  = array('.. — ', ',, ', '....', '.....', '......', ' ...');
								$replace = array('. — ',  ', ',  '...',  '...',   '...',    '...') ;

								$solr_add_string = str_replace($search, $replace, $solr_add_string);

								$solr_add_xml = new DOMDocument();


								// Parsen der konvertierten ListRecords-Antword und Generierung der Ausgabe
								if ($solr_add_xml->loadXML($solr_add_string)) {

									// Statisch
									$h3 = $this->makeElementWithText('h3', $this->parameters['name']);
									$this->contentElement->appendChild($h3);
									if ($this->parameters['setName']) {
										$h3->appendChild($this->document->createTextNode(' / ' . $this->parameters['setName']));
									}
									if ($this->parameters['setSpec']) {
										$h3->appendChild($this->document->createTextNode('(' . $this->parameters['setSpec'] . ')'));
									}

									// Link auf OAI-XML
									$a = $this->makeElementWithText('a', 'OAI-XML anzeigen', 'OAILink');
									$a->setAttribute('onclick', 'window.open(this.href, "_blank"); return false;');
									$XMLURL = $this->url . '?verb=ListRecords&metadataPrefix=oai_dc';
									if (array_key_exists('setSpec', $this->parameters) && $this->parameters['setSpec'] !== 'allSets') {
										$XMLURL .= '&set=' . $set['setSpec'];
									}
									$a->setAttribute('href', $XMLURL);
									$this->contentElement->appendChild($this->makeElementWithContent('p', $a));

									// Einzelne "Suchergebnissse"
									$doc_nodes = $solr_add_xml->getElementsByTagName('doc');

									$i = 1;

									foreach ($doc_nodes as $doc_node) {
										// Für die Ausgabe relevante Elemente ermitteln
										// Geht einfacher mit XPath, aber Konstruktion leider komplizierter...
										$doc_node_domdocument = new DOMDocument();
										$doc_node_domdocument->loadXML($solr_add_xml->saveXML($doc_node));

										$xpath_doc_node = new DOMXpath($doc_node_domdocument);

										$title_node 	= $xpath_doc_node->query("field[@name='oai_title']");
										$url_nodes 		= $xpath_doc_node->query("field[@name='oai_url']");
										$format_nodes 	= $xpath_doc_node->query("field[@name='oai_format']");
										$index_node 	= $xpath_doc_node->query("field[@name='oai_index']");
										$isbd_node 		= $xpath_doc_node->query("field[@name='oai_isbd']");

										$div = $this->document->createElement('div');
										$this->contentElement->appendChild($div);
										$div->setAttribute('id', 'div_' . $i);
										$div->setAttribute('class', 'result_list_record');
										$div->setAttribute('onmouseover', 'show_index(this.id)');
										$div->setAttribute('onmouseout', 'hide_index(this.id)');

										$p = $this->document->createElement('p');
										$div->appendChild($p);
										$p->setAttribute('class', 'result_web');

										// Hier wird immer die erste URL zur Verlinkung verwendet, aber unterschiedliches Javascript
										$a = $this->makeElementWithText('a', $title_node->item(0)->nodeValue);
										$p->appendChild($a);
										$a->setAttribute('class', 'result_link_top');
										$a->setAttribute('href', $url_nodes->item(0)->nodeValue);
										if ($url_nodes->length > 1) {
											// Es gibt mehrere Links
											$a->setAttribute('onclick', 'show_links("#div_' . $i . '_link"); return false;');

											$ul = $this->document->createElement('ul');
											$p->appendChild($ul);
											$ul->setAttribute('class', 'result_links');
											foreach ($url_nodes as $url_node) {
												$a = $this->makeElementWithText('a', $url_node->nodeValue);
												$ul->appendChild($this->makeElementWithContent('li', $a));
												$a->setAttribute('href', '$url_node->nodeValue');
												$a->setAttribute('onclick', 'window.open(this.href, "_blank"); return false;');
											}

										}
										else {
											// Es gibt nur einen Link
											$a->setAttribute('onclick', 'window.open(this.href, "_blank")');
										}


										// Gibt es außer dem Titel weiteres zum anzeigen?

										if ($isbd_node->item(0)->nodeValue != "" || $url_nodes->length > 1 || $format_nodes->length > 0) {

											$p = $this->document->createElement('p');
											$div->appendChild($p);
											$p->setAttribute('class', 'result_web');

											if ($isbd_node->item(0)->nodeValue != "") {
												$p->appendChild($this->document->createTextNode($isbd_node->item(0)->nodeValue));

												if ($url_nodes->length > 1 || $format_nodes->length > 0) {
													$p->appendChild($this->document->createElement('br'));
												}
											}

											// Gibt es eine Footer (mehrere Links oder Formatangaben)?
											if ($url_nodes->length > 1 || $format_nodes->length > 0) {

												// Gibt es mehrere Links?
												if ($url_nodes->length > 1) {
													$img = $this->document->createElement('img');
													$p->appendChild($img);
													$img->setAttribute('src', 'resources/images/multiple_links.png');
													$img->setAttribute('alt', 'Zu diesem Eintrag gibt es ' . $url_nodes->length . ' Links.');
													if ($format_nodes->length > 0) {
														$p->appendChild($this->createTextNode(' ')); // Non-breaking space
													}
												}

												// Gibt es Formatangaben?
												if ($format_nodes->length > 0) {

													foreach ($format_nodes as $format_node) {
														$formatName = '';
														// Alle unterstützten Formate
														switch ($format_node->nodeValue) {
															case "application/pdf":
																$formatName = 'pdf';
																break;

															case "image/jpeg":
																$formatName = 'jpeg';
																break;

															case "image/png":
																$formatName = 'png';
																break;

															case "image/tiff":
																$formatName = 'tiff';
																break;
														}

														if ($formatName !== '') {
															$img = $this->document->createElement('img');
															$p->appendChild('img');
															$img->setAttribute('src', 'resources/images/' . $formatName . '.png');
															$img->setAttribute('alt', strtoupper($formatName));
														}

														// Leerzeichen, falls noch weitere Symbole folgen
														if ($format_node->nodeValue != $format_nodes->item($format_nodes->length - 1)->nodeValue) {
															$p->appendChild($this->createTextNode(' ')); // Non-breaking space
														}
													}
												}
											}
										}

										// Indexeintrag
										$p = $this->makeElementWithText('p', $index_node->item(0)->nodeValue);
										$this->contentElement->appendChild($p);
										$p->setAttribute('class', 'index_div');
										$p->setAttribute('id', 'div_' . $i . '_index');

										$i++;
									}

								}
								else {
									$this->contentElement->appendChild($this->makeElementWithText('p', 'Fehler beim Konvertieren der ListRecords-Antwort.', 'error'));
								}

							}
							else {
								$this->contentElement->appendChild($this->makeElementWithText('p', 'Fehler beim Konvertieren der ListRecords-Antwort.', 'error'));
							}
						} // Ende if oai_dc
					}
				}

			}
			else {
				$this->contentElement->appendChild($this->makeElementWithText('p', 'Fehler beim Parsen der ListRecords-Antwort.', 'error'));
			}

		}
		else {
			$p = $this->makeElementWithText('p', 'Der Server ist nicht erreichbar. Fehler: ', 'error');
			$this->contentElement->appendChild($p);
			$p->appendChild($this->makeElementWithText('pre', curl_error($oai_listrecords_ch)));
		}

		curl_close($oai_listrecords_ch);
	}

}

?>
