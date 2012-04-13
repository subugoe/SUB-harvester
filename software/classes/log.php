<?php

require_once(dirname(__FILE__) . '/error.php');

/**
 * Diese Klasse stellt Logmeldungen aus der Datenbank dar.
 */
class log {

	private $owner;



	public function __construct($owner) {
		$this->owner = $owner;
	}



	// Erzeugt gemäß der Parameter den Output, der aus einer
	// Tabelle besteht. Diese wird in die aufrufende Webseite eingebunden.
	// Ist eine Quellen-ID angegeben werden nur die Logmeldungen dieser Quelle
	// dargestellt und die Spalte Quelle entfällt.
	public function getLogMessages($status, $type, $limit, $start, $oai_source_id = NULL) {
		$logDiv = $this->owner->document->createElement('div');
		$logDiv->setAttribute('class', 'log');

		// Logmeldungen abfragen
		// SQL-Abfrage aufbauen
		$sql = "SELECT SQL_CALC_FOUND_ROWS
					oai_logs.status AS status,
					oai_logs.type AS type,
					DATE_FORMAT(oai_logs.time, '%d.%m.%Y, %k:%i') AS time,
					oai_logs.message AS message,
					oai_sets.setName AS set_name,
					oai_sets.setSpec AS set_spec,
					oai_sources.name AS source_name,
					oai_sources.id AS source_id
				FROM
					(oai_logs INNER JOIN oai_sets ON oai_logs.oai_set = oai_sets.id)
					INNER JOIN oai_sources ON oai_sets.oai_source = oai_sources.id ";

		// WHERE ermitteln
		if (!is_null($oai_source_id) || $status >= 0 || $type >= 0) {
			// WHERE-Klausel ist erforderlich
			$sql .= "WHERE ";

			if(!is_null($oai_source_id)) {
				// Nur Logmeldungen einer Quelle
				$sql .= "oai_sets.oai_source = " . intval($oai_source_id) . " ";
			}

			if($status >= 0) {
				// Wurde schon auf eine Quelle eingegrenzt?
				$sql .= is_null($oai_source_id) ? "" : "AND ";
				$sql .= "oai_logs.status = " . intval($status) . " ";
			}

			if($type >= 0) {
				// Wurde schon auf eine Quelle eingegrenzt?
				$sql .= !is_null($oai_source_id) || $status >= 0 ? "AND " : "";
				$sql .= "oai_logs.type = " . intval($type) . " ";
			}
		}

		$sql .= "ORDER BY oai_logs.time DESC ";
		$sql .=	"LIMIT " . intval($start) . ", " . intval($limit);
		$result = mysql_query($sql, $this->owner->db_link);
		if (!$result) {
			$error = new error($this->owner->document);
			$logDiv->appendChild($error->SQLError($sql, mysql_error()));
			return $logDiv;
		}

		$sql = "SELECT FOUND_ROWS()";
		$count = mysql_query($sql, $this->owner->db_link);
		if (!$count) {
			$error = new error($this->owner->document);
			$logDiv->appendChild($error->SQLError($sql, mysql_error()));
			return $logDiv;
		}
		$total_log_entries = mysql_result($count, 0);


		// Anzahl und Position ausgeben
		if ($total_log_entries === 0) {
			$logP = $this->owner->makeElementWithText('p', 'Keine Lognachrichten vorhanden.', 'log-no-messages');
			$logDiv->appendChild($logP);
		}
		else {
			$logDiv->appendChild($this->pager($limit, $start, $total_log_entries));

			$logTable = $this->owner->document->createElement('table');
			$class = 'log';
			if (is_null($oai_source_id)) {
				$class .= ' with-source-column';
			}
			$logTable->setAttribute('class', $class);
			$logDiv->appendChild($logTable);

			$thead = $this->owner->document->createElement('thead');
			$logTable->appendChild($thead);
			$tr = $this->owner->document->createElement('tr');
			$thead->appendChild($tr);

			$tr->appendChild($this->owner->document->createElement('th'));

			$tr->appendChild($this->owner->makeElementWithText('th', 'Zeit', 'column-time'));

			if (is_null($oai_source_id)) {
				$tr->appendChild($this->owner->makeElementWithText('th', 'Quelle', 'column-source'));
			}

			$tr->appendChild($this->owner->makeElementWithText('th', 'Set', 'column-set'));

			$tr->appendChild($this->owner->makeElementWithText('th', 'Meldung', 'column-message'));

			$tbody = $this->owner->document->createElement('tbody');
			$logTable->appendChild($tbody);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$tr = $this->owner->document->createElement('tr');
				$tbody->appendChild($tr);
				if ($row['status'] == 1) {
					$tr->setAttribute('class', 'log-error');
				}

				$img = $this->owner->document->createElement('img');
				if ($row['type'] == 0) {
					$img->setAttribute('src', 'resources/images/harvester.png');
					$img->setAttribute('alt', 'Harvester');
				}
				else {
					$img->setAttribute('src', 'resources/images/indexer.png');
					$img->setAttribute('alt', 'Indexer');
				}
				$tr->appendChild($this->owner->makeElementWithContent('td', $img, 'column-icon'));

				$tr->appendChild($this->owner->makeElementWithText('td', $row['time'], 'column-time'));

				if (is_null($oai_source_id)) {
					$link = $this->owner->makeElementWithText('a', $row['source_name']);
					$link->setAttribute('href', '#');
					$link->setAttribute('onclick', 'show(' . $row['source_id'] . ')');
					$tr->appendChild($this->owner->makeElementWithContent('td', $link, 'column-source'));
				}

				$setName = $row['set_name'];
				if ($setName === 'allSets') {
					$setName = '∞';
				}
				$tr->appendChild($this->owner->makeElementWithText('td', $setName, 'column-set'));

				$tr->appendChild($this->owner->makeElementWithText('td', $row['message'], 'column-message'));
			}

			$logDiv->appendChild($this->pager($limit, $start, $total_log_entries));
		}

