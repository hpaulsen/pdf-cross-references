<?php

/**
 * RESTful document interface
 */

require '../app/bootstrap.php';
require '../app/DocumentDetail.php';

$class = new DocumentDetail();
$class->handle();
