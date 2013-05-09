<?php

/**
 * Class Config
 *
 * Stores configuration information for a project. The configuration should be stored to this object in a separate file.
 */

class Config {
	/** @var string Path to the directory containing the application code */
	public static $appDirectory;
	/** @var string Path to the database configuration directory */
	public static $dbDirectory;
	/** @var string Path to the 3rd-party library directory */
	public static $libraryDirectory;
	/** @var string Path to a writable cache directory */
	public static $cacheDirectory;
	/** @var string Path to the public directory */
	public static $publicDirectory;
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
		if (!isset(self::$appDirectory) || strlen(self::$appDirectory) == 0)
			$result[] = '$appDirectory: Application directory is not defined';
		if (!is_dir(self::$appDirectory))
			$result[] = '$appDirectory: Application directory not found';

		if (!isset(self::$dbDirectory) || strlen(self::$dbDirectory) == 0)
			$result[] = '$dbDirectory: Database directory is not defined';
		if (!is_dir(self::$dbDirectory))
			$result[] = '$dbDirectory: Database directory not found';

		if (!isset(self::$libraryDirectory) || strlen(self::$libraryDirectory) == 0)
			$result[] = '$libraryDirectory: Library directory is not defined';
		if (!is_dir(self::$libraryDirectory))
			$result[] = '$libraryDirectory: Library directory not found';

		if (!isset(self::$cacheDirectory) || strlen(self::$cacheDirectory) == 0)
			$result[] = '$cacheDirectory: Cache directory is not defined';
		if (!is_dir(self::$cacheDirectory))
			$result[] = '$cacheDirectory: Cache directory not found';

		if (!isset(self::$publicDirectory) || strlen(self::$publicDirectory) == 0)
			$result[] = '$publicDirectory: Public directory is not defined';
		if (!is_dir(self::$publicDirectory))
			$result[] = '$publicDirectory: Public directory not found';

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
