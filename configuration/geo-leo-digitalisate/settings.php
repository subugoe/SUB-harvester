<?php
/*
 * Enthält die Einstellungen für den Harvester.
 */


// ***** MYSQL Verbindungsdaten *****
include(dirname(__FILE__) . '/db_connect.php');



// ***** Allgmein *****

// Name des Dienstes; wird auf den Webseiten genutzt
define("SERVICE_NAME", 		'GEO-LEO Digitalisate');

// Adresse des Solr-Index
define("SOLR",				'http://localhost:8080/solr/geo-leo-digitalisate');

define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/geo-leo-digitalisate');
//define("DATA_FOLDER",		'C:/harvest_data');



// ***** gemeinsam genutzte Einstellungen *****
include(dirname(__FILE__) . '/../settings-common.php');


?>
