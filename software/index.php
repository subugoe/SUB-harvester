<?php

/***************************************************************
 **************                                  ***************
 **************   EROMM Search - OAI-Harvester   ***************
 **************                                  ***************
 ***************************************************************
 *
 * Diese Datei dient zur Konfiguration des OAI-Harvesters
 * von EROMM Search.
 *
 */

$mysq_error_message = "Fehler in der Datenbankabfrage";
$content = "";

// Funktionen einbinden
require_once(dirname(__FILE__) . '/scripts/scripts_funcs.php');
// Einstellungen laden
readConfiguration();

$parameters = $_POST;
$parameters = array_merge($parameters, $_GET);

// Befehlsklasse instanziieren und Befehl ausführen.
require_once(dirname(__FILE__) . "/commands/commands.php");

$commandName = 'start';
if (array_key_exists('do', $parameters)) {
	$commandName = $parameters['do'];
}

$command = command::subclassForCommand($commandName, $parameters);
if ($command) {
	$command->run();
	echo $command->document->saveHTML();
}


?>