		return $logDiv;
	}



	// Erzeugt Pager mit Knöpfen zum Vor- und Zurücknavigieren und
	// Anzeige der aktuellen Position.
	private function pager ($limit, $start, $total_log_entries) {
		$pagerDiv = $this->owner->document->createElement('div');
		$pagerDiv->setAttribute('class', 'pager');

		if ($start != 0) {
			$firstButton = $this->owner->document->createElement('input');
			$firstButton->setAttribute('type', 'button');
			$firstButton->setAttribute('value', 'Neueste');
			$firstButton->setAttribute('onclick', 'navigate(0)');
			if ($start === 0) {
				$firstButton->setAttribute('disabled', 'disabled');
			}
			$firstButton->setAttribute('class', 'navigationButton first');
			$pagerDiv->appendChild($firstButton);

			$prevButton = $this->owner->document->createElement('input');
			$prevButton->setAttribute('type', 'button');
			$prevButton->setAttribute('value', 'Neuere');
			$prevButton->setAttribute('onclick', 'navigate(' . ($start - $limit) . ')');
			$prevButton->setAttribute('class', 'navigationButton previous');
			$pagerDiv->appendChild($prevButton);
		}
		if ($start + $limit < $total_log_entries) {
			$nextButton = $this->owner->document->createElement('input');
			$nextButton->setAttribute('type', 'button');
			$nextButton->setAttribute('value', 'Ältere');
			$nextButton->setAttribute('onclick', 'navigate(' . ($start + $limit) . ')');
			$nextButton->setAttribute('class', 'navigationButton next');
			$pagerDiv->appendChild($nextButton);
		}

		$pageInfo = $this->owner->document->createElement('p');
		$pageInfo->setAttribute('class', 'pageInfo');
		$infoString = ($start+1) . ' bis ' . min($limit + $start, $total_log_entries) . ' von ' . $total_log_entries;
		$pageInfo->appendChild($this->owner->document->createTextNode($infoString));
		$pagerDiv->appendChild($pageInfo);

		return $pagerDiv;
	}

}

?>
