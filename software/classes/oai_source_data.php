<?php

/*
 * Diese Klasse lädt die Daten einer OAI-Quelle und stellt sie stellt
 * sie unter einer Schnitstelle zur Verfügung.
 * 
 * Woher die Daten stammen, muss lediglich bei der Instanziierung angegeben werden.
 */


class oai_source_data {
	
	private $source_data = array();
	
	
	// Beim Typ "post" und "get" werden die anderen Übergabewerte eifnach auf 0 gesetzt sein.
	// Beim Typ "db" ist dier Link und die ID der OAI-Quelle zu übergeben
	public function __construct($type, $db_link, $id) {
		
		switch ($type) {
			
			case "post":
				
				$this->source_data['name'] = $_POST['name'];
				$this->source_data['url'] = $_POST['url'];
				$this->source_data['country'] = $_POST['country'];
				$this->source_data['from'] = $_POST['from'];
				$this->source_data['harvest_period'] = $_POST['harvest_period'];
				$this->source_data['comment'] = $_POST['comment'];
				
				$this->source_data['index_relation'] = isset($_POST['index_relation']) ? 1 : 0;
				$this->source_data['index_creator'] = isset($_POST['index_creator']) ? 1 : 0;
				$this->source_data['index_contributor'] = isset($_POST['index_contributor']) ? 1 : 0;
				$this->source_data['index_publisher'] = isset($_POST['index_publisher']) ? 1 : 0;
				$this->source_data['index_date'] = isset($_POST['index_date']) ? 1 : 0;
				$this->source_data['index_subject'] = isset($_POST['index_subject']) ? 1 : 0;
				$this->source_data['index_description'] = isset($_POST['index_description']) ? 1 : 0;
				$this->source_data['index_identifier'] = isset($_POST['index_identifier']) ? 1 : 0;
				$this->source_data['index_source'] = isset($_POST['index_source']) ? 1 : 0;
				
				$this->source_data['dc_date_postproc'] = $_POST['dc_date_postproc'];
				
				$this->source_data['view_creator'] = isset($_POST['view_creator']) ? 1 : 0;
				$this->source_data['view_contributor'] = isset($_POST['view_contributor']) ? 1 : 0;
				$this->source_data['view_publisher'] = isset($_POST['view_publisher']) ? 1 : 0;
				$this->source_data['view_date'] = isset($_POST['view_date']) ? 1 : 0;
				$this->source_data['view_identifier'] = isset($_POST['view_identifier']) ? 1 : 0;

				$this->source_data['active'] = isset($_POST['active']) ? 1 : 0;
				$this->source_data['identifier_filter'] = $_POST['identifier_filter'];
				$this->source_data['identifier_resolver'] = $_POST['identifier_resolver'];
				$this->source_data['identifier_resolver_filter'] = $_POST['identifier_resolver_filter'];
				$this->source_data['identifier_alternative'] = $_POST['identifier_alternative'];
				
				
				$this->source_data['sets'] = $_POST['sets'];
				
				break;
			
			case "get":
				
				break;
			
			case "db":
				
				break;
			
		}
	}
	
	/*******************************
	/* Get-Methoden für alle Daten *
	/*******************************/
	
	// Allgemein
	public function getName() {
		return $this->source_data['name'];
	}
	public function getUrl() {
		return $this->source_data['url'];
	}
	public function getCountry() {
		return $this->source_data['country'];
	}
	public function getFrom() {
		return $this->source_data['from'];
	}
	public function getHarvestPeriod() {
		return $this->source_data['harvest_period'];
	}
	public function getActive() {
		return $this->source_data['active'];
	}
	public function getComment() {
		return $this->source_data['comment'];
	}
	
	// Nachbearbeitung
	public function getDcDatePostproc() {
		return $this->source_data['dc_date_postproc'];
	}	
	
	// Indexfelder
	public function getIndexRelation() {
		return $this->source_data['index_relation'];
	}
	public function getIndexCreator() {
		return $this->source_data['index_creator'];
	}
	public function getIndexContributor() {
		return $this->source_data['index_contributor'];
	}
	public function getIndexPublisher() {
		return $this->source_data['index_publisher'];
	}
	public function getIndexDate() {
		return $this->source_data['index_date'];
	}
	public function getIndexSubject() {
		return $this->source_data['index_subject'];
	}
	public function getIndexDescription() {
		return $this->source_data['index_description'];
	}
	public function getIndexIdentifier() {
		return $this->source_data['index_identifier'];
	}
	public function getIndexSource() {
		return $this->source_data['index_source'];
	}
	
	// Anzeigefelder
	public function getViewCreator() {
		return $this->source_data['view_creator'];
	}
	public function getViewContributor() {
		return $this->source_data['view_contributor'];
	}
	public function getViewPublisher() {
		return $this->source_data['view_publisher'];
	}
	public function getViewDate() {
		return $this->source_data['view_date'];
	}
	public function getViewIdentifier() {
		return $this->source_data['view_identifier'];
	}
	
	// Linkeinstellungen
	public function getIdentifierFilter() {
		return $this->source_data['identifier_filter'];
	}
	public function getIdentifierResolver() {
		return $this->source_data['identifier_resolver'];
	}
	public function getIdentifierResolverFilter() {
		return $this->source_data['identifier_resolver_filter'];
	}
	public function getIdentifierAlternative() {
		return $this->source_data['identifier_alternative'];
	}
	
	// Sets
	public function getSets() {
		return $this->source_data['sets'];
	}
	
}

?>