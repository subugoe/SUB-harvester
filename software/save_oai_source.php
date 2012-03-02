<?php

/*
 * Speichert eine neue OAI-Quelle in der Datenbank.
 * Gibt eine Meldung über den Erfolg des Speicherns.
 */


require_once(dirname(__FILE__) . "/classes/oai_source_data.php");
require_once(dirname(__FILE__) . "/classes/button_creator.php");

$oai_source = new oai_source_data("post", 0, 0);

$sql = "INSERT INTO oai_sources (
			url,
			name,
			view_creator,
			view_contributor,
			view_publisher,
			view_date,
			view_identifier,
			index_relation,
			index_creator,
			index_contributor,
			index_publisher,
			index_date,
			index_identifier,
			index_subject,
			index_description,
			index_source,
			dc_date_postproc,
			identifier_filter,
			identifier_resolver,
			identifier_resolver_filter,
			identifier_alternative,
			country_code,
			active,
			added,
			" . ( strlen($oai_source->getFrom()) == 10 ? "from, " : "" )."
			`harvest_period`,
			" . ( strlen($oai_source->getFrom()) == 10 ? "last_harvest, ": "" )."
			`reindex`, 
			`comment` ) 
		VALUES (
		'" . mysql_real_escape_string($oai_source->getUrl()) . "',
		'" . mysql_real_escape_string($oai_source->getName()) . "',
		" . intval($oai_source->getViewCreator()) . ",
		" . intval($oai_source->getViewContributor()) . ",
		" . intval($oai_source->getViewPublisher()) . ",
		" . intval($oai_source->getViewDate()) . ",
		" . intval($oai_source->getViewIdentifier()) . ",
		" . intval($oai_source->getIndexRelation()) . ",
		" . intval($oai_source->getIndexCreator()) . ",
		" . intval($oai_source->getIndexContributor()) . ",
		" . intval($oai_source->getIndexPublisher()) . ",
		" . intval($oai_source->getIndexDate()) . ",
		" . intval($oai_source->getIndexIdentifier()) . ",
		" . intval($oai_source->getIndexSubject()) . ",
		" . intval($oai_source->getIndexDescription()) . ",
		" . intval($oai_source->getIndexSource()) . ",
		" . intval($oai_source->getDcDatePostproc()) . ",
		'" . mysql_real_escape_string($oai_source->getIdentifierFilter()) . "',
		'" . mysql_real_escape_string($oai_source->getIdentifierResolver()) . "',
		'" . mysql_real_escape_string($oai_source->getIdentifierResolverFilter()) . "',
		'" . mysql_real_escape_string($oai_source->getIdentifierAlternative()) . "',
		'" . mysql_real_escape_string($oai_source->getCountry()) . "',
		" . intval($oai_source->getActive()) . ",
		NOW(), 
		" . (strlen($oai_source->getFrom()) == 10 ? "'" . mysql_real_escape_string($oai_source->getFrom()) . "', " : "") ."
		'" . mysql_real_escape_string($oai_source->getHarvestPeriod()) . "',
		" . (strlen($oai_source->getFrom()) == 10 ? "'" . mysql_real_escape_string($oai_source->getFrom()) . "', " : "") ."
		0,
		'" . mysql_real_escape_string($oai_source->getComment()) . "')";

// $content .= "<p>".$sql."</p>\n";

$button_creator = new button_creator();

if (mysql_query($sql, $db_link)) {
	
	$source_id = mysql_insert_id($db_link);
	
	$sql = "INSERT INTO oai_sets (
				id,
				oai_source,
				setSpec,
				setName,
				online,
				harvest,
				harvest_status,
				index_status
				)
				VALUES ";

	$sets = $oai_source->getSets();
	
	foreach($sets as $set) {
		$sql .= "(NULL,"
				. intval($source_id) . ",
				'" . mysql_real_escape_string($set['setSpec']) . "',
				'" . mysql_real_escape_string($set['setName']) . "',
				TRUE," .
				(isset($set['harvest']) ? 1 : 0) .",
				-1,
				-1), ";
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