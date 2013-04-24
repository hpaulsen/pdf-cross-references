<?php

require_once '../library/Rest.php';

/**
 * Class DocumentDetail
 *
 * Only reads the document info. The document info is set when the document is uploaded to Document.php
 */
class DocumentDetail extends Rest {
	protected $table = 'metadata';

	function get(){
		if (isset($_GET['id'])){
			$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE id=:id');
			if ($stmt->execute(array('id'=>(int)$_GET['id']))){
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$err = $stmt->errorInfo();
				$this->error($err[2]);
			}
		} elseif (isset($_GET['file_id'])) {
			$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE file_id=:file_id');
			if ($stmt->execute(array('file_id'=>(int)$_GET['file_id']))) {
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$err = $stmt->errorInfo();
				$this->error($err[2]);
			}
		} else {
			$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
			$count = isset($_GET['count']) ? (int)$_GET['count'] : 15;
			$stmt = $this->db->query('SELECT * FROM '.$this->table.' LIMIT '.$start.', '.$count);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

	protected $db;

	function __construct(){
		global $db;
		$this->db = $db;
	}
}