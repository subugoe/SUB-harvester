<?php

/*
 * Erzeugt ein Preview einer Suchergebnisliste eines Sets anhand
 * der vorgenommen Einstellungen auf der "Hinzüfgen"- bzw. "Editierseite".
 * 
 * Dabei werden nur die Records des Sets dargestellt, die bei der ersten Anfrage
 * geliefert werden. Die Anzahl ist damit von der OAI-Quelle abhängig.
 */

set_time_limit(120);  

// Template laden
$file = fopen('./templates/html_template.html', "r");

$output = "";

while (!feof($file)) {
        $output .= fgets($file);
}
fclose($file);

$output = str_replace("%javascript%", 
	"<script src=\"jquery-1.6.2.min.js\" type=\"text/javascript\" charset=\"utf-8\"></script>\n<script src=\"preview_oai_set.js\" type=\"text/javascript\" charset=\"utf-8\"></script>", $output);

$content = "";

// 
$content .= "	<p style=\"text-align: right; margin-top: -20px;\"><input type=\"submit\" value=\" Previewfenster schließen\" onclick=\"window.close()\"></input></p>\n";
$content .= "	<h2>Preview</h2>\n";

sleep(1);

// Harvesten

$oai_listrecords_ch = curl_init();
curl_setopt($oai_listrecords_ch, CURLOPT_RETURNTRANSFER, 1);

// Mindestgeschwindigkeit um zu verhindern, dass das PHP-Skript die maximale Laufzeit
// bei extrem langsamen Servern erreicht und abbricht.
curl_setopt($oai_listrecords_ch, CURLOPT_LOW_SPEED_LIMIT, 1024);
curl_setopt($oai_listrecords_ch, CURLOPT_LOW_SPEED_TIME, 90);

// Ignoriere ungültige SSL-Zertifikate bei HTTPS
curl_setopt($oai_listrecords_ch, CURLOPT_SSL_VERIFYPEER, false);

