<?php

require_once '../library/Rest.php';

class CrossReference extends Rest {
	protected $table = 'cross_reference';

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
			$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE source_file_id=:file_id');
			if ($stmt->execute(array('file_id'=>(int)$_GET['file_id']))) {
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$err = $stmt->errorInfo();
				$this->error($err[2]);
			}
		} elseif (isset($_GET['pattern_id'])) {
//			$stmt = $this->db->prepare('SELECT * FROM '.$this->table);
//			$stmt->execute();
//			return $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE match_pattern_id=:pattern_id');
			if ($stmt->execute(array('pattern_id'=>(int)$_GET['pattern_id']))) {
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
	 * Given a document_id and a pattern_id, this will search the document for the specified pattern
	 */
	function post(){
		if (!isset($_GET['file_id'])) $this->error('Required parameter "file_id" is missing.');
		if (!isset($_GET['pattern_id'])) $this->error('Required parameter "pattern_id" is missing.');
		if ($_GET['file_id'] != (string)(int)$_GET['file_id']) $this->error('file_id must be an integer');
		if ($_GET['pattern_id'] != (string)(int)$_GET['pattern_id']) $this->error('pattern_id must be an integer');

		$documentId = (int)$_GET['file_id'];
		$patternId = (int)$_GET['pattern_id'];

		// Check to see if it already exists
		$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE source_file_id=:fileId AND match_pattern_id=:patternId');
		if (!$stmt->execute(array('fileId'=>$documentId,'patternId'=>$patternId))){
			$err = $stmt->errorInfo();
			$this->error('Error checking existence of cross-reference: '.$err[2]);
		}
		if (false !== ($row = $stmt->fetch(PDO::FETCH_ASSOC)))
			$this->error('Error: cross-references already exist for file "'.$documentId.'" and pattern "'.$patternId.'" ('.print_r($row,true).')',400);

		// Load the pattern
		$pattern = $this->getPattern($patternId);

		$result = $this->matchPattern($pattern,$documentId,$patternId);
		return count($result);
//		return $result;
	}

	protected function matchPattern($pattern,$documentId,$patternId){
		$filename = dirname(__FILE__).'/../documents/text/'.$documentId.'.txt';
		if (!file_exists($filename)) $this->error('Error - could not locate parsed version of document ("'.$filename.'").');

		$handle = fopen($filename,'r');

		$prevLine = '';
		$resultArr = array();
		while (!feof($handle)){
			// Search the given line for the pattern
			$line = fgets($handle);
			if (false === ($result = preg_match_all($pattern,$line,$matches,PREG_OFFSET_CAPTURE)))
				$this->error('Error searching for pattern "'.$pattern.'"');
			elseif ($result > 0) {
				$stmt = $this->db->prepare('INSERT INTO cross_reference (source_file_id, match_pattern_id, matched_text, context) VALUES (:fileId, :patternId, :matchedText, :context)');

				foreach ($matches[0] as $match){
					$text = $match[0];
					$position = $match[1]-1;
					$context = trim($line);
					if (strlen($context) > 100) {
						$l = strlen($text);
						$fair = floor((100-$l)/2); // if context were equal before and after...
						$beginning = min($fair,$position);
//						$post = max($fair,100-$pre-$l);
						$context = substr($context,$beginning,100);
					}
					$resultArr[] = array(
						'text'=>$text,
						'context'=>$context,
					);

					if (!$stmt->execute(array('fileId'=>$documentId,'patternId'=>$patternId,'matchedText'=>$text,'context'=>$context))){
						$err = $stmt->errorInfo();
						$this->error('Error saving reference: '.$err[2]);
					}
				}
			}
		}
		return $resultArr;
	}

	protected function getPattern($patternId){
		$stmt = $this->db->prepare('SELECT * FROM match_pattern WHERE id=:pattern_id');
		if (!$stmt->execute(array('pattern_id'=>$patternId))) {
			$err = $stmt->errorInfo();
			$this->error('Error while seeking pattern with id "'.$patternId.'": '.$err[2]);
		}
//		if ($stmt->rowCount() == 0) $this->error('Could not find pattern with id "'.$patternId.'".',404);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row['pattern'];
	}

	protected $db;

	function __construct(){
		global $db;
		$this->db = $db;
	}
}