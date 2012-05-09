<?php

require_once(dirname(__FILE__) . '/commands.php');


/**
 * Befehl zum Speichern einer neue OAI-Quelle in der Datenbank.
 * Gibt eine Meldung über den Erfolg des Speicherns.
 */
class command_saveOAISource extends command {

	public function appendContent () {

		$sql = "INSERT INTO oai_sources (
					url,
					name," .
					/*
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
					*/
					"identifier_filter,
					identifier_resolver,
					identifier_resolver_filter,
					identifier_alternative,
					country_code,
					active,
					added,
					" . (strlen($this->parameters['from']) === 10 ?  "from, " : "" )."
					`harvest_period`,
					" . (strlen($this->parameters['from']) === 10 ?  "last_harvest, ": "" )."
					`reindex`,
					`comment` )
				VALUES (
				'" . mysql_real_escape_string($this->parameters['url']) . "',
				'" . mysql_real_escape_string($this->parameters['name']) . "'," .
				/*
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
				*/
				"'" . mysql_real_escape_string($this->parameters['identifier_filter']) . "',
				'" . mysql_real_escape_string($this->parameters['identifier_resolver']) . "',
				'" . mysql_real_escape_string($this->parameters['identifier_resolver_filter']) . "',
				'" . mysql_real_escape_string($this->parameters['identifier_alternative']) . "',
				'" . mysql_real_escape_string($this->parameters['country']) . "',
				" . (isset($this->parameters['active']) ? 1 : 0) . ",
				NOW(),
				" . (strlen($this->parameters['from']) === 10 ? "'" . mysql_real_escape_string($this->parameters['from']) . "', " : "") ."
				'" . mysql_real_escape_string($this->parameters['harvest_period']) . "',
				" . (strlen($this->parameters['from']) === 10 ?  "'" . mysql_real_escape_string($this->parameters['from']) . "', " : "") ."
				0,
				'" . mysql_real_escape_string($this->parameters['comment']) . "')";


		if (mysql_query($sql, $this->db_link)) {
			$source_id = mysql_insert_id($this->db_link);

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

			$sets = $this->parameters['sets']['unchanged'];
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

			if (mysql_query($sql, $this->db_link)) {
				$this->contentElement->appendChild($this->makeElementWithText('p', 'OAI-Quelle gespeichert.'));
				$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zur Startseite'));
			}
			else {
				$p = $this->makeElementWithText('p', 'Die Sets konnten nicht gespeichert werden. Bitte die OAI-Quelle (zum Beispiel über phpMyAdmin) löschen und ggf. neu anlegen.');
				$this->contentElement->appendChild($p);
				$p->setAttribute('class', 'error');

				$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zurück'));
			}
		}
		else {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			$this->contentElement->appendChild($this->makeFormWithSubmitButton('Zurück'));
		}
	}

}

?>
