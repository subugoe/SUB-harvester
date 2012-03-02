<?php
/*
 * Diese Datei enthält Funktionen, die von Harvester und / oder Indexscript genutzt werden
 */


// Liest die Konfigurationsdatei ein.
// Ist die Variable HARVESTER_CONFIGURATION_NAME gesetzt, werden die Einstellungen
// aus dem Unterordner des config Ordners mit dem Namen des Variableninhalts geladen.
function readConfiguration () {
	$configurationPath = dirname(__FILE__) . '/../../configuration';
	$configurationName = getenv('HARVESTER_CONFIGURATION_NAME');
	if ($configurationName) {
		$configurationPath .= '/' . $configurationName;
	}
	$configurationPath .= '/settings.php';
	require_once($configurationPath);
}


// Prüft, ob die Datenbankverbindung noch besteht, 
// versucht im Fehlerfall eine Wiederherstellung
// Gibt den DB-Link zurück
function mysql_connection_check($db_link) {
	
	if (!mysql_ping($db_link)) {
		// Versuchen, die Verbindung wiederherzustellen
		readConfiguration();
		$db_link = mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, TRUE);
		
		// DB-Einstellungen
		mysql_select_db(DB_NAME);
		mysql_query("SET NAMES 'utf8'");
		mysql_query("SET CHARACTER SET 'utf8'");
	}
	
	return $db_link;
}


// Erstellt Logeinträge
function insert_log($set_id, $status, $message, $db_link, $add, $delete, $type) {
	
	$sql = "INSERT INTO oai_logs 
		(
			id, 
			time, 
			oai_set, 
			status, 
			type, 
			message,
			added, 
			deleted 
		)
		VALUES (
			NULL, 
			NOW(), 
			" . intval($set_id) . ",
			" . intval($status) . ",
			" . intval($type) . ",
			'" . mysql_real_escape_string($message) . "',
			" . intval($add) . ",
			" . intval($delete) . "
		)";
	
	// Besteht die Verbindung noch?
	$db_link = mysql_connection_check($db_link);
	
	// Fehler wird nicht abgefangen, falls mit der Datenbank was nicht ok ist,
	// ist das schon vorher aufgefallen.
	$result = mysql_query($sql, $db_link);
	
	if(!$result) {
		// Fehler wird einfach ausgegeben
		echo "\n\n".substr(date('c', time()), 0, 19)."\n\n";
		echo "Fehler \"".mysql_error($db_link)."\" beim Schreiben des Logeintrags:\n";
		echo $message;
		echo "\nDer SQL-Befehl war:\n";
		echo "\"".$sql."\"";
	}
}

// Diese Funktion fügt das $source_folder dem Archiv $archive unter dem Verzeichnis $archive_dest hinzu
// Man könnte hier noch viel Fehlerbehandlung einfügen... TODO
function add_to_archive($archive, $archive_dest, $source_folder) {
	
	// Zur Sicherheit, bei großen Verzeichnissen könnte das Limit erreicht werden
	set_time_limit(600);
	
	// Verzeichnis einlesen
	$source_elements = scandir($source_folder);
	
	// Alle Elemente durchgehen
	foreach ($source_elements as $source_element) {
		
		// Übergehen der übergeordneten Ebene 
		if ($source_element != ".." && $source_element != ".") {
			
			// Ist Element ein Verzeichnis?
			if (is_dir($source_folder."/".$source_element)) {
				// Es ist ein Verzeichnis
				// Verzeichnis im Archiv erstellen
				$archive->addEmptyDir($archive_dest."/".$source_element);
				// Und das Verzeichnis ins Archive kopieren
				add_to_archive($archive, $archive_dest."/".$source_element, $source_folder."/".$source_element);
				
			} else {
				// Das Element ist eine Datei
				// Datei dem Archiv hinzufügen
				$archive->addFile($source_folder."/".$source_element, $archive_dest."/".$source_element);
			}
		}
	}
}

// Diese Funktion löscht das Verzeichnis $folder inklusive aller Unterverzeichnisse und Dateien
// Man könnte hier noch viel Fehlerbehandlung einfügen... TODO
function remove_folder($folder) {
	// Zur Sicherheit, bei großen Verzeichnissen könnte das Limit erreicht werden
	set_time_limit(600);
	
	// Verzeichnis einelsen
	$folder_elements = scandir($folder);
	// Alle Elemente durchgehen
	foreach ($folder_elements as $folder_element) {
		// Übergehen der übergeordneten Ebene 
		if ($folder_element != ".." && $folder_element != ".") {
			// Ist Element ein Verzeichnis?
			if (is_dir($folder."/".$folder_element)) {
				// Verzeichnis löschen
				remove_folder($folder."/".$folder_element);
			} else {
				// Datei löschen
				unlink($folder."/".$folder_element);
			}
		}
	}
	// Verzeichnis ist jetzt leer und kann gelöscht werden
	rmdir($folder);
}

// Diese Funktion verschiebt das Verzeichnis $source inklusive aller Unterverzeichnisse nach $target
// Man könnte hier noch viel Fehlerbehandlung einfügen... TODO
function move_folder($source, $target) {
	
	// Zur Sicherheit, bei großen Verzeichnissen könnte das Limit erreicht werden
	set_time_limit(600);
	
	// Zielverzeichnis erstellen, falls noch nicht vorhanden
	if (!file_exists($target)) {
		// Zielverzeichnis erstellen
		@mkdir($target."/".$source_folder_element, CHMOD, true);
	}
	
	// Verzeichnis einelsen
	$source_folder_elements = scandir($source);
	// Alle Elemente durchgehen
	foreach ($source_folder_elements as $source_folder_element) {
		// Übergehen der übergeordneten Ebene 
		if ($source_folder_element != ".." && $source_folder_element != ".") {
			// Ist Element ein Verzeichnis?
			if (is_dir($source."/".$source_folder_element)) {
				// Quelleverzeichnis ins Zielverzeichnis verschieben
				move_folder($source."/".$source_folder_element, $target."/".$source_folder_element);
			} else {
				// Datei Verschieben
				//echo "copy(".$source."/".$source_folder_element.", ".$target."/".$source_folder_element.")\n";
				copy($source."/".$source_folder_element, $target."/".$source_folder_element);
				unlink($source."/".$source_folder_element);
			}
		}
	}
	// Quellverzeichnis löschen
	rmdir($source);
}