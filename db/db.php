<?php

try {

	/**
	 * Open/create sqlite database
	 */

	$db = new PDO('sqlite:'.Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$dbCacheFolder.DIRECTORY_SEPARATOR.Config::$dbFilename);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
	echo $e->getMessage();
}