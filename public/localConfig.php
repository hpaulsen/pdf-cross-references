<?php
/**
 * The actual configuration variables...
 */

require 'Config.php';

$root = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
$root = preg_replace('/\w+\/\.\.\//', '', $root);

Config::$appDirectory = $root.'app';
Config::$dbDirectory = $root.'db';
Config::$libraryDirectory = $root.'library';
Config::$cacheDirectory = $root.'cache';
Config::$dbCacheFolder = 'db';
Config::$documentCacheFolder = 'documents';
Config::$documentTextCacheFolder = 'documentTexts';
Config::$dbFilename = 'cr.sq3';
Config::$installCompleteFile = 'installcomplete.txt';
