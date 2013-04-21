<?php

/**
 * RESTful document interface
 */

require '../app/bootstrap.php';
require '../app/Document.php';

$class = new Document();
$class->handle();
