<?php
/**
 * Serves an uploaded document to the user
 */

require '../library/ResponseCode.php';
require '../db/db.php';
global $db;

if (!isset($_GET['id'])) {
	header(ResponseCode::CLIENT_ERROR_BAD_REQUEST);
	echo 'Required parameter "id" not found.';
	exit;
}

$documentId = (int)$_GET['id'];

if ($documentId != $_GET['id']) {
	header(ResponseCode::CLIENT_ERROR_BAD_REQUEST);
	echo 'Parameter "id" must be an integer.';
	exit;
}

$stmt = $db->prepare('SELECT * FROM `file` WHERE id=:id');
if ($stmt->execute(array('id'=>$documentId))){
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($row === false) {
		header(ResponseCode::CLIENT_ERROR_NOT_FOUND);
		echo 'Document with id "'.$documentId.'" could not be found.';
		exit;
	} else {
		if (!file_exists($row['location'])) {
			header(ResponseCode::SERVER_ERROR_INTERNAL_SERVER_ERROR);
			echo 'Local file inconsistency: file not found';
			exit;
		}
		header('Content-Description: File Transfer');
		$type = end(explode('.',$row['name']));
		switch ($type) {
			case 'pdf':
				header('Content-type: application/pdf');
				break;
			case 'txt':
				header('Content-type: text/plain');
				break;
			case 'html':
				header('Content-type: text/html');
				break;
			default:
				header(ResponseCode::SERVER_ERROR_INTERNAL_SERVER_ERROR);
				echo 'Unknown file type "'.$type.'"';
				exit;
		}
		header('Content-Length: '.filesize($row['location']));
		header('Content-Disposition: inline; filename="'.$row['name'].'"');
		header('Content-Transfer-Encoding: binary');
		ob_clean();
		flush();
//		header('Content-Disposition: attachment; filename="'.$row['name'].'"');
		readfile($row['location']);
		exit;
	}
} else {
	header(ResponseCode::SERVER_ERROR_INTERNAL_SERVER_ERROR);
	$err = $stmt->errorInfo();
	echo 'Database error: '.$err[2];
	exit;
}
