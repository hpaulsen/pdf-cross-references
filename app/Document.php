<?php

require_once Config::$libraryDirectory.DIRECTORY_SEPARATOR.'Rest.php';

class Document extends Rest
{
	/** @var string Set in constructor */
	protected $table;
	/** @var array Set in constructor */
	protected $allowedExtensions;
	/** @var string Set in constructor */
	protected $documentDirectory;
	/** @var string Set in constructor */
	protected $documentTextDirectory;
	/** @var string Set in constructor */
	protected $cacheDirectory;

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
		$stmt = $this->db->prepare('INSERT INTO '.$this->table.' (id,filename,doc_id,location) VALUES (:id, :filename, :docId, :location)');
		if (!isset($_FILES['user_file']) && $_SERVER['CONTENT_LENGTH'] > 0){
			$maxSize = ini_get('post_max_size');
			$this->error('Uploaded file exceeds limit of '.$maxSize);
		}
		foreach ($_FILES['user_file']['filename'] as $index=>$filename){
			$tmp = explode('.',$filename);
			$extension = end($tmp);
			if (!in_array($extension,$this->allowedExtensions)){
				$this->error('File type "'.$extension.'" not allowed');
			} else {
				// Try to determine the publication code
				$pubName = '';
				if (preg_match('/SP[\s\.]?(\d{3,4}-\d{2,3})/i',$filename,$matches)){
					// NIST Special Publication
					$pubName = 'NIST SP '.$matches[1];
				} elseif (preg_match('/FIPS[^\d]*(\d{2,4}(-\d)?)/i',$filename,$matches)){
					// FIPS publication
					$pubName = 'FIPS '.$matches[1];
				} elseif (preg_match('/IR[^\d]*(\d{4})/i',$filename,$matches)){
					// NIST IR publication
					$pubName = 'NIST IR '.$matches[1];
				}

				$maxId++;
				$newName = $this->documentDirectory.$maxId.'.'.$extension;
				if ($_FILES['user_file']['error'][$index]) {
					$this->error('File upload of "'.$filename.'" failed with error: '.$_FILES['user_file']['error'][0]);
				} else {
					if (!move_uploaded_file($_FILES['user_file']['tmp_name'][$index],$newName))
						$this->error('File upload of "'.$filename.'" failed');
					else {
						if (!$stmt->execute(array('id'=>$maxId,'filename'=>$filename,'docId'=>$pubName,'location'=>$newName))){
							$err = $stmt->errorInfo();
							unlink($newName);
							$this->error('Unable to save file to database. Error: '.$err[2]);
						} else {
							$txtFilename = $this->documentTextDirectory.$maxId.'.txt';
							switch ($extension){
								case 'pdf':
									if (!$this->parsePdf($newName,$this->documentTextDirectory,$maxId))
										$this->error('Unable to extract text of '.$filename.'.');
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

	protected function parsePdf($pdfFilename,$txtDirectory,$filename){
		if ($txtDirectory[strlen($txtDirectory)-1] !== DIRECTORY_SEPARATOR)
			$txtDirectory .= DIRECTORY_SEPARATOR;
		$ruby = <<<EQL
#!/usr/bin/env ruby
# coding: utf-8

# Extract all text from a single PDF
# PDF text is saved as individual files with page number, e.g. filename_1.txt, filename_2.txt, etc (not zero-indexed)
# PDF metadata is sent back to stdout (or whatever the ruby terminology is...)

require 'rubygems'
require 'pdf/reader'

pdfFilename = "$pdfFilename"
txtFilename = "$txtDirectory$filename"
pageCount = 0

PDF::Reader.open(pdfFilename) do |reader|
	puts reader.info.inspect
	reader.pages.each do |page|
		pageCount = pageCount + 1
		txtFile = File.open(txtFilename+"_#{pageCount}.txt","w");
		txtFile.puts page.text
		txtFile.close
	end
end
EQL;
		file_put_contents($this->documentDirectory.'command.rb',$ruby);
		$command = 'ruby '.$this->documentDirectory.'command.rb';
		$descriptorspec = array(
			1 => array("pipe", 'w'),  // stdout is a pipe that the child will write to
			2 => array("file", $this->cacheDirectory."error-output.txt", "a") // stderr is a file to write to
		);
		$process = proc_open($command, $descriptorspec, $pipes);

		if (is_resource($process)) {

			// Store the pdf info in the metadata table

			$result = stream_get_contents($pipes[1]);
			$result = $this->parsePdfInfoString($result);
			fclose($pipes[1]);
			$return_value = proc_close($process);
			$stmt = $this->db->prepare('INSERT INTO `metadata` (`file_id`, `name`, `value`) VALUES (:file_id, :name, :value)');
			foreach ($result as $key=>$value){
				if (!$stmt->execute(array('file_id'=>$filename,'name'=>$key,'value'=>$value))){
					$err = $stmt->errorInfo();
					$this->error('Error saving pdf info: "'.$err[2].'".');
				}
			}

			// search for cross-references

			$i = 1;
			$pattern = '//si';
			while (file_exists($pdfPageFile=$txtDirectory.$filename.'_'.$i.'.txt')){
				if (false !== ($text = file_get_contents($pdfPageFile))){
				}
				$i++;
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
					$i=1;
					while (file_exists($filename=$this->documentTextDirectory.$id.'_'.$i.'.txt')){
						if (!unlink($filename)) $this->error('Failed to delete text cache for page '.$i.'. Stopping.');
						$i++;
					}
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
		$this->allowedExtensions = array('pdf');
		$this->cacheDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR;
		$this->documentDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$documentCacheFolder.DIRECTORY_SEPARATOR;
		$this->documentTextDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$documentTextCacheFolder.DIRECTORY_SEPARATOR;
	}
}

