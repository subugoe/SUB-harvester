<?php

require_once(dirname(__FILE__) . '/commands.php');
require_once(dirname(__FILE__) . '/../classes/log.php');


/**
 * Befehl für die Startseite des Harvesters
 */
class command_start extends command {

	private function commandForm () {
		$commandForm = $this->makeForm();
		$commandForm->setAttribute('class', 'hidden');

		$commandForm->appendChild($this->makeInput('hidden', 'id'));
		$commandForm->appendChild($this->makeInput('hidden', 'do', $this->commandName));

		return $commandForm;
	}



	private function showAndEditSection () {
		$container = $this->document->createElement('div');

		$h2 = $this->document->createElement('h2');
		$container->appendChild($h2);
		$h2->appendChild($this->document->createTextNode('OAI-Quellen anzeigen und bearbeiten'));

		$form = $this->makeForm();
		$container->appendChild($form);
		$form->setAttribute('class', 'show-all');
		$form->appendChild($this->makeInput('submit', NULL, 'Alle OAI-Quellen anzeigen'));
		$form->appendChild($this->makeInput('hidden', 'do', 'list_oai_sources'));

		$container->appendChild($this->filterForm());

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

		return $container;
	}



	private function logSection () {
		$container = $this->document->createElement('div');
		$container->setAttribute('class', 'logs');

		$h2 = $this->document->createElement('h2');
		$container->appendChild($h2);
		$h2->appendChild($this->document->createTextNode('Logs'));

		$form = $this->makeForm();
		$container->appendChild($form);

		$form->appendChild($this->makeLabel('max_hit_display', 'Anzahl der Meldungen'));
		$select = $this->makeSelectWithOptions('limit_select', Array(
			Array('value' => 5),
			Array('value' => 10, 'defaultSelection' => TRUE),
			Array('value' => 50),
			Array('value' => 100),
			Array('value' => 200)
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'navigate(0)');
		$select->setAttribute('id', 'max_hit_display');

		$form->appendChild($this->makeLabel('show_status_select', 'Status:'));
		$select = $this->makeSelectWithOptions('show_status_select', Array(
			Array('value' => -1, 'label' => 'egal', 'defaultSelection' => TRUE),
			Array('value' => 0, 'label' => 'OK'),
			Array('value' => 1, 'label' => 'Fehler')
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'navigate(0)');
		$select->setAttribute('id', 'show_status_select');

		$form->appendChild($this->makeLabel('show_type_select', 'Typ:'));
		$select = $this->makeSelectWithOptions('show_type_select', Array(
			Array('value' => -1, 'label' => 'egal', 'defaultSelection' => TRUE),
			Array('value' => 0, 'label' => 'Harvester'),
			Array('value' => 1, 'label' => 'Indexer')
		));
		$form->appendChild($select);
		$select->setAttribute('onchange', 'navigate(0)');
		$select->setAttribute('id', 'show_type_select');

		$log = new log($this);
		$container->appendChild($log->getLogMessages(-1, -1, 10, 0));

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
