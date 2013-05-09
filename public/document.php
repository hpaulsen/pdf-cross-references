<?php

/**
 * RESTful document interface
 */

require 'bootstrap.php';
require Config::$appDirectory.DIRECTORY_SEPARATOR.'Document.php';

$class = new Document();
$class->handle();
