<?php

require_once(dirname(__FILE__) . '/commands.php');

/**
 * Befehl zum Anzeigen der Liste bekannter OAI Quellen.
 */
class command_listOAISources extends command {

	protected function appendContent () {
		$this->clearEditLock();

		$this->contentElement->appendChild($this->harvestingOverview());
		$this->contentElement->appendChild($this->filterSection());


		// Template für Abfrage der ausgewählten OAI-Quellen erstellen (wird zum Zählen und für die Daten benötigt).
		$sql_query_select_oai_sources_where = "";
		$where_set = false;

		// Wird nach "aktiv" selektiert?
		if(array_key_exists('show_active', $this->parameters) && $this->parameters['show_active'] > 0) {
			$sql_query_select_oai_sources_where .= " WHERE";
			$where_set = true;

			$condition = (intval($this->parameters['show_active']) === 1) ? "TRUE" : "FALSE";
			$sql_query_select_oai_sources_where .= " oai_sources.active = ".$condition;

			unset($condition); // zur Sicherheit
		}

		// Wird nach "status" selektiert?
		if (array_key_exists('show_status', $this->parameters) && $this->parameters['show_status'] > 0) {
			$sql_query_select_oai_sources_where .= $where_set ? " AND" : " WHERE";
			$where_set = true;

			$condition = (intval($this->parameters['show_status']) === 1) ? "NOT IN" : "IN";
			$sql_query_select_oai_sources_where .= " oai_sources.id " . $condition . "
							(SELECT DISTINCT oai_source
							FROM oai_sets
							WHERE harvest_status > 0
							OR index_status > 0)";

			unset($condition); // zur Sicherheit
		}

		// Gibt es Textfilter?
		// Filter: Name
		$current_filter_name = '';
		if (array_key_exists('filter_name', $this->parameters)) {
			$current_filter_name = mysql_real_escape_string($this->parameters['filter_name']);
		}
		$current_filter_name_parsed = "";
		if (strlen($current_filter_name) >= 3) {
			$current_filter_name_single = explode(" ", $current_filter_name);

			foreach ($current_filter_name_single as $filter_name) {
				if (strlen($filter_name) >= 3) {
					$current_filter_name_parsed .= strlen($current_filter_name_parsed) == 0 ? "" : " ";
					$current_filter_name_parsed .= "+".$filter_name;
				}
			}
		}

		// Filter: URL
		$current_filter_url = '';
		if (array_key_exists('filter_url', $this->parameters)) {
			$current_filter_url = mysql_real_escape_string($this->parameters['filter_url']);
		}
		$current_filter_url_parsed = '';
		if (strlen($current_filter_url) >= 3) {
			$current_filter_url_single = explode(" ", $current_filter_url);

			foreach ($current_filter_url_single as $filter_url) {
				if (strlen($filter_url) >= 3) {
					$current_filter_url_parsed .= strlen($current_filter_url_parsed) == 0 ? "" : " AND ";
					$current_filter_url_parsed .= "url LIKE '%" . $filter_url . "%'";
				}
			}
		}

		$current_filter_bool = 'AND';
		if (array_key_exists('filter_bool', $this->parameters) && $this->parameters['filter_bool'] === 'OR') {
			$current_filter_bool = 'OR';
		}

		// WHERE Bedingung aufbauen
		if (strlen($current_filter_name_parsed) >= 3 || strlen($current_filter_url_parsed) >= 3) {
			$sql_query_select_oai_sources_where .= $where_set ? " AND (" : " WHERE (";

			// Zum "where" string hinzufügen
			if (strlen($current_filter_name_parsed) > 0) {
				$sql_query_select_oai_sources_where .= "MATCH (name) AGAINST ('" . $current_filter_name_parsed . "' IN BOOLEAN MODE)";
			}
			if (strlen($current_filter_name_parsed) > 0 && strlen($current_filter_url_parsed) > 0) {
				$sql_query_select_oai_sources_where .=  " " . $current_filter_bool . " ";
			}
			if (strlen($current_filter_url_parsed) > 0) {
				$sql_query_select_oai_sources_where .= $current_filter_url_parsed;
			}
			$sql_query_select_oai_sources_where .= ")";
		}

		// Abfrage der Anzahl der ausgewählten OAI-Quellen, JOIN wird wenn möglich übergangen
		if(array_key_exists('show_status', $this->parameters) && intval($this->parameters['show_status']) > 0) {
			$sql = "SELECT COUNT( DISTINCT oai_sources.id )
					FROM oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source" .
					$sql_query_select_oai_sources_where;
		} else {
			$sql = "SELECT COUNT( DISTINCT oai_sources.id )
					FROM oai_sources" .
					$sql_query_select_oai_sources_where;
		}


		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$this->contentElement->appendChild($error->SQLError($sql, mysql_error()));
			return;
		}
		$count_selected_oai_sources = mysql_result($result, 0);
		if($count_selected_oai_sources > 0) {

			$current_limit = 50;
			if (array_key_exists('limit', $this->parameters)) {
				$current_limit = intval($this->parameters['limit']);
			}

			$current_start = 0;
			if (array_key_exists('start', $this->parameters)) {
				$current_start = intval($this->parameters['start']);
			}

			$current_sortby = 'name';
			if (array_key_exists('sortby', $this->parameters)
				&& in_array($this->parameters['sortby'], Array('name', 'url', 'date')) ) {
				$current_sortby = $this->parameters['sortby'];
			}

			$current_sorthow = 'ASC';
			if (array_key_exists('sorthow', $this->parameters)
				&& $this->parameters['sorthow'] === 'DESC') {
				$current_sorthow = 'DESC';
			}


			$listDiv = $this->document->createElement('div');
			$this->contentElement->appendChild($listDiv);

			$listDiv->appendChild($this->makePager($current_limit, $current_start, $count_selected_oai_sources));

			$table = $this->makeTableStart($current_sortby);
			$listDiv->appendChild($table);

			// Erstellung der einzelnen Tabellenzeilen
			$sql = "SELECT
						oai_sources.id AS id,
						oai_sources.url AS url,
						oai_sources.name AS name,
						oai_sources.active AS active,
						DATE_FORMAT(oai_sources.added, '%e.%c.%Y - %k:%i') AS added_view,
						GREATEST(MAX(oai_sets.index_status), MAX(oai_sets.harvest_status)) AS status,
						COUNT(oai_sets.id) AS total_sets,
						SUM(oai_sets.harvest) AS active_sets
					FROM
						oai_sources INNER JOIN oai_sets ON oai_sources.id = oai_sets.oai_source"
				.	$sql_query_select_oai_sources_where
				."	GROUP BY oai_sources.id, oai_sources.name, oai_sources.url, oai_sources.active, oai_sources.added"
				."	ORDER BY " . $current_sortby . " " . $current_sorthow
				."	LIMIT " . $current_start . ", " . $current_limit;

			// echo $sql;

			$result = mysql_query($sql, $this->db_link);
			if (!$result) {
				$error = new error($this->document);
				$listDiv->appendChild($error->SQLError($sql, mysql_error()));
				return;
			}

			$tbody = $this->document->createElement('tbody');
			$table->appendChild($tbody);

			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				// Prüfen, die Quelle komplett geharvestet wird.
				// Ist nur nötig, wenn es nur ein "aktives" set gibt.
				if ($row['active_sets'] == 1) {
					$sql = "SELECT setSpec FROM oai_sets WHERE oai_source = " . $row['id'] . " AND harvest = 1";
					$result_allsets = mysql_query($sql, $this->db_link);
					if (!$result_allsets) {
						$error = new error($this->document);
						$listDiv->appendChild($error->SQLError($sql, mysql_error()));
						return;
					}
					$setSpec = mysql_fetch_array($result_allsets, MYSQL_ASSOC);

					if ($setSpec['setSpec']== 'allSets') {
						$row['allSets'] = TRUE;
					}
				}

				$tbody->appendChild($this->makeTRForServer($row));
			}

