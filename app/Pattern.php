<?php

require_once '../library/Rest.php';

class Pattern extends Rest
{
	protected $table = 'match_pattern';

	function get(){
		if (isset($_GET['id'])){
			$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE id=:id');
			if ($stmt->execute(array('id'=>(int)$_GET['id']))){
				return $stmt->fetch(PDO::FETCH_ASSOC);
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

	function post(){
		// Load the data
		$data = json_decode(file_get_contents('php://input'));
		if (!isset($data->pattern))
			$this->error('Required member item "pattern" is missing. '.print_r($_SERVER,true),400);

		if (isset($data->id)){
			// modify existing
			$this->error('Modifying a pattern is not permitted. Create a new pattern and delete the old if needed.');
//			$stmt = $this->db->prepare('UPDATE '.$this->table.' SET pattern=:pattern WHERE id=:id');
//			if ($stmt->execute(array('pattern'=>$data->pattern,'id'=>(int)$data->id))){
//				return $data;
//			} else {
//				$err = $stmt->errorInfo();
//				$this->error('Save failed with error: '.$err[2]);
//			}
		} else {
			// add new
			$stmt = $this->db->prepare('INSERT INTO '.$this->table.' (pattern) VALUES (:pattern)');
			if ($stmt->execute(array('pattern'=>$data->pattern))){
				$data->id = $this->db->lastInsertId();
				return $data;
			} else {
				$err = $stmt->errorInfo();
				$this->error('Save failed with error: '.$err[2]);
			}
		}
	}

	function delete(){
		if (!isset($_GET['id']))
			$this->error('Required GET parameter "id" is missing');
		else {
			$id = (int)$_GET['id'];
			$stmt = $this->db->prepare('DELETE FROM '.$this->table.' WHERE id=:id');
			if ($stmt->execute(array('id'=>$id))){
				return 'Deleted item '.$id;
			} else {
				$err = $stmt->errorInfo();
				$this->error('Delete failed with error: '.$err[2]);
			}
		}
	}

	protected $db;

	function __construct(){
		global $db;
		$this->db = $db;
	}
}

