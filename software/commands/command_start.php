<?php

require_once(dirname(__FILE__) . '/commands.php');
require_once(dirname(__FILE__) . '/../classes/log.php');


/**
 * Befehl für die Startseite des Harvesters
 */
class command_start extends command {

	private function commandForm () {
		$commandForm = $this->makeForm();
		$commandForm->setAttribute('id', 'command');

		$commandForm->appendChild($this->makeInput('hidden', 'id'));
		$commandForm->appendChild($this->makeInput('hidden', 'do', $this->commandName));

		return $commandForm;
	}



	private function showAndEditSection () {
		$container = $this->document->createElement('div');
		$container->setAttribute('class', 'show-filter');

		$h2 = $this->document->createElement('h2');
		$container->appendChild($h2);
		$h2->appendChild($this->document->createTextNode('OAI-Quellen zeigen'));

		$form = $this->makeForm();
		$container->appendChild($form);
		$form->setAttribute('class', 'show-all');
		$form->appendChild($this->makeInput('submit', NULL, 'Alle OAI-Quellen zeigen'));
		$form->appendChild($this->makeInput('hidden', 'do', 'list_oai_sources'));

		$container->appendChild($this->document->createTextNode(' oder suchen nach '));

		$container->appendChild($this->filterForm());

		$container->appendChild($this->makeClear());

		return $container;
	}



	private function addSection () {
		$container = $this->document->createElement('div');
		$container->setAttribute('class', 'add-new');

		$h2 = $this->document->createElement('h2');
		$container->appendChild($h2);
		$h2->appendChild($this->document->createTextNode('OAI-Quelle hinzufügen'));

		$form = $this->makeForm();
		$container->appendChild($form);

		$form->appendChild($this->makeLabel('add_oai_source', 'Request URL:'));
		$form->appendChild($this->makeInput('text', 'add_oai_source'));

		$form->appendChild($this->makeInput('submit', NULL, 'OAI-Quelle hinzufügen'));
		$form->appendChild($this->makeInput('hidden', 'do', 'add_oai_source'));

		$container->appendChild($this->makeClear());

		return $container;
	}



	public function appendContent () {
		$this->contentElement->appendChild($this->commandForm());
		$this->contentElement->appendChild($this->showAndEditSection());
		$this->contentElement->appendChild($this->addSection());
		$this->contentElement->appendChild($this->logSection());
	}


}

?>
