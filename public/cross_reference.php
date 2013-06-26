<?php

/**
 * RESTful document name interface
 */

require 'bootstrap.php';
require Config::$appDirectory.DIRECTORY_SEPARATOR.'CrossReference.php';

$class = new CrossReference();
$class->handle();
