<?php

abstract class command {

	protected $document;

	protected $contentElement;

	protected $headElement;



	abstract public function getContent ();



	public static function subclassForCommand ($command) {
		$commandObject;

		switch($command) {
			case 'add_oai_source':
				require_once(dirname(__FILE__) . '/command_add_oai_source.php');
				$commandObject = new command_addOAISource();
				break;
			case 'save_oai_source':
				require_once(dirname(__FILE__) . '/command_save_oai_source.php');
				$commandObject = new command_saveOAISource();
				break;
			case 'list_oai_sources':
				require_once(dirname(__FILE__) . '/command_list_oai_sources.php');
				$commandObject = new command_listOAISources();
				break;
			case 'edit_oai_source':
				require_once(dirname(__FILE__) . '/command_edit_oai_source.php');
				$commandObject = new command_editOAISource();
				break;
			case 'update_oai_source':
				require_once(dirname(__FILE__) . '/command_update_oai_source.php');
				$commandObject = new command_updateOAISource();
				break;
			case 'show_oai_source':
				require_once(dirname(__FILE__) . '/command_show_oai_source.php');
				$commandObject = new command_showOAISource();
				break;
			case 'delete_oai_source':
				require_once(dirname(__FILE__) . '/command_delete_oai_source.php');
				$commandObject = new command_listOAISource();
				break;
			case 'preview_oai_set':
				require_once(dirname(__FILE__) . '/command_preview_oai_set.php');
				$commandObject = new command_previewOAISet();
				break;
			case 'display_log':
				require_once(dirname(__FILE__) . '/command_display_log.php');
				$commandObject = new command_displayLog();
				break;
			default:
				require_once(dirname(__FILE__) . '/command_start.php');
				$commandObject = new command_start();
		}

		return $commandObject;
	}


	public function setupTemplate () {
		$this->document = DOMImplementation->createDocument();

		$htmlElement = $this->document->createElement('html');
		$htmlElement->setAttribute('lang', 'de');
		$this->document->appendChild($htmlElement)

		$this->headElement = $this->document->createElement('head');
		$htmlElement->appendChild($this->headElement);

		$metaElement = $this->document->createElement('meta');
		$metaElement->setAttribute('http-equiv', 'Content-Type');
		$metaElement->setAttribute('content', 'text/html;charset=utf-8');
		$this->headElement->appendChild($metaElement);

		$titleElement = $this->document->createElement('title');
		$titleElement->appendChild($this->document->createTextNode(SERVICE_NAME . ' OAI-Harvester'));
		$this->headElement->appendChild($titleElement);

		$this->addCSSToHead('resources/css/oai_harvester.css');
		$this->addCSSToHead('resources/css/jquery-ui-1.8.18.custom.css');

		$this->addJSToHead('resources/javascript/jquery-1.7.1.min.js');
		$this->addJSToHead('resources/javascript/jquery-ui-1.8.18.custom.min.js');
		$this->addJSToHead('resources/javascript/jquery.uitablefilter.js');
		$this->addJSToHead('resources/javascript/jquery.tablesorter.min.js');
		$this->addJSToHead('resources/javascript/common.js');
		$this->addJSToHead('resources/javascript/add_oai_source.js');
		$this->addJSToHead('resources/javascript/edit_oai_source.js');
		$this->addJSToHead('resources/javascript/list_oai_sources.js');
		$this->addJSToHead('resources/javascript/preview_oai_set.js');

		$bodyElement = $this->document->createElement('body');
		$htmlElement->appendChild($bodyElement);

		$mainDivElement = $this->document->createElement('div');
		$mainDivElement->setAttribute('id', 'main');

		$h1Element = $this->document->createElement('h1');
		$h1Element->appendChild($this->document->createTextNode(SERVICE_NAME . ' OAI-Harvester'));
		$mainDivElement->appendChild($h1Element);

		$this->contentElement = $this->document->createElement('div');
		$this->contentElement->setAttribute('id', 'content');
		$mainDivElement->appendChild($this->contentElement);
	}


	public function addCSSToHead ($URL) {
		if ($this->headElement && $URL) {
			$linkElement = $this->document->createElement('link');
			$linkElement->setAttribute('href', $URL);
			$linkElement->setAttribute('type', 'text/css');
			$linkElement->setAttribute('rel', 'stylesheet');
			$this->headElement->appendChild($linkElement);
		}
	}


	public function addJSToHead ($URL) {
		if ($this->headElement && $URL) {
			$scriptElement = $this->document->createElement('script');
			$scriptElement->setAttribute('src', $URL);
			$scriptElement->setAttribute('type', 'text/javascript');
			$this->headElement->appendChild($scriptElement);
		}
	}


	public function run () {
		$output = $this->getContent();

		return $output;
	}

}

?>