$url = $_GET['url']."?verb=ListRecords&metadataPrefix=oai_dc";
// Set oder alles?
$url .= isset($_GET['setSpec']) ? "&set=".$_GET['setSpec'] : "";

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
				if (isset($_GET['setSpec'])) {
					$content .= "<p>Dieses Set enthält keine Datensätze.</p>";
				} else {
					$content .= "<p>Diese OAI-Quelle enthält keine Datensätze.</p>";
				}	
			} else {
				$content .= "<p>Fehler beim Harvesten - ".$error_node->item(0)->getAttribute('code').": ".$error_node->item(0)->nodeValue."</p>";
			}	
		} else {

			// Alle records ermitteln
			$record_nodes = $dom->getElementsByTagName('record');
			
			if ($record_nodes->length < 1) {
				// Es gibt keine Records
				// Dieser Fall sollte gemäß Protokoll nicht eintreten, da es dafür einen
				// Errorcode gibt... aber man weiß ja nie...
				if (isset($_GET['setSpec'])) {
					$content .= "<p>Dieses Set enthält keine Datensätze.</p>";
				} else {
					$content .= "<p>Diese OAI-Quelle enthält keine Datensätze.</p>";
				}			
			
			} else {
				
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
								
								if (preg_match($_GET['identifier_filter'], $dc_identifier->nodeValue)) {	
									
									// Passendes Element gefunden
									$link = $dc_identifier->nodeValue;
									$link_found = true;
									
									if ($_GET['identifier_resolver'] != "") {
										if ($_GET['identifier_resolver_filter'] != "") {
											
											// Resolver-Filter ist gesetzt
											// Resolver nur hinzufügen, wenn der Filter erfolgreich ist
											if (preg_match($_GET['identifier_resolver_filter'], $link)) {
												$link = $_GET['identifier_resolver'].$link;
											}
											
										} else {
											// Kein Resolver-Filter gesetzt, Resolver einfach hinzufügen
											$link = $_GET['identifier_resolver'].$link;
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
								$link = $_GET['identifier_alternative'];
								// Neues oai_url-Element erzeugen
								$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
								// Wert auf den link setzten 
								$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
								// und einhängen
								$oai_dc_node->item(0)->appendChild($node);	
							}	
						} else {
							// Es kann keine URL gebildet werden, default setzen
							$link = $_GET['identifier_alternative'];
							// Neues oai_url-Element erzeugen
							$node = $dom->createElementNS('http://www.eromm.org/eromm_oai_harvester/', 'eromm_oai:oai_url');
							// Wert auf den link setzten 
							$node->nodeValue = htmlspecialchars($link, ENT_QUOTES, "UTF-8", false);
							// und einhängen
							$oai_dc_node->item(0)->appendChild($node);
						}	
					} else { //Ende if $oai_dc_node->length > 0
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
							$content .= "	<p style=\"text-align: center;\"><a href=\"".$_GET['url']."?verb=ListRecords&amp;metadataPrefix=oai_dc".( isset($_GET['setSpec']) ? "&amp;set=".$_GET['setSpec'] : "" )."\" onclick=\"window.open(this.href, '_blank'); return false;\"><img style=\"vertical-align: top;\" src=\"images/xml.png\" alt=\"OAI-XML anzeigen\" title=\"OAI-XML anzeigen\" /></a></p>";
							$content .= "<p>Keine \"oai_dc-Elemente\" gefunden! Bitte XML prüfen!</p>";
							$oai_dc = false;
							break;
						}
					}
				}
				
				if ($oai_dc) {
				
					// XSL importieren
					$oai2index_xml = new DOMDocument();
					$oai2index_xml->load("xsl/oai2index.xsl");
					
					$oai2index_xsl = new XSLTProcessor();
					$oai2index_xsl->importStylesheet($oai2index_xml);
					
					// Parameter setzen
					$xsl_parameters = array(
					    'timestamp' 			=> date('Y.m.d, H:i:s'),
					    'country_code' 			=> $_GET['country'],
						'oai_repository_id'		=> 'unset',
						'i_cre' 				=> $_GET['i_cre'],
						'i_con' 				=> $_GET['i_con'],
						'i_pub' 				=> $_GET['i_pub'],
						'i_dat' 				=> $_GET['i_dat'],
						'i_ide' 				=> $_GET['i_ide'],
						'i_rel' 				=> $_GET['i_rel'],
						'i_sub' 				=> $_GET['i_sub'],
						'i_des' 				=> $_GET['i_des'],
						'i_sou' 				=> $_GET['i_sou'],
						'v_cre' 				=> $_GET['v_cre'],
						'v_con' 				=> $_GET['v_con'],
						'v_pub' 				=> $_GET['v_pub'],
						'v_dat' 				=> $_GET['v_dat'],
						'v_ide' 				=> $_GET['v_ide']
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
							
							$content .= "	<h3 style=\"text-align: center; text-indent: 0; font-weight: normal; margin-top: -15px;\">".$_GET['name'];
							// Sind Name oder setSpec gesetzt?
							$content .= isset($_GET['setName']) || isset($_GET['setSpec']) ? " <span style=\"color: #424242;\">/</span>" : "";
							// Ist ein setName gesetzt? => Anzeigen
							$content .= isset($_GET['setName']) ? " ". htmlentities($_GET['setName'], ENT_QUOTES, 'UTF-8') : "";
							// Ist ein setSpec gesetzt? => Anzeigen
							$content .= isset($_GET['setSpec']) ? " (". htmlentities($_GET['setSpec'], ENT_QUOTES, 'UTF-8') .")" : "";
							$content .= "</h3>";
							
							// Link auf OAI-XML
							$content .= "	<p style=\"text-align: center;\"><a href=\"".$_GET['url']."?verb=ListRecords&amp;metadataPrefix=oai_dc".( isset($_GET['setSpec']) ? "&amp;set=".$_GET['setSpec'] : "" )."\" onclick=\"window.open(this.href, '_blank'); return false;\"><img style=\"vertical-align: top;\" src=\"images/xml.png\" alt=\"OAI-XML anzeigen\" title=\"OAI-XML anzeigen\" /></a></p>";
							
							$content .= "	<hr style=\"margin-top: 20px; margin-bottom: 20px; color: #424242;; width: 60%;\" />";
							$content .= "	<div style=\"width: 525px; margin-left: auto; margin-right: auto;\">";
						
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
								
								$content .= "			<div id=\"div_".$i."\" class=\"result_list_record\" onmouseover=\"show_index(this.id)\" onmouseout=\"hide_index(this.id)\">\n";
								$content .= "				<p class=\"result_web\">\n";
								
								// Hier wird immer die erste URL zur Verlinkung verwendet, aber unterschiedliches Javascript
								if ($url_nodes->length > 1) {
									// Es gibt mehrere Links
									$content .= "					<a class=\"result_link_top\" href=\"".$url_nodes->item(0)->nodeValue."\" onclick=\"show_links('#div_".$i."_link'); return false;\">\n";
								} else {
									// Es gibt nur einen Link
									$content .= "					<a class=\"result_link_top\" href=\"".$url_nodes->item(0)->nodeValue."\" onclick=\"window.open(this.href, '_blank'); return false;\">\n";
								}
								
								// Titel - Header des Eintrags
								$content .= "						".htmlentities($title_node->item(0)->nodeValue, ENT_QUOTES, "UTF-8", false)."\n";
								$content .= "					</a>\n";
								$content .= "				</p>\n";
								
								// Falls es mehrere Links gibt, Linkliste erstellen
								if ($url_nodes->length > 1) {
									
									$content .= "				<div id=\"div_".$i."_link\" class=\"link_div\" style=\"display: none;\">\n";
									$content .= "					<ul class=\"result_links\">\n";
									
									// Listenelemente für jeden Link
									foreach ($url_nodes as $url_node) {
										$content .= "						<li><a href=\"".$url_node->nodeValue."\" onclick=\"window.open(this.href, '_blank'); return false;\">".$url_node->nodeValue."</a></li>\n";
									}
	
									$content .= "					</ul>\n";
									$content .= "				</div>\n";			
								}
								
								// Gibt es außer dem Titel weiteres zum anzeigen?
								
								if ($isbd_node->item(0)->nodeValue != "" || $url_nodes->length > 1 || $format_nodes->length > 0) {
								
									$content .= "				<p class=\"result_web\">\n";
									
									if ($isbd_node->item(0)->nodeValue != "") {
										$content .= "					".htmlentities($isbd_node->item(0)->nodeValue, ENT_QUOTES, "UTF-8", false)."\n";
										
										if ($url_nodes->length > 1 || $format_nodes->length > 0) {
											$content .= "<br />\n";
										}
									}
									
									// Gibt es eine Footer (mehrere Links oder Formatangaben)?
									if ($url_nodes->length > 1 || $format_nodes->length > 0) {
										
										// Gibt es mehrere Links?
										if ($url_nodes->length > 1) {
											$content .= "<img src=\"images/multiple_links.png\" alt=\"This record contains ".$url_nodes->length." links.\" title=\"This record contains ".$url_nodes->length." links.\" />";	
											if ($format_nodes->length > 0) {
												$content .= "&nbsp;";
											}
										}
		
										// Gibt es Formatangaben?
										if ($format_nodes->length > 0) {
											
											foreach ($format_nodes as $format_node) {
		
												// Alle unterstützten Formate
												switch ($format_node->nodeValue) {
													
													case "application/pdf":
														$content .= "<img src=\"images/pdf.png\" alt=\"PDF\" title=\"PDF\" />";
														break;
														
													case "image/jpeg":
														$content .= "<img src=\"images/jpeg.png\" alt=\"JPG\" title=\"JPG\" />";
														break;
														
													case "image/png":
														$content .= "<img src=\"images/png.png\" alt=\"PNG\" title=\"PNG\" />";
														break;
														
													case "image/tiff":
														$content .= "<img src=\"images/png.png\" alt=\"TIFF\" title=\"TIFF\" />";
														break;
		
												}
												
												// Leerzeichen, falls noch weitere Symbole folgen
												if ($format_node->nodeValue != $format_nodes->item($format_nodes->length - 1)->nodeValue) {
													$content .= "&nbsp;";
												}
											}
										}
									}
		
									$content .= "					</p>\n";
								}
	
								$content .= "				</div>\n";
								
								// Indexeintrag
								$content .= "				<div id=\"div_".$i."_index\" class=\"index_div\" style=\"display: none; position: absolute;\">\n";
								$content .= "					<p>\n";
								$content .=	"						".htmlentities($index_node->item(0)->nodeValue, ENT_QUOTES, "UTF-8", false)."\n";
								$content .= "					</p>\n";
								$content .= "				</div>\n";
								
								$i++;
							}
							
							$content .= "			</div>\n";
						
						} else {
							$content .= "<p>Fehler beim Konvertieren der ListRecords-Antwort.</p>";
						}
					
					} else {
						$content .= "<p>Fehler beim Konvertieren der ListRecords-Antwort.</p>";
					}
				} // Ende if oai_dc
			}
		}
	
	} else {
		$content .= "<p>Fehler beim parsen der ListRecords-Antwort.</p>";		
	}
	
} else {
	$content .= "<p>Der Server ist nicht erreichbar. Fehler: <br /><tt>".curl_error($oai_listrecords_ch)."</tt></p>";
}

curl_close($oai_listrecords_ch);
echo str_replace("%content%", $content, $output);

?>