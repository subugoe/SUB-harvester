#!/usr/bin/env php
<?php
/**
 *	Naive script to start harvesting and indexing from a cronjob.
 *
 *  Goes through the subfolders of the configuration folder, assuming
 *  each of their names is an active service and kicks off harvesting
 *  and indexing for each of them one-by-one.
 *
 *  March 2012, Sven-S. Porst, SUB Göttingen, <porst@sub.uni-goettingen.de>
 */

$serviceFolders = glob(dirname(__FILE__) . '/../../configuration/*', GLOB_ONLYDIR);

foreach ($serviceFolders as $serviceFolder) {
	$servicePath = pathinfo($serviceFolder);
	$serviceName = $servicePath['basename'];
	putenv('HARVESTER_CONFIGURATION_NAME=' . $serviceName);

	exec('/usr/bin/env php -f "' . dirname(__FILE__) . '/harvester.php"');
	exec('/usr/bin/env php -f "' . dirname(__FILE__) . '/indexer.php"');
}

return 0;
