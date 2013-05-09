<?php

require 'localConfig.php';

// Store test results
$testResults = array();
// Each $testResults element should be an array with the following keys:
const TITLE = 'title';
const RESULT = 'result'; // 'unknown', true, or false
const DETAIL = 'detail'; // an array of detailed messages
// The following should be used to indicate that the status of a test cannot be determined because of a dependency
const RESULT_DEPENDENCY_FAILED = 'unknown';

// These are the tests
const TEST_CONFIG_COMPLETE = 'configComplete';
$testResults[TEST_CONFIG_COMPLETE] = array(
	TITLE => 'Configuration complete',
	RESULT => RESULT_DEPENDENCY_FAILED,
	DETAIL => array(),
);
const TEST_EXEC_ALLOWED = 'execAllowed';
$testResults[TEST_EXEC_ALLOWED] = array(
	TITLE => 'PHP has permission to exec() function',
	RESULT => RESULT_DEPENDENCY_FAILED,
	DETAIL => array(),
);
const TEST_RUBY_INSTALLED = 'rubyInstalled';
$testResults[TEST_RUBY_INSTALLED] = array(
	TITLE => 'Ruby is installed',
	RESULT => RESULT_DEPENDENCY_FAILED,
	DETAIL => array(),
);
const TEST_RUBY_PDF_READER_INSTALLED = 'rubyPdfReaderInstalled';
$testResults[TEST_RUBY_PDF_READER_INSTALLED] = array(
	TITLE => 'The Ruby pdf-reader is installed',
	RESULT => RESULT_DEPENDENCY_FAILED,
	DETAIL => array(),
);
const TEST_CACHE_READY = 'cacheReady';
$testResults[TEST_CACHE_READY] = array(
	TITLE => 'The cache is ready',
	RESULT => RESULT_DEPENDENCY_FAILED,
	DETAIL => array(),
);
const TEST_DB_READY = 'dbReady';
$testResults[TEST_DB_READY] = array(
	TITLE => 'The database is initialized',
	RESULT => RESULT_DEPENDENCY_FAILED,
	DETAIL => array(),
);

function addResult($section,$result=null,$detailIfTrue='',$detailIfFalse=''){
	global $testResults;
	if ($result !== null)
		$testResults[$section][RESULT] = $result;
	if  ($result === true) {
		if (is_array($detailIfTrue))
			$testResults[$section][DETAIL] = $detailIfTrue;
		elseif ($detailIfTrue != '')
			$testResults[$section][DETAIL][] = $detailIfTrue;
	} elseif ($result === false) {
		if (is_array($detailIfFalse))
			$testResults[$section][DETAIL] = $detailIfFalse;
		elseif ($detailIfFalse != '')
			$testResults[$section][DETAIL][] = $detailIfFalse;
	}
}

function getResult($section) {
	global $testResults;
	return $testResults[$section][RESULT];
}

$priorInstallExists = file_exists(Config::$installCompleteFile);

// Test config
addResult(
	TEST_CONFIG_COMPLETE,
	Config::complete(),
	'',
	Config::errors()
);

// Test exec privileges
if (getResult(TEST_CONFIG_COMPLETE) === true) {
	$disabledCommands = explode(', ',ini_get('disable_functions'));
	addResult(
		TEST_EXEC_ALLOWED,
		!in_array('exec',$disabledCommands),
		'',
		'Check php ini to make sure that \'exec\' is NOT listed in the section \'disabled_commands\'.'
	);
}

// Test ruby
if (getResult(TEST_CONFIG_COMPLETE) === true && getResult(TEST_EXEC_ALLOWED) === true) {
	$rubyVersion = exec('ruby -v');
	addResult(
		TEST_RUBY_INSTALLED,
		preg_match('/^ruby\s\d+(\.\d+)/',$rubyVersion) > 0,
		'',
		'Install ruby.'
	);
}

// Test ruby pdf-reader gem
if (getResult(TEST_CONFIG_COMPLETE) === true && getResult(TEST_EXEC_ALLOWED) === true && getResult(TEST_RUBY_INSTALLED) === true) {
	exec('gem specification pdf-reader',$rubyPdfReaderVersion);
	$rubyPdfReaderVersion = implode("\n",$rubyPdfReaderVersion);
	if (preg_match('/^name\:\s+pdf\-reader/m',$rubyPdfReaderVersion)){
		$rubyPdfReaderInstalled = true;
		if (preg_match('/^\s+version\:\s+(\d+(\.\d+)+)/m',$rubyPdfReaderVersion,$matches)){
			if (count($matches)>=1)
				$rubyPdfReaderVersion = $matches[1];
		} else {
			$rubyPdfReaderVersion = 'unknown';
		}
	} else {
		$rubyPdfReaderInstalled = false;
	}
	addResult(
		TEST_RUBY_PDF_READER_INSTALLED,
		$rubyPdfReaderInstalled,
		'Version: '.$rubyPdfReaderVersion,
		'Install ruby pdf-reader (run "gem install pdf-reader" from the command line)'
	);
}

