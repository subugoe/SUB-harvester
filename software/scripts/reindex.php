<?php
/**
 * Reindex all records for the current harvesting setup.
 *
 * 2012 by Sven-S. Porst, SUB Göttingen <porst@sub.uni-goettingen.de>
 *
 *
 * 1. Read the harvesting setup from HARVESTER_CONFIGURATION_NAME
 * 2. from the archive folder of that configuration:
 *    a) unzip harvest folders and move unzipped data
 *    b) start indexing
 *    b) remove newly created zip files from archive folder
 * 			(to avoid duplicate records in the archive)
 */


require_once(dirname(__FILE__) . '/scripts_funcs.php');


$dataFolder = realpath(dirname(__FILE__) . '/../../data');
$configurationName = getenv('HARVESTER_CONFIGURATION_NAME');
if ($configurationName) {
	$dataFolder .= '/' . $configurationName;
}

$archiveFolder = $dataFolder . '/archive';
$tempFolder = $dataFolder . '/temp';
$harvestFolder = $dataFolder . '/harvest';

$archiveFileList = glob($archiveFolder . '/*.zip');

/**
 * a) unzip harvest folders and move unzipped data
 */
foreach ($archiveFileList as $id => $zipPath) {
	$zip = new ZipArchive;
	if ($zip->open($zipPath)) {
		echo("Extracting zip file " . $zipPath . "\n");

		// extract to temp folder
		$name = 'reindex-' . $id;
		$targetPath = $tempFolder . '/' . $name;
		$zip->extractTo($targetPath);
		$zip->close();

		// move interesting subfolder to harvest folder
		$harvestSubfolder = $targetPath . '/harvest';
		if (!rename($harvestSubfolder, $harvestFolder . '/' . $name)) {
			echo("Could not move " . $harvestSubfolder . " to harvest folder.\n");
		}

		// clean up temp folder
		remove_folder($targetPath);
	}
	else {
		echo("Cannot open zip-File " . $zipPath . "\n");
	}
}


/**
 * b) start indexing
 */
echo("\nStart Indexing…\n");
exec('/usr/bin/env php -f "' . dirname(__FILE__) . '/indexer.php"');
echo("\n");


/**
 * c) remove newly created zip files from archive folder
 */
$reindexArchiveFileList = glob($archiveFolder . '/reindex*.zip');
foreach ($reindexArchiveFileList as $zipPath) {
	if (unlink($zipPath)) {
		echo("Deleted file " . $zipPath . "\n");
	}
	else {
		echo("Could not delete file " . $zipPath . "\n");
	}
}


?>
