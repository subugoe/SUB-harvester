<?php
/*
 * Enthält die Einstellungen für den Harvester.
 */


// ***** MYSQL Verbindungsdaten *****
include(dirname(__FILE__) . '/db_connect.php');



// ***** Allgmein *****

// Name des Dienstes; wird auf den Webseiten genutzt
define("SERVICE_NAME", 		'GEO-LEO Artikel');

// Adresse des Solr-Index
define("SOLR",				'http://localhost:8080/solr/geo-leo-artikel');

define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/geo-leo-artikel');
//define("DATA_FOLDER",		'C:/harvest_data');

define("METADATA_FORMAT",	'oai_dc');

// File name of stylesheet in software/xsl to convert the downloaded OAI-XML to Solr XML.
define("XSL_FILENAME",		'oaidc2solr.xsl');



// ***** gemeinsam genutzte Einstellungen *****
include(dirname(__FILE__) . '/../settings-common.php');


?>
