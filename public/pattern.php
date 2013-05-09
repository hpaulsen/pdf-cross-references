<?php

/**
 * RESTful pattern interface
 */

require 'bootstrap.php';
require Config::$appDirectory.DIRECTORY_SEPARATOR.'Pattern.php';

$class = new Pattern();
$class->handle();
