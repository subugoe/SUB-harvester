<?php
/*
 * Enthält die Einstellungen für den Harvester.
 */


// ***** MYSQL Verbindungsdaten *****
include(dirname(__FILE__) . '/db_connect.php');



// ***** Allgemein *****

// Name des Dienstes; wird auf den Webseiten genutzt
define("SERVICE_NAME", 		'GoeScholar');

// Adresse des Solr-Index
define("SOLR",				'http://solr-harvest.tc.sub.uni-goettingen.de/solr/goescholar');

define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/goescholar');
//define("DATA_FOLDER",		'C:/harvest_data');

define("METADATA_FORMAT",	'oai_dc');

// File name of stylesheet in software/xsl to convert the downloaded OAI-XML to Solr XML.
define("XSL_FILENAME",		'oaidc2solr.xsl');



// ***** gemeinsam genutzte Einstellungen *****
include(dirname(__FILE__) . '/../settings-common.php');


?>
