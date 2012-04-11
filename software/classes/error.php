<?php

/**
 * Diese Klasse error gibt Markup für Fehlermeldungen zurück.
 */
class errors {

	private $document;


	public function __construct($document) {
		$this->document = $document;
	}


	public function SQLError ($SQLQuery, $errorMessage) {
		$errorDiv = $this->document->createElement('div');
		$errorDiv->setAttribute('class', 'error');

		$errorPre = $this->document->createElement('pre');
		$errorPre->appendChild($this->document->createTextNode($SQLQuery));
		$errorDiv->appendChild($errorPre);

		$errorDiv->appendChild($this->document->createTextNode('führte zu'));

		$errorEm = $this->document->createElement('em');
		$errorEm->appendChild($this->document->createTextNode($errorMessage));
		$errorDiv->appendChild($errorEm);

		return $errorDiv;
	}

}

?>
