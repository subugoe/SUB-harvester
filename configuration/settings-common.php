<?php
/*
 * Enthält die gemeinsam genutzten Einstellungen für die Scripte.
 *
 * Wird eingebunden aus der Einstellungsdatei für den jeweiligen Dienst.
 */


// Verzeichnis in dem die geharvesteten OAI-Daten gespeichert werden
define("HARVEST_FOLDER",	DATA_FOLDER . '/harvest');

// Verzeichnis in dem nach dem Indexieren alle Daten in einer Datei gezippt gespeichert werden
define("ARCHIVE_FOLDER",	DATA_FOLDER . '/archive');

// Verzeichnis in dem die für den Index vorbereiteten Daten zwischengespeichert werden
// bevor sie in das Archiv bzw. das Fehlerverzeichnis verschoben werden
define("TEMP_FOLDER",	DATA_FOLDER . '/temp');

// Verzeichnis in alle Daten eines Sets verschoben werden, wenn beim Harvesten
// oder Indexieren Fehler auftraten (zum Nachvollziehen)
define("ERROR_FOLDER",	DATA_FOLDER . '/error');


// CHMOD setzen TODO
// Die Berechtigung mit der neue Daten geschrieben werden
define("CHMOD",				0777);



// ***** Indexer *****

// Die Update-URL von Solr
define("SOLR_INDEXER",		SOLR . '/update');
// Die Ping-URL von Solr
define("SOLR_PING",			SOLR . '/admin/ping');



// ***** Harvester *****

// Der vom Harvester verwendete User-Agent
define("USERAGENT",				'eromm-oai-harvesting-bot/1.2 (+http://www.eromm.org)');

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
