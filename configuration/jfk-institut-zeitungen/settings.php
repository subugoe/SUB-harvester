<?php
/*
 * Enthält die Einstellungen für den Harvester.
 */


// ***** MYSQL Verbindungsdaten *****
include(dirname(__FILE__) . '/db_connect.php');



// ***** Allgmein *****

// Name des Dienstes; wird auf den Webseiten genutzt
define("SERVICE_NAME", 		'Test');

// Adresse des Solr-Index
define("SOLR",				'http://solr-harvest.tc.sub.uni-goettingen.de/solr/jfk-institut-zeitungen');

define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/jfk-institut-zeitungen');

define("METADATA_FORMAT",	'marc21');

// File name of stylesheet in software/xsl to convert the downloaded OAI-XML to Solr XML.
define("XSL_FILENAME",		'fu-marc2solr.xsl');



// ***** gemeinsam genutzte Einstellungen *****
include(dirname(__FILE__) . '/../settings-common.php');


?>
