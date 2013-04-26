<?php

require 'config.php';

/**
 * Test for initialization
 */
if (!file_exists(Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$installCompleteFile)) {
	header('Location: install.php');
	exit;
}

require '../db/db.php';

