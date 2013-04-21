<?php

try {

	/**
	 * Open/create sqlite database
	 */

	$db = new PDO('sqlite:'.dirname(__FILE__).'/sqlite/cross_reference.sqlite3');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	/**
	 * Initialize database
	 */
	if (file_exists(dirname(__FILE__).'/schema.sql')){
		$db->exec(file_get_contents(dirname(__FILE__).'/schema.sql'));
	} else {
		echo 'schema.sql not found';
	}

} catch (PDOException $e) {
	echo $e->getMessage();
}