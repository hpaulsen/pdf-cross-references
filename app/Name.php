<?php

require_once Config::$libraryDirectory.DIRECTORY_SEPARATOR.'Rest.php';

class Name extends Rest {
	protected $table = 'filename';

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

	function post(){
		// Load the data
		$data = json_decode(file_get_contents('php://input'));
		if (!isset($data->file_id))
			$this->error('Required member item "file_id" is missing. '.print_r($_SERVER,true),400);
		if (!isset($data->name))
			$this->error('Required member item "name" is missing. '.print_r($_SERVER,true),400);

		if (isset($data->id)){
			// modify existing
			$this->error('Modifying a name is not permitted. Create a new name and delete the old if needed.',400);
//			$stmt = $this->db->prepare('UPDATE '.$this->table.' SET pattern=:pattern WHERE id=:id');
//			if ($stmt->execute(array('pattern'=>$data->pattern,'id'=>(int)$data->id))){
//				return $data;
//			} else {
//				$err = $stmt->errorInfo();
//				$this->error('Save failed with error: '.$err[2]);
//			}
		} else {
			// add new
			$stmt = $this->db->prepare('INSERT INTO '.$this->table.' (file_id, name) VALUES (:file_id, :name)');
			if ($stmt->execute(array('file_id'=>$data->file_id, 'name'=>$data->name))){
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