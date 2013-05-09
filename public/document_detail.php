<?php

/**
 * RESTful document interface
 */

require 'bootstrap.php';
require Config::$appDirectory.DIRECTORY_SEPARATOR.'DocumentDetail.php';

$class = new DocumentDetail();
$class->handle();
