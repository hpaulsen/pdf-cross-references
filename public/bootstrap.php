<?php

require 'localConfig.php';

/**
 * Test for initialization
 */
if (!file_exists(Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$installCompleteFile)) {
	header('Location: install.php');
	exit;
}

require Config::$dbDirectory.DIRECTORY_SEPARATOR.'db.php';