// Test and configure the cache directory
function testCacheSubDir($dir){
	if (file_exists($dir)) {
		if (!is_writable($dir)) return 'Cache sub-directory "'.$dir.'" already exists but is not writable. Change the permissions of the folder to make it writable by the web server.';
	} else {
		if (!mkdir($dir)) return 'Unable to create folder "'.$dir.'". Problem unknown - this shouldn\'t happen!';
		if (!is_writable($dir)) return 'Unable to write to newly created folder "'.$dir.'". Problem unknown - this shouldn\'t happen!';
	}
	return true;
}
if (getResult(TEST_CONFIG_COMPLETE) === true) {
	$cacheDir = Config::$cacheDirectory;
	$cacheErrors = array();
	if (file_exists($cacheDir)) {
		// test whether the cache directory is writable
		if (is_writable($cacheDir)) {
			$result = testCacheSubDir($cacheDir.'/db');
			if (!$result) $cacheErrors[] = $result;

			$result = testCacheSubDir($cacheDir.'/documents');
			if (!$result) $cacheErrors[] = $result;

			$result = testCacheSubDir($cacheDir.'/texts');
			if (!$result) $cacheErrors[] = $result;
		} else {
			$cacheErrors[] = 'Cache directory is not writable. Change the permissions of "'.$cacheDir.'" to make sure it is writable by the web server.';
		}
	} else {
		$cacheErrors[] = 'Cache directory not found. Create a cache directory at "'.$cacheDir.'" and make sure it is writable by the web server.';
	}
	addResult(
		TEST_CACHE_READY,
		count($cacheErrors) == 0,
		'',
		$cacheErrors
	);
}

// Create the database
if (getResult(TEST_CONFIG_COMPLETE) === true && getResult(TEST_CACHE_READY) === true) {
	$dbReady = false;
	$dbError = '';
	$schemaFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'db'.DIRECTORY_SEPARATOR.'schema.sql';
	if (file_exists($schemaFile)) {
		$schema = file_get_contents($schemaFile);
		if ($schema === false) {
			$dbError = 'Unable to load "'.$schemaFile.'". This shouldn\'t happen!';
		} else {
			try {
				$db = new PDO('sqlite:'.Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$dbCacheFolder.DIRECTORY_SEPARATOR.Config::$dbFilename);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if ($db->exec($schema) === false) {
					$err = $db->errorInfo();
					$dbError = $err[2];
				} else {
					$dbReady = true;
				}
			} catch(PDOException $e) {
				$dbError = $e->getMessage();
			}
		}
	} else {
		$dbError = 'Missing file: "'.$schemaFile.'". Please re-download.';
	}
	addResult(
		TEST_DB_READY,
		$dbReady,
		'',
		$dbError
	);
}

// Check to see if we are done
$installationComplete = true;
foreach ($testResults as $testResult) {
	if ($testResult[RESULT] !== true) {
		$installationComplete = false;
		break;
	}
}
if ($installationComplete) {
	if (!touch(Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$installCompleteFile)) {
		$installationComplete = false;
		$installationError = 'Unable to create installation complete file! (This shouldn\'t happen.)';
	}
}

?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Install</title>
	<!--[if lt IE 9]><script src="lib/html5shiv-printshiv.js" media="all"></script><![endif]-->
<style type="text/css">
/* Copied from jquery-ui */
.ui-helper-clearfix:before,
.ui-helper-clearfix:after {
	content: "";
	display: table;
	border-collapse: collapse;
}
.ui-helper-clearfix:after {
	clear: both;
}
.ui-helper-clearfix {
	min-height: 0; /* support: IE7 */
}
/* End copied from jquery-ui */
div.item {
	border: 1px solid #0d3349;
	padding: 10px;
	vertical-align: middle;
	text-align: center;
	width: 450px;
}
div.item:nth-child(odd){
	background-color: #AAAAFF;
}
div.item:nth-child(even){
	background-color: #DDDDFF;
}
div.question {
	float: left;
	width: 300px;
	font-size: 1.1em;
	text-align: left;
}
div.answer {
	float: left;
	width: 150px;
	font-size: 1.3em;
	font-weight: bold;
}
div.positive {
	color: green;
}
div.negative {
	color: red;
}
div.unknown {
	color: yellow;
}
div.description {
	text-align: left;
	font-style: italic;
	padding-top: 10px;
}
</style>
</head>
<body>
<h1>Installation Check</h1>

<?php if ($priorInstallExists) : ?>
	<p>Initialization already completed - double checking</p>
<?php endif; ?>

<div>
	<p>Please resolve any items marked with a red &#x2717; below:</p>
</div>

<?php foreach ($testResults as $testResult) : ?>
	<div class="item ui-helper-clearfix">
		<div class="question"><?php echo $testResult[TITLE]; ?></div>
		<?php if ($testResult[RESULT] === RESULT_DEPENDENCY_FAILED) : ?>
			<div class="answer unknown">?</div>
			<div class="description">This test is dependent on other tests which have failed. Correct the above errors and then try again.</div>
		<?php elseif ($testResult[RESULT]) : ?>
			<div class="answer positive">&#x2713;</div>
		<?php else : ?>
			<div class="answer negative">&#x2717;</div>
		<?php endif; ?>
		<div class="description">
			<?php foreach ($testResult[DETAIL] as $detail) : ?>
				<div><?php echo $detail; ?></div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endforeach; ?>

<div class="item ui-helper-clearfix">
	<div class="question">Installation complete</div>
<?php if ($installationComplete) : ?>
		<div class="answer positive">&#x2713;</div>
		<div class="description"><a href="index.php">Continue</a></div>
<?php elseif (isset($installationError) && strlen($installationError) > 0) : ?>
		<div class="answer negative">&#x2717;</div>
<?php else : ?>
		<div class="answer negative">&#x2717;</div>
		<div class="description">Fix any errors above and then retry running this script.</div>
<?php endif; ?>
</div>
</body>
</html>