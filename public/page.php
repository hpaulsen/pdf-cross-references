<?php

/**
 * RESTful document name interface
 */

require 'bootstrap.php';
require Config::$appDirectory.DIRECTORY_SEPARATOR.'Page.php';

$class = new Page();
$class->handle();
