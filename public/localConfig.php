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
Config::$publicDirectory = $root.'public';
Config::$cacheDirectory = $root.'cache';
Config::$dbCacheFolder = 'db';
Config::$documentCacheFolder = 'documents';
Config::$documentTextCacheFolder = 'texts';
Config::$dbFilename = 'cr.sq3';
Config::$installCompleteFile = 'installcomplete.txt';
set_include_path(get_include_path().PATH_SEPARATOR.'/home/hpaulsen/ruby/gems');
exec('export GEM_HOME="/home/hpaulsen/ruby/gems"');
exec('export GEM_PATH="$GEM_HOME:/home/hpaulsen/ruby/gems"');
exec('export PATH="$GEM_HOME/bin:$PATH:/home/hpaulsen/ruby/bin"');
