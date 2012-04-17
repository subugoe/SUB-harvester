<?php

/**
 * Diese Klasse error gibt Markup für Fehlermeldungen zurück.
 */
class error {

	private $document;


	public function __construct($document) {
		$this->document = $document;
	}


	public function SQLError ($SQLQuery, $errorMessage) {
		$errorDiv = $this->document->createElement('div');
		$errorDiv->setAttribute('class', 'error');

		$errorDiv->appendChild($this->makeElementWithText('pre', $SQLQuery));
		$errorDiv->appendChild($this->document->createTextNode('führte zu'));
		$errorDiv->appendChild($this->makeElementWithText('em', $errorMessage));

		return $errorDiv;
	}

}

?>
