<?php

/*
 * Speichert eine neue OAI-Quelle in der Datenbank.
 * Gibt eine Meldung über den Erfolg des Speicherns.
 */


require_once("./classes/oai_source_data.php");
require_once("./classes/button_creator.php");

$oai_source = new oai_source_data("post", 0, 0);

$sql = "INSERT INTO `oai_sources` (
			`url`, 
			`name`, 
			`view_creator`, 
			`view_contributor`, 
			`view_publisher`, 
			`view_date`, 
			`view_identifier`, 
			`index_relation`, 
			`index_creator`, 
			`index_contributor`, 
			`index_publisher`, 
			`index_date`, 
			`index_identifier`, 
			`index_subject`, 
			`index_description`, 
			`index_source`, 
			`dc_date_postproc`, 
			`identifier_filter`, 
			`identifier_resolver`, 
			`identifier_resolver_filter`, 
			`identifier_alternative`, 
			`country_code`, 
			`active`, 
			`added`, 
			".( strlen($oai_source->getFrom()) == 10 ? "`from`, " : "" )."
			`harvest_period`, 
			".( strlen($oai_source->getFrom()) == 10 ? "`last_harvest`, ": "" )."
			`reindex`, 
			`comment` ) 
		VALUES (
		'".mysql_real_escape_string($oai_source->getUrl())."', 
		'".mysql_real_escape_string(stripslashes($oai_source->getName()))."', 
		".$oai_source->getViewCreator().", 
		".$oai_source->getViewContributor().", 
		".$oai_source->getViewPublisher().", 
		".$oai_source->getViewDate().", 
		".$oai_source->getViewIdentifier().", 
		".$oai_source->getIndexRelation().", 
		".$oai_source->getIndexCreator().", 
		".$oai_source->getIndexContributor().", 
		".$oai_source->getIndexPublisher().", 
		".$oai_source->getIndexDate().", 
		".$oai_source->getIndexIdentifier().", 
		".$oai_source->getIndexSubject().", 
		".$oai_source->getIndexDescription().", 
		".$oai_source->getIndexSource().", 
		".$oai_source->getDcDatePostproc().", 
		'".mysql_real_escape_string(stripslashes($oai_source->getIdentifierFilter()))."', 
		'".mysql_real_escape_string(stripslashes($oai_source->getIdentifierResolver()))."',
		'".mysql_real_escape_string(stripslashes($oai_source->getIdentifierResolverFilter()))."',
		'".mysql_real_escape_string(stripslashes($oai_source->getIdentifierAlternative()))."',
		'".$oai_source->getCountry()."',
		'".$oai_source->getActive()."',
		NOW(), 
		". (strlen($oai_source->getFrom()) == 10 ? "'".$oai_source->getFrom()."', " : "") ." 
		'".$oai_source->getHarvestPeriod()."', 
		". (strlen($oai_source->getFrom()) == 10 ? "'".$oai_source->getFrom()."', " : "") ." 
		0,
		'".mysql_real_escape_string(stripslashes($oai_source->getComment()))."')";

// $content .= "<p>".$sql."</p>\n";

$button_creator = new button_creator();

if (mysql_query($sql, $db_link)) {
	
	$source_id = mysql_insert_id($db_link);
	
	$sql = "INSERT INTO `oai_sets` ( 
				`id`,
				`oai_source` ,
				`setSpec` ,
				`setName` ,
				`online` ,
				`harvest` ,
				`harvest_status` ,
				`index_status`
				)
				VALUES ";

	$sets = $oai_source->getSets();
	
	foreach($sets as $set) {
		$sql .= "(NULL , ". $source_id.", '".mysql_real_escape_string($set['setSpec'])."', '".mysql_real_escape_string(stripslashes($set['setName']))."', TRUE, ".(isset($set['harvest']) ? 1 : 0).", -1, -1), ";
	}
	
	$sql = substr($sql, 0, -2);
	
	// Debug-Code
	//$content .= "<p>".$sql."</p>";
	
	if (mysql_query($sql, $db_link)) {	
		$content .= "<p>OAI-Quelle gespeichert.</p>\n";
		$content .= $button_creator->createButton("Zur Startseite");
		
	} else {
		$content .= "<p>Die Sets konnten nicht gespeichert werden. Bitte OAI-Quelle (über phpMyAdmin) löschen und ggf. neu anlegen.</p>\n";
		$content .= $button_creator->createButton("Zurück");
	}
} else {
	$content .= "			<p>Fehler beim Speichern: ".mysql_error()."</p>\n";
	$content .= "<pre>$sql</pre>";
	$content .= $button_creator->createButton("Zurück");
}

?>