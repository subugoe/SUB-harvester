<?php
/*
 * Enthält die Einstellungen für den Harvester.
 */


// ***** MYSQL Verbindungsdaten *****
include(dirname(__FILE__) . '/db_connect.php');



// ***** Allgemein *****

// Name des Dienstes; wird auf den Webseiten genutzt
define("SERVICE_NAME", 		'DigiZeitschriften');

// Adresse des Solr-Index
define("SOLR",				'http://localhost:8080/solr/digizeitschriften');

define("DATA_FOLDER",		'/var/www/htdocs/harvester/data/digizeitschriften');
//define("DATA_FOLDER",		'C:/harvest_data');



// ***** gemeinsam genutzte Einstellungen *****
include(dirname(__FILE__) . '/../settings-common.php');


?>