			$listDiv->appendChild($this->makePager($current_limit, $current_start, $count_selected_oai_sources));
		}
		else {
			$this->contentElement->appendChild($this->makeElementWithText('p', 'Keine OAI-Quellen gefunden.', 'no-results'));
		}
	}



	protected function harvestingOverview () {
		$container = $this->document->createElement('div');
		$h2 = $this->document->createElement('h2');
		$container->appendChild($h2);
		$h2->appendChild($this->document->createTextNode('OAI-Quellen'));

		$p = $this->document->createElement('p');
		$container->appendChild($p);

		// Abfrage der Anzahl der OAI-Quellen
		$sql = "SELECT COUNT('id') FROM oai_sources";
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$container->appendChild($error->SQLError($sql, mysql_error()));
			return $container;
		}
		$count_oai_sources = mysql_result($result, 0);

		// Abfrage der Anzahl der Sets, die Pseudosets "allSets" und "noSetSupport" werden ignoriert)
		$sql = "SELECT COUNT('id') AS count_oai_sets FROM oai_sets WHERE NOT setspec LIKE '%allSets%'";
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$container->appendChild($error->SQLError($sql, mysql_error()));
			return $container;
		}
		$count_oai_sets = mysql_result($result, 0);

		// Kein Mechanismus zur Unterscheidung von Plural und Singular.
		$p->appendChild($this->document->createTextNode('Zurzeit sind insgesamt '));
		$em = $this->document->createElement('em');
		$p->appendChild($em);
		$em->appendChild($this->document->createTextNode($count_oai_sources . ' OAI-Quellen'));
		$p->appendChild($this->document->createTextNode(' mit '));
		$em = $this->document->createElement('em');
		$p->appendChild($em);
		$em->appendChild($this->document->createTextNode($count_oai_sets . ' Sets'));
		$p->appendChild($this->document->createTextNode(' bekannt.'));
		$p->appendChild($this->document->createElement('br'));


		// Abfrage der Anzahl der aktiven OAI-Quellen
		$sql = "SELECT COUNT('id') AS count_active_oai_sources FROM oai_sources WHERE active = 1";
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$container->appendChild($error->SQLError($sql, mysql_error()));
			return $container;
		}
		$count_active_oai_sources = mysql_result($result, 0);

		// Abfrage der Anzahl der geharvesteten Sets
		$sql = "SELECT COUNT('id') AS count_oai_sets FROM oai_sets WHERE harvest = 1 AND NOT (setspec LIKE '%allSets%' OR setspec LIKE '%noSetSupport%')";
		$result = mysql_query($sql, $this->db_link);
		if (!$result) {
			$error = new error($this->document);
			$container->appendChild($error->SQLError($sql, mysql_error()));
			return $container;
		}
		$count_harvested_oai_sets = mysql_result($result, 0);

		$p->appendChild($this->document->createTextNode('Aus den '));
		$em = $this->document->createElement('em');
		$p->appendChild($em);
		$em->appendChild($this->document->createTextNode($count_active_oai_sources . ' aktiven OAI-Quellen'));
		$p->appendChild($this->document->createTextNode(' werden '));
		$em = $this->document->createElement('em');
		$p->appendChild($em);
		$em->appendChild($this->document->createTextNode($count_harvested_oai_sets . ' Sets'));
		$p->appendChild($this->document->createTextNode(' geharvestet.'));

		return $container;
	}



	private function filterSection () {
		$container = $this->document->createElement('div');

		$container->appendChild($this->filterForm());

		$form = $this->makeForm();
		$container->appendChild($form);
		$form->setAttribute('class', 'list-configuration');

		$form->appendChild($this->makeLabel('limit_select', 'Anzahl der Treffer:'));
		$select = $this->makeSelectWithOptions('limit_select', Array(
			Array('value' => 5),
			Array('value' => 10, 'defaultSelection' => TRUE),
			Array('value' => 50),
			Array('value' => 100),
			Array('value' => 200)
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'refresh()');
		$select->setAttribute('id', 'limit_select');

		$form->appendChild($this->makeLabel('show_active_select', 'Aktiv:'));
		$select = $this->makeSelectWithOptions('show_active_select', Array(
			Array('value' => 0, 'label' => 'egal', 'defaultSelection' => TRUE),
			Array('value' => 1, 'label' => 'aktiv'),
			Array('value' => 2, 'label' => 'inaktiv')
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'refresh()');
		$select->setAttribute('id', 'show_active_select');

		$form->appendChild($this->makeLabel('show_status_select', 'Status:'));
		$select = $this->makeSelectWithOptions('show_status_select', Array(
			Array('value' => 0, 'label' => 'egal', 'defaultSelection' => TRUE),
			Array('value' => 1, 'label' => 'OK'),
			Array('value' => 2, 'label' => 'Fehler')
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'refresh()');
		$select->setAttribute('id', 'show_status_select');

		return $container;
	}




	private function makeTRForServer ($row) {
		$tr = $this->document->createElement('tr');

		$a = $this->makeElementWithText('a', $row['name'], 'oai_source_link');
		$a->setAttribute('href', '#');
		$a->setAttribute('onclick', 'show(' . $row['id'] . ')');
		$tr->appendChild($this->makeElementWithContent('td', $a, 'column-name'));

		$tr->appendChild($this->makeElementWithText('td', $row['url'], 'column-url'));

		$tr->appendChild($this->makeElementWithText('td', $row['added_view'], 'column-added'));
		$setInfo = $row['active_sets'] . ' (' . (intval($row['total_sets']) - 1) . ')';
		if (array_key_exists('allSets', $row) && $row['allSets'] === TRUE) {
			$setInfo = '∞';
		}
		$tr->appendChild($this->makeElementWithText('td', $setInfo, 'column-sets'));


		$img = $this->document->createElement('img');
		if ($row['active']) {
			$img->setAttribute('src', 'resources/images/ok.png');
			$img->setAttribute('alt', 'OAI-Quelle wird geharvestet.');
		}
		else {
			$img->setAttribute('src', 'resources/images/not_ok.png');
			$img->setAttribute('alt', 'OAI-Quelle wird nicht geharvestet.');
		}
		$tr->appendChild($this->makeElementWithContent('td', $img, 'column-active'));

		if ($row['status'] > 0) {
			$img = $this->document->createElement('img');
			$img->setAttribute('src', 'resources/images/error.png');
			$img->setAttribute('alt', 'Fehler!');
			$td = $this->makeElementWithContent('td', $img, 'column-status');
		}
		else {
			$td = $this->document->createElement('td');
		}
		$tr->appendChild($td);

		$form = $this->makeForm();
		$button = $this->makeInput('button', NULL, 'Edit');
		$button->setAttribute('onclick', 'edit(' . $row['id'] . ')');
		$form->appendChild($button);
		$tr->appendChild($this->makeElementWithContent('td', $form, 'column-edit'));

		return $tr;
	}


	private function makeTableStart ($sortby) {
		$table = $this->document->createElement('table');
		$table->setAttribute('id', 'oai_sources_list');
		$table->setAttribute('class', 'sort-' . $sortby);

		// Tabellekopf mit den dynamischen Sortierungen
		$thead = $this->document->createElement('thead');
		$table->appendChild($thead);
		$tr = $this->document->createElement('tr');
		$thead->appendChild($tr);

		$tr->appendChild($this->makeSortableTH('name', 'Name'));
		$tr->appendChild($this->makeSortableTH('url', 'URL'));
		$tr->appendChild($this->makeSortableTH('added', 'Hinzugefügt'));
		$tr->appendChild($this->makeElementWithText('th', 'Sets'));
		$tr->appendChild($this->makeElementWithText('th', 'Akt'));
		$tr->appendChild($this->makeElementWithText('th', 'Status'));
		$tr->appendChild($this->makeElementWithText('th', ''));

		return $table;
	}


	private function makeSortableTH ($name, $label) {
		// Default sort direction.
		$sortdirection = 'ASC';
		if ($name === 'added') {
			$sortdirection = 'DESC';
		}

		// If we currently sort by this column, invert the current direction.
		if (array_key_exists('sorthow', $this->parameters)
			&& (array_key_exists('sortby', $this->parameters) && $this->parameters['sortby'] === $name) ) {
			if ($name === 'added') {
				if ($this->parameters['sorthow'] === 'DESC') {
					$sortdirection = 'ASC';
				}
			}
			else {
				if ($this->parameters['sorthow'] === 'ASC') {
					$sortdirection = 'DESC';
				}
			}
		}

		$a = $this->makeElementWithText('a', $label);
		$a->setAttribute('href', '#');
		$a->setAttribute('onclick', 'changeSort("' . $name . '", ' . $sortdirection . ')');

		$th = $this->makeElementWithContent('th', $a, 'column-' . $name);

		return $th;
	}


	private function makePager ($limit, $start, $total) {
		$pagerDiv = $this->document->createElement('div');
		$pagerDiv->setAttribute('class', 'pager');

		$firstButton = $this->makeInput('button', NULL, 'Zur 1. Seite');
		$pagerDiv->appendChild($firstButton);
		$firstButton->setAttribute('onclick', 'gotoFirstPage()');
		$firstButton->setAttribute('class', 'navigationButton first');
		if ($start === 0) {
			$firstButton->setAttribute('disabled', 'disabled');
		}

		$prevButton = $this->makeInput('button', NULL, 'Zurück');
		$pagerDiv->appendChild($prevButton);
		$prevButton->setAttribute('onclick', 'previous()');
		$prevButton->setAttribute('class', 'navigationButton previous');
		if ($start === 0) {
			$prevButton->setAttribute('disabled', 'disabled');
		}

		$nextButton = $this->makeInput('button', NULL, 'Weiter');
		$pagerDiv->appendChild($nextButton);
		$nextButton->setAttribute('onclick', 'next()');
		$nextButton->setAttribute('class', 'navigationButton next');
		if ($start + $limit > $total) {
			$nextButton->setAttribute('disabled', 'disabled');
		}

		$pageInfo = $this->document->createElement('p');
		$pagerDiv->appendChild($pageInfo);
		$pageInfo->setAttribute('class', 'pageInfo');
		$pageInfo->appendChild($this->makeElementWithText('em', $start + 1));
		$pageInfo->appendChild($this->document->createTextNode(' bis '));
		$pageInfo->appendChild($this->makeElementWithText('em', min($limit + $start, $total)));
		$pageInfo->appendChild($this->document->createTextNode(' von '));
		$pageInfo->appendChild($this->makeElementWithText('em', $total));

		return $pagerDiv;
	}

}

?>
