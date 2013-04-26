<?php

class Config {
	/** @var string Path to a writable cache directory */
	public static $cacheDirectory;
	/** @var string folder name under cache directory where db will be stored */
	public static $dbCacheFolder;
	/** @var string folder name under cache directory where documents will be stored */
	public static $documentCacheFolder;
	/** @var string folder name under cache directory where document parsed texts will be stored */
	public static $documentTextCacheFolder;
	/** @var string filename for the sqlite database (will be stored in $dbCacheFolder) */
	public static $dbFilename;
	/** @var string filename for the file that indicates whether the installation was successful (will be stored in $cacheDirectory) */
	public static $installCompleteFile;

	public static function complete() {
		return count(self::errors()) == 0;
	}

	public static function errors() {
		$result = array();
		if (!isset(self::$cacheDirectory) || strlen(self::$cacheDirectory) == 0)
			$result[] = '$cacheDirectory: Cache directory is not defined';
		if (!isset(self::$dbCacheFolder) || strlen(self::$dbCacheFolder) == 0)
			$result[] = '$dbCacheFolder: Cache folder for database is not defined';
		if (!isset(self::$documentCacheFolder) || strlen(self::$documentCacheFolder) == 0)
			$result[] = '$documentCacheFolder: Cache folder for documents is not defined';
		if (!isset(self::$documentTextCacheFolder) || strlen(self::$documentTextCacheFolder) == 0)
			$result[] = '$documentTextCacheFolder: Cache folder for document transcriptions is not defined';
		if (!isset(self::$dbFilename) || strlen(self::$dbFilename) == 0)
			$result[] = '$dbFilename: Database filename is not defined';
		if (!isset(self::$installCompleteFile) || strlen(self::$installCompleteFile) == 0)
			$result[] = '$installCompleteFile: Installation complete filename is not defined';
		return $result;
	}
}

$p = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'cache';
$p = preg_replace('/\w+\/\.\.\//', '', $p);
Config::$cacheDirectory = $p;
Config::$dbCacheFolder = 'db';
Config::$documentCacheFolder = 'documents';
Config::$documentTextCacheFolder = 'documentTexts';
Config::$dbFilename = 'cr.sq3';
Config::$installCompleteFile = 'installcomplete.txt';
