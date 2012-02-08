<?php
/*
 * Enthält die Einstellungen für die Scripte
 */

// ***** Allgmein *****

// Adresse des Solr-Index
define("SOLR",				'http://localhost:8983/solr');

// Verzeichnis in dem die geharvesteten OAI-Daten gespeichert werden
define("HARVEST_FOLDER",	'/web/http/weromm/eromm_search/harvest');
//define("HARVEST_FOLDER",	'C:/eromm_search/harvest');

// Verzeichnis in dem nach dem Indexieren alle Daten in einer Datei gezippt gespeichert werden
define("ARCHIVE_FOLDER",	'/web/http/weromm/eromm_search/archive');
//define("ARCHIVE_FOLDER",	'C:/eromm_search/archive');

// Verzeichnis in dem die für den Index Vorbereiteten Daten zwischengespeichert werden
// bevor sie in das Archiv bzw. das Fehlerverzeichnis verschoben werden
define("TEMP_FOLDER",	'/web/http/weromm/eromm_search/temp');
//define("TEMP_FOLDER",	'C:/eromm_search/temp');

// Verzeichnis in alle Daten eines Sets verschoben werden, wenn beim Harvesten
// oder Indexieren Fehler autraten (zum Nachvollziehen)
define("ERROR_FOLDER",	'/web/http/weromm/eromm_search/error');
//define("ERROR_FOLDER",	'C:/eromm_search/error');


// CHMOD setzen TODO
// Die Berechtigung mit der neue Daten geschrieben werden
define("CHMOD",				0777);


// ***** Indexer *****

// Die Update-URL von Solr
define("SOLR_INDEXER",		SOLR.'/update');
// Die Ping-URL von Solr
define("SOLR_PING",			SOLR.'/admin/ping');


// ***** Harvester *****

// Der vom Harvester verwendete User-Agent
define("USERAGENT",				"eromm-oai-harvesting-bot/1.2 (+http://www.eromm.org)");
// Wartezeit swischen den Listrecords-Anfragen beim Harvesten
define("LISTRECORDS_DELAY",		5);
// Minimalgeschwindigkeit in Bytes pro Sekunde die über den Zeitraum SPEED_TIME nicht unterschritten werden darf (sonst Abbruch des Harvestens)
define("SPEED_LIMIT",			1024);
// Zeitraum in dem die Minimageschwindigkeit (SPEED_LIMIT) erreicht werden muss in Sekunden
define("SPEED_TIME",			180);
// Maximale Anzahl der Versuche einer Anfrage (HTTP)
define("ERROR_RETRY",			8);
// Verzögerung bis zum Wiederholen einer Anfrage (HTTP) im Fehlerfall in Sekunden
define("ERROR_RETRY_DELAY",		40);





?>