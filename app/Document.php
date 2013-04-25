<?php

require_once '../library/Rest.php';
//require_once '../library/PDFreader/File/PDFreader.class.php';
//require_once '../library/php-pdf-parser-master/pdf.php';

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
		if (!isset($_FILES['user_file']) && $_SERVER['CONTENT_LENGTH'] > 0){
			$maxSize = ini_get('post_max_size');
			$this->error('Uploaded file exceeds limit of '.$maxSize);
		}
		foreach ($_FILES['user_file']['name'] as $index=>$filename){
			$extension = end(explode('.',$filename));
			if (!in_array($extension,$this->allowedExtensions)){
				$this->error('File type "'.$extension.'" not allowed');
			} else {
				$maxId++;
				$newName = $this->documentDirectory.$maxId.'.'.$extension;
				if ($_FILES['user_file']['error'][$index]) {
					$this->error('File upload of "'.$filename.'" failed with error: '.$_FILES['user_file']['error'][0]);
				} else {
					if (!move_uploaded_file($_FILES['user_file']['tmp_name'][$index],$newName))
						$this->error('File upload of "'.$filename.'" failed');
					else {
						if (!$stmt->execute(array('id'=>$maxId,'name'=>$filename,'location'=>$newName))){
							$err = $stmt->errorInfo();
							unlink($newName);
							$this->error('Unable to save file to database. Error: '.$err[2]);
						} else {
							$txtFilename = $this->documentDirectory.'text/'.$maxId.'.txt';
							switch ($extension){
								case 'pdf':
									if (!$this->parsePdf($newName,$txtFilename,$filename))
										$this->error('Unable to extract text of '.$filename.'.');
									if (!$this->parsePdfMetadata($newName,$this->documentDirectory.'text/'.$maxId.'m.txt',$filename,$maxId))
										$this->error('Unable to extract metadata of '.$filename.'.');
									break;
								case 'txt':
									if (!copy($newName,$txtFilename))
										$this->error('Unable to extract text of '.$filename.'.');
									break;
							}
						}
					}
				}
			}
		}
	}

	protected function parsePdf($pdfFilename,$txtFilename,$uploadFilename){
		$descriptorspec = array(
			1 => array("file", $txtFilename, 'w'),  // stdout is a file that the child will write to
			2 => array("file", $this->documentDirectory."error-output.txt", "a") // stderr is a file to write to
		);

		$ruby = <<<EQL
#!/usr/bin/env ruby
# coding: utf-8

# Extract all text from a single PDF

require 'rubygems'
require 'pdf/reader'

filename = "$pdfFilename"

PDF::Reader.open(filename) do |reader|
  reader.pages.each do |page|
    puts page.text
  end
end
EQL;

		file_put_contents($this->documentDirectory.'command.rb',$ruby);

		$process = proc_open('ruby '.$this->documentDirectory.'command.rb', $descriptorspec, $pipes);

		if (is_resource($process)) {
			$return_value = proc_close($process);
			return $return_value > -1;
		} else {
			$this->error('Unable to allocate required resource');
		}
	}

	protected function parsePdfMetadata($pdfFilename,$txtFilename,$uploadFilename,$fileId){
		$descriptorspec = array(
			1 => array("pipe", 'w'),  // stdout is a pipe that the child will write to
			2 => array("file", $this->documentDirectory."error-output.txt", "a") // stderr is a file to write to
		);

		$ruby = <<<EQL
#!/usr/bin/env ruby
# coding: utf-8

# Extract metadata only

require 'rubygems'
require 'pdf/reader'

filename = "$pdfFilename"

PDF::Reader.open(filename) do |reader|
  puts reader.info.inspect
#  puts reader.metadata.inspect
end
EQL;

		file_put_contents($this->documentDirectory.'command.rb',$ruby);

		$process = proc_open('ruby '.$this->documentDirectory.'command.rb', $descriptorspec, $pipes);

		if (is_resource($process)) {
			$result = stream_get_contents($pipes[1]);
			$result = $this->parsePdfInfoString($result);
			fclose($pipes[1]);
			$return_value = proc_close($process);

			// Store the pdf info in the metadata table
			$stmt = $this->db->prepare('INSERT INTO `metadata` (`file_id`, `name`, `value`) VALUES (:file_id, :name, :value)');
			foreach ($result as $key=>$value){
				if (!$stmt->execute(array('file_id'=>$fileId,'name'=>$key,'value'=>$value))){
					$err = $stmt->errorInfo();
					$this->error('Error saving pdf info: "'.$err[2].'".');
				}
			}

			return $return_value > -1;
		} else {
			$this->error('Unable to allocate required resource');
		}
	}

	protected function parsePdfInfoString($string){
		if (false !== ($numMatches = preg_match_all('/\:(\w*)=>"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/s',$string,$matches))) {
			$result = array();
			for ($i=0; $i<count($matches[1]); $i++){
				$result[$matches[1][$i]] = $matches[2][$i];
			}
			return $result;
		} else {
			$this->error('Error in translating metadata');
		}
	}

	function delete(){
		if (!isset($_GET['id']))
			$this->error('Required GET parameter "id" is missing');
		else {
			$id = (int)$_GET['id'];
			$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE id=:id');
			if ($stmt->execute(array('id'=>$id))){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				$location = $row['location'];
				$stmt = $this->db->prepare('DELETE FROM '.$this->table.' WHERE id=:id');
				if ($stmt->execute(array('id'=>$id))){
					if (!unlink($location)) $this->error('Failed to delete file');
					if (!unlink($this->documentDirectory.'text/'.$id.'.txt')) $this->error('Failed to delete text cache');
					return 'Deleted item '.$id;
				} else {
					$err = $stmt->errorInfo();
					$this->error('Delete failed with error: '.$err[2]);
				}
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

