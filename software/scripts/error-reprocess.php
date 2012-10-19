<?php
/**
 * Reindex all records for the current harvesting setup.
 *
 * 2012 by Sven-S. Porst, SUB Göttingen <porst@sub.uni-goettingen.de>
 *
 *
 * 1. Read the harvesting setup from HARVESTER_CONFIGURATION_NAME
 * 2. go through all *timestamp* subfolders inside the 'error' folder for that configuration
 *    a) create a 'error-reprocess-*timestamp*' folder in 'harvest'
 *	  b) copy the content of the *timestamp* folder’s harvesting subfolder
 * 			into the folder created in a)
 *    c) start indexing
 */


require_once(dirname(__FILE__) . '/scripts_funcs.php');


$dataFolder = realpath(dirname(__FILE__) . '/../../data');
$configurationName = getenv('HARVESTER_CONFIGURATION_NAME');
if ($configurationName) {
	$dataFolder .= '/' . $configurationName;
}

$errorFolder = $dataFolder . '/error';
$tempFolder = $dataFolder . '/temp';
$harvestFolder = $dataFolder . '/harvest';

$errorSubfolderList = glob($errorFolder . '/*', GLOB_ONLYDIR);
foreach ($errorSubfolderList as $errorSubfolder) {
	$subfolderName = pathinfo($errorSubfolder, PATHINFO_BASENAME);
	$OAITargetIDFolderList = glob($errorSubfolder . '/indexing/*', GLOB_ONLYDIR);
	foreach ($OAITargetIDFolderList as $OAITargetIDFolder) {
		$OAITargetID = pathinfo($OAITargetIDFolder, PATHINFO_BASENAME);
		$OAISetIDFolderList = glob($OAITargetIDFolder . '/*', GLOB_ONLYDIR);
		foreach ($OAISetIDFolderList as $OAISetIDFolder) {
			$OAISetID = pathinfo($OAISetIDFolder, PATHINFO_FILENAME);
			$reprocessFolder = $harvestFolder . '/error-reprocess-' . $subfolderName . '/' . $OAITargetID . '/' . $OAISetID;
			if (mkdir($reprocessFolder, 0700, TRUE)) {
				echo('Created folder: ' . $reprocessFolder . "\n");
				$OAIDataFolder = $OAISetIDFolder . '/oai';
				$OAIFiles = glob($OAIDataFolder . '/*');
				echo ('Copying into ' . $reprocessFolder . ': ' . $OAIDataFolder. "\n");
				foreach($OAIFiles as $OAIFile) {
					$fileName = pathinfo($OAIFile, PATHINFO_BASENAME);
					if (!copy($OAIFile, $reprocessFolder . '/' . $fileName)) {
						echo('ERROR: Could not copy ' . $OAIFile . ' to ' . $reprocessFolder);
					}
				}
			}
			else {
				echo('ERROR: Could not create folder: ' . $reprocessFolder . "\n");
			}
		}
	}
}


/**
 * b) start indexing
 */
echo("\nStart Indexing…\n");
exec('/usr/bin/env php -f "' . dirname(__FILE__) . '/indexer.php"');
echo("\n");


?>
