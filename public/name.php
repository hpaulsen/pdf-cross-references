<?php

/**
 * RESTful document name interface
 */

require 'bootstrap.php';
require Config::$appDirectory.DIRECTORY_SEPARATOR.'Name.php';

$class = new Name();
$class->handle();
