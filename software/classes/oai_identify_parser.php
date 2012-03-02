<?php

/*
 * Diese Klasse parsed die Identify-Antwort einer OAI-Quelle.
 */



class oai_identify_parser {
	
	private $repositoryName = "";
	private $baseURL = "";
	private $protocolVersion = "";
	private $earliestDatestamp = "";
	private $deletedRecord = "";
	private $granularity = "";
	private $adminEmail =  array();
	
	private $valid = true;
	
	public function __construct($identify_response) {
		
		$dom = new DOMDocument();
		
		if ($dom->loadXML($identify_response)) {
			
			$element = $dom->getElementsByTagName('repositoryName');
			$this->repositoryName = @$element->item(0)->nodeValue;
			unset($element);
			
			$element = $dom->getElementsByTagName('baseURL');
			$this->baseUrl = $element->item(0)->nodeValue;
			unset($element);
			
			$element = $dom->getElementsByTagName('protocolVersion');
			$this->protocolVersion = $element->item(0)->nodeValue;
			unset($element);
			
			$element = $dom->getElementsByTagName('earliestDatestamp');
			$this->earliestDatestamp = @$element->item(0)->nodeValue;
			unset($element);
			
			$element = $dom->getElementsByTagName('deletedRecord');
			$this->deletedRecord = $element->item(0)->nodeValue;
			unset($element);
			
			$element = $dom->getElementsByTagName('granularity');
			$this->granularity = $element->item(0)->nodeValue;
			unset($element);
			
			$elements =$dom->getElementsByTagName('adminEmail');
			foreach ($elements as $element) {
				$this->adminEmail[] = $element->nodeValue;
			}
			
			// Mindestens der Name und die URL sollten gesetzt sein, sonst wird die Quelle
			// als nicht valide angesehen. Eigentlich sind alle abgefragten Elemente Pflicht...
			
			if (strlen($this->repositoryName) == 0 || strlen($this->baseUrl) == 0) {
				$this->valid = false;
			}
			
		} else {
			$this->valid = false;
		}
	}

	// Konnte das XML geparsed werden?
	public function isResponseValid() {
		return $this->valid;
	}

	// GET-Funktionen für alle Instanzvariablen.
	
	public function getRepositoryName() {
		return $this->repositoryName;
	}
	
	public function getBaseUrl() {
		return $this->baseUrl;
	}
	
	public function getProtocolVersion() {
		return $this->protocolVersion;
	}
	
	public function getEarliestDatestamp() {
		return $this->earliestDatestamp;
	}
	
	public function getDeletedRecord() {
		return $this->deletedRecord;
	}
	
	public function getGranularity() {
		return $this->granularity;
	}
	
	public function getAdminEmail() {
		return $this->adminEmail;
	}
}

?>