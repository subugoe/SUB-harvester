<?php
/*
 * Enthält die Einstellungen für den Harvester.
 */


// ***** MYSQL Verbindungsdaten *****
include(dirname(__FILE__) . '/db_connect.php');



// ***** Allgmein *****

// Name des Dienstes; wird auf den Webseiten genutzt
define("SERVICE_NAME", 		'Pangaea');

// Adresse des Solr-Index
define("SOLR",				'http://solr-harvest.tc.sub.uni-goettingen.de/solr/pangaea');

define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/pangaea');

define("METADATA_FORMAT",	'pan_md');

// File name of stylesheet in software/xsl to convert the downloaded OAI-XML to Solr XML.
define("XSL_FILENAME",		'panmd2solr.xsl');



// ***** gemeinsam genutzte Einstellungen *****
include(dirname(__FILE__) . '/../settings-common.php');


?>
