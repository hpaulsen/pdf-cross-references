<?php

require_once '../library/Rest.php';

class Document extends Rest
{
	/** @var string Set in constructor */
	protected $table;
	/** @var array Set in constructor */
	protected $allowedExtensions;
	/** @var string Set in constructor */
	protected $documentDirectory;

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
		// Expect a single or multiple files
		$files = array();
		$maxId = -1;
		$stmt = $this->db->prepare('SELECT MAX(id) AS maxId FROM '.$this->table);
		if ($stmt->execute()){
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (is_array($row) && isset($row['maxId']))
				$maxId = $row['maxId'];
		}
		$stmt = $this->db->prepare('INSERT INTO '.$this->table.' (id,name,location) VALUES (:id, :name, :location)');
		foreach ($_FILES['user_file']['name'] as $index=>$filename){
			$extension = end(explode('.',$filename));
			if (!in_array($extension,$this->allowedExtensions)){
				$this->error('File type "'.$extension.'" not allowed');
			} else {
				$newName = $this->documentDirectory.$maxId.'.'.$extension;
				if ($_FILES['user_file']['error'][$index]) {
					$this->error('File upload of "'.$filename.'" failed with error: '.$_FILES['user_file']['error'][0]);
				} else {
					if (!move_uploaded_file($_FILES['user_file']['tmp_name'][$index],$newName))
						$this->error('File upload of "'.$filename.'" failed');
					else {
						$maxId++;
						if (!$stmt->execute(array('id'=>$maxId,'name'=>$filename,'location'=>$newName))){
							$err = $stmt->errorInfo();
							$this->error('Unable to save file to database. Error: '.$err[2]);
						}
					}
				}
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

	/**
	 * @var PDO
	 */
	protected $db;

	function __construct(){
		global $db;
		$this->db = $db;
		$this->table = 'file';
		$this->allowedExtensions = array('pdf','txt');
		$this->documentDirectory = dirname(__FILE__).'/../documents/';
	}
}

