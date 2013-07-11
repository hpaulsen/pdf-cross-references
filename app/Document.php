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
	/** @var int Set in constructor */
	protected $contextLength;

	/**
	 * There are several ways to call this using the following parameters:
	 *
	 * int id - the internal id of a document
	 * int page - the 1-based page count for the document
	 * bool metadata - whether to return the metadata for the document
	 * int start - where to start while listing documents
	 * int count - the number of documents to return
	 *
	 * get($start,$count) to retrieve a list of documents
	 *
	 * get($id) to retrieve info for a single document
	 *
	 * get($id,$metadata) to retrieve metadata for a single document
	 *
	 * @return array|mixed
	 */
	function get(){
		if (isset($_GET['id'])){
			$id = $_GET['id'];
			if (isset($_GET['metadata']) && ($_GET['metadata'] == true || $_GET['metadata'] == 'true')){
				return $this->getMetadata($id);
			} else {
				return $this->getDocument($id);
			}
		} else {
			$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
			$count = isset($_GET['count']) ? (int)$_GET['count'] : 30;
			return $this->getDocumentList($start,$count);
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
		$stmt = $this->db->prepare('INSERT INTO '.$this->table.' (id,filename,doc_id,doc_type,location,num_pages) VALUES (:id, :filename, :docId, :docType, :location, :numPages)');
		if (!isset($_FILES['user_file']) && $_SERVER['CONTENT_LENGTH'] > 0){
			$maxSize = ini_get('post_max_size');
			$this->error('Uploaded file exceeds limit of '.$maxSize);
		}
		foreach ($_FILES['user_file']['name'] as $index=>$filename){
			$tmp = explode('.',$filename);
			$extension = end($tmp);
			if (!in_array($extension,$this->allowedExtensions)){
				$this->error('File type "'.$extension.'" not allowed');
			} else {
				// Try to determine the publication code
				$pubName = '';
				$docType = 'other/unknown';
				if (preg_match('/SP[\s\.]?(\d{3,4}-\d{2,3})/i',$filename,$matches)){
					// NIST Special Publication
					$docType = 'NIST SP';
					$pubName = $docType.' '.$matches[1];
				} elseif (preg_match('/FIPS[^\d]*(\d{2,4}(-\d)?)/i',$filename,$matches)){
					// FIPS publication
					$docType = 'FIPS';
					$pubName = $docType.' '.$matches[1];
				} elseif (preg_match('/IR[^\d]*(\d{4})/i',$filename,$matches)){
					// NIST IR publication
					$docType = 'NIST IR';
					$pubName = $docType.' '.$matches[1];
				}

				$maxId++;
				$newName = $this->documentDirectory.$maxId.'.'.$extension;
				if ($_FILES['user_file']['error'][$index]) {
					$this->error('File upload of "'.$filename.'" failed with error: '.$_FILES['user_file']['error'][0]);
				} else {
					if (!move_uploaded_file($_FILES['user_file']['tmp_name'][$index],$newName))
						$this->error('File upload of "'.$filename.'" failed');
					else {
						$numPages = 1;
						switch ($extension){
							case 'pdf':
								if (!$this->parsePdf($newName,$maxId,$numPages))
									$this->error('Unable to extract text of '.$filename.'.');
								break;
							case 'txt':
								$txtFilename = $this->documentTextDirectory.$maxId.'.txt';
								if (!copy($newName,$txtFilename))
									$this->error('Unable to extract text of '.$filename.'.');
								break;
						}
						if (!$stmt->execute(array('id'=>$maxId,'filename'=>$filename,'docId'=>$pubName,'docType'=>$docType,'location'=>$newName,'numPages'=>$numPages))){
							$err = $stmt->errorInfo();
							$this->deleteRelatedFiles($maxId,$newName);
							$this->error('Unable to save file to database. Error: '.$err[2]);
						}
					}
				}
			}
		}
	}

	protected function getDocumentList($start,$count){
		$q = <<<EOQ
SELECT
	id,
	filename,
	doc_id,
	num_pages
	,(SELECT COUNT(*) FROM cross_reference c JOIN page p ON p.file_id=o.id AND p.page=c.page_number WHERE source_file_id=o.id AND include=1) AS num_references
FROM $this->table AS o
LIMIT $start,$count
EOQ;
		$stmt = $this->db->query($q);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	protected function getDocument($docId){
		$q = <<<EOQ
SELECT
	id,
	filename,
	doc_id,
	num_pages
	,(SELECT COUNT(*) FROM cross_reference c JOIN page p ON p.file_id=o.id AND p.page=c.page_number WHERE source_file_id=o.id AND include=1) AS num_references
FROM $this->table AS o
WHERE id=:id
EOQ;
		$stmt = $this->db->prepare($q);
		if ($stmt->execute(array('id'=>(int)$_GET['id']))){
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} else {
			$err = $stmt->errorInfo();
			$this->error($err[2]);
		}
	}

	protected function getMetadata($docId){
		$stmt = $this->db->prepare('SELECT * FROM '.$this->metadataTable.' WHERE file_id=:id');
		if ($stmt->execute(array('id'=>$docId))){
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$err = $stmt->errorInfo();
			$this->error($err[2]);
		}
	}


	protected function parsePdf($pdfFilename,$fileId,&$numPages=null){
		$metadata = $this->savePdfPages($pdfFilename,$fileId);

		if ($metadata !== false) {
			$this->saveMetadata($metadata,$fileId);

			// search for cross-references

			$pageNum = 1;
			$text = '';
			$prevPageText = '';
			$pageStatement = $this->db->prepare('INSERT INTO `page` (`file_id`,`page`,`include`,`is_glossary`) VALUES (:fileId,:page,:include,:isGlossary)');
			$crossRefStatement = $this->db->prepare('INSERT INTO `cross_reference` (`source_file_id`,`referenced_file_id`,`referenced_file_type`,`page_number`,`matched_text`,`matched_offset`,`context`,`reference_type`) VALUES (:sourceFileId,:referencedFileId,:referencedFileType,:pageNum,:matchedText,:matchedOffset,:context,:type)');
			$docHasGlossary = false;
			$refNumPattern = '/(\d{2,5}([\-\s\.\:]+\d{1,3})?[A-Za-z]?)/us';
			$resultArr = array();
			while (file_exists($pdfPageFile=$this->documentTextDirectory.$fileId.'_'.$pageNum.'.txt')){
				if (is_string($text) && mb_strlen($text,'UTF-8')>0)
					$prevPageText = $text;
				if (false !== ($text = file_get_contents($pdfPageFile))){
					$text = iconv('UTF-8','UTF-8//IGNORE',$text);

					$text = $this->removeHeadFoot($text,$headerLength);

					// search for "glossary"
					$isInGlossary = ($docHasGlossary || $this->pageReferencesGlossary($text)) ? 2 : 0; // 0 for false, 1 for true, 2 for 'maybe, maybe not - the document has a glossary somewhere!'

					if (!$pageStatement->execute(array('fileId'=>$fileId,'page'=>$pageNum,'include'=>true,'isGlossary'=>$isInGlossary))){
						$err = $pageStatement->errorInfo();
						$this->error('Error saving page: '.$err[2]);
					}

					// Search for references
					if (false === ($result = preg_match_all($refNumPattern,$text,$matches,PREG_OFFSET_CAPTURE)))
						$this->error('Error searching for pattern "'.$refNumPattern.'"');
					elseif ($result > 0) {
						foreach ($matches[1] as $match){
							$matchedText = $match[0];
							$position = $match[1]-1;
							$context = $text;
							if (mb_strlen($context,'UTF-8') > $this->contextLength) {
								$fair = $position-floor($this->contextLength/2); // if context were equal before and after...
								if ($fair < 0){
									$pre = mb_substr($prevPageText,$fair,$this->contextLength,'UTF-8');
//									$position += mb_strlen($pre,'UTF-8');
									$context = $pre.mb_substr($context,0,$this->contextLength+$fair,'UTF-8');
								} else {
									$context = mb_substr($context,$fair,$this->contextLength,'UTF-8');
								}
							}
							$resultArr[] = array(
								'text'=>$text,
								'context'=>$context,
							);

							$refDocId = preg_replace('/\s/','',$matchedText);
							$refDocIdLength = mb_strlen($refDocId,'UTF-8');
							$refDocType = 'other/unknown';

							// Search for revision number
							if (preg_match('/'.$matchedText.'[^\w\w]*[Rr][\w\s,-\.]*([\d]+)/us',$context,$matches2)){
								$refDocId .= ' rev. '.$matches2[1];
							}

							// Search for NIST SP
							if ($refDocIdLength>=5 && preg_match('/\WS(pec(ial)?)?[^\w\d]*P(ub(l(ication(s)?)?)?)?[^\w\d]*([A-C\d\-\,\s])*'.$matchedText.'\W/us',$context)){
								$refDocType = 'NIST SP';
								$refDocId = $refDocType.' '.$refDocId;
							// Search for NIST IR
							} elseif (preg_match('/IR\s*'.$matchedText.'/us',$context)){
								$refDocType = 'NIST IR';
								$refDocId = $refDocType.' '.$refDocId;
							// Search for FIPS
							} elseif (preg_match('/FIPS\s*(P(ub(lication(s)?)?)?)?[\s\.\-\d\,]*'.$matchedText.'/us',$context)){
								$refDocType = 'FIPS';
								$refDocId = $refDocType.' '.$refDocId;
							// Search for IEEE
							} elseif (preg_match('/IEEE\s*'.$matchedText.'/us',$context)){
								$refDocType = 'IEEE';
								$refDocId = $refDocType.' '.$refDocId;
							// Search for Public Law
							} elseif (preg_match('/Public Law '.$matchedText.'/us',$context)){
								$refDocType = 'Public Law';
								$refDocId = $refDocType.' '.$refDocId;
							// Search for OMB
							} elseif (preg_match_all('/OMB\)?[\w\s]{1,12}([\d\w\s\-]+\,)*([\d\w]+\-)?'.$matchedText.'/us',$context,$matches2)){
								$refDocType = 'OMB';
								if (mb_strlen($matches2[2][0],'UTF-8')>0)
									$refDocId = $refDocType.' '.$matches2[2][0].$refDocId;
								else
									$refDocId = $refDocType.' '.$refDocId;
								// Search for GAO
							} elseif (preg_match('/GAO.{1,12}\-?'.$matchedText.'/us',$context)){
								$refDocType = 'GAO';
								$refDocId = $refDocType.' '.$refDocId;
								// Search for NSTISSI
							} elseif (preg_match('/NSTISSI.{1,12}'.$matchedText.'\s*([A-Za-z\d]+\-[A-Za-z\d]+)/us',$context,$matches2)){
								$refDocType = 'NSTISSI';
								$refDocId = $refDocType.' '.$refDocId.' '.$matches2[1];
								// Search for ISO/IEC
							} elseif (preg_match('/ISO\/IEC\s'.$matchedText.'/us',$context)){
								$refDocType = 'ISO/IEC';
								$refDocId = $refDocType.' '.$refDocId;
								// Search for ISO
							} elseif (preg_match('/ISO\s'.$matchedText.'/us',$context)){
								$refDocType = 'ISO';
								$refDocId = $refDocType.' '.$refDocId;
								// Searh for SAS
							} elseif (preg_match('/SAS\s'.$matchedText.'/us',$context)){
								$refDocType = 'SAS';
								$refDocId = $refDocType.' '.$refDocId;
							} else continue; // don't save it if we don't know what it was!

							if (!$crossRefStatement->execute(array('sourceFileId'=>$fileId,'referencedFileId'=>$refDocId,'referencedFileType'=>$refDocType,'pageNum'=>$pageNum,'matchedText'=>$matchedText,'matchedOffset'=>$position,'context'=>$context))){
								$err = $crossRefStatement->errorInfo();
								$this->error('Error saving reference: '.$err[2]);
							}
						}
					}
				}
				$pageNum++;
			}
			$numPages = $pageNum-1;

			return $resultArr;
		} else {
			$this->error('Unable to allocate required resource');
		}
	}

	/**
	 * Search for the word "glossary" on the page
	 *
	 * @param $text
	 *
	 * @return bool
	 */
	protected function pageReferencesGlossary($text){
		$glossaryPattern = '/glossary/usi';
		if (false === ($result = preg_match_all($glossaryPattern,$text))){
			$errText = 'Error searching for pattern "'.$glossaryPattern.'" ';
			switch (preg_last_error()) {
				case PREG_NO_ERROR:
					$errText .= 'No error.';
					break;
				case PREG_INTERNAL_ERROR:
					$errText .= 'Internal error.';
					break;
				case PREG_BACKTRACK_LIMIT_ERROR:
					$errText .= 'Backtrack limit error.';
					break;
				case PREG_RECURSION_LIMIT_ERROR:
					$errText .= 'Recursion limit error.';
					break;
				case PREG_BAD_UTF8_ERROR:
					$errText .= 'Bad UTF8 error.';
					break;
				case PREG_BAD_UTF8_OFFSET_ERROR:
					$errText .= 'Bad UTF8 offset error.';
					break;
				default:
					$errText .= 'Unknown error: "'.preg_last_error().'".';
			}

			$this->error($errText);
		}
		return $result > 0;
	}

	/**
	 * Stores metadata in the database
	 *
	 * @param array $metadata
	 * @param int $fileId
	 */
	protected function saveMetadata(array $metadata,$fileId){
		$stmt = $this->db->prepare('INSERT INTO `metadata` (`file_id`, `name`, `value`) VALUES (:file_id, :name, :value)');
		foreach ($metadata as $key=>$value){
			if (!$stmt->execute(array('file_id'=>$fileId,'name'=>$key,'value'=>$value))){
				$err = $stmt->errorInfo();
				$this->error('Error saving pdf info: "'.$err[2].'".');
			}
		}
	}

	/**
	 * Parses a pdf file and stores individual pages of text in the documentTextDirectory while returning the
	 * metadata information from the pdf.
	 *
	 * @param string $pdfFilename The path to the pdf file to read
	 * @param int $fileId The local id of the pdf file (will be used as a prefix in the documentTextDirectory)
	 *
	 * @return array|bool An array of key=>value pairs for the metadata of the pdf, or false on failure
	 */
	protected function savePdfPages($pdfFilename,$fileId){
		$txtDirectory = $this->documentTextDirectory;
		$ruby = <<<EQL
#!/usr/bin/env ruby
# coding: utf-8

# Extract all text from a single PDF
# PDF text is saved as individual files with page number, e.g. filename_1.txt, filename_2.txt, etc (not zero-indexed)
# PDF metadata is sent back to stdout (or whatever the ruby terminology is...)

require 'rubygems'
require 'pdf/reader'

pdfFilename = "$pdfFilename"
txtFilename = "$txtDirectory$fileId"
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
			// Get the metadata
			$result = stream_get_contents($pipes[1]);
			$result = $this->parsePdfInfoString($result);
//			$return_value = proc_close($process);
			fclose($pipes[1]);
			return $result;
		}
		return false;
	}

	/**
	 * Attempt to remove headers and footers
	 * @param string $text
	 * @return string
	 */
	protected function removeHeadFoot($text,&$headerLength) {
		$headerLength = 0;
		$textArr = mb_split("\n",$text);
		if (count($textArr)>2 && preg_match('/^\s{3}/',$textArr[0]) && mb_strlen($textArr[1],'UTF-8')+mb_strlen($textArr[2],'UTF-8')==0){
			$line = array_shift($textArr); // remove the first line
			$headerLength += mb_strlen($line,'UTF-8')+1;
			while (mb_strlen($textArr[0],'UTF-8')==0){
				$line = array_shift($textArr); // remove empty lines following the first
				$headerLength += mb_strlen($line,'UTF-8')+1;
			}
		}
		while (count($textArr) > 0 && mb_strlen($textArr[count($textArr)-1],'UTF-8')==0){
			array_pop($textArr);
		}
		$l = count($textArr);
		if (count($textArr)>2 && preg_match('/^\s{3}/',$textArr[$l-1]) && mb_strlen($textArr[$l-2],'UTF-8')+mb_strlen($textArr[$l-3],'UTF-8')==0){
			array_pop($textArr); // remove the last line
			while (mb_strlen($textArr[count($textArr)-1],'UTF-8')==0){
				array_pop($textArr); // remove empty lines preceding the last
			}
		}
		return implode("\n",$textArr);
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
					return $this->deleteRelatedFiles($id,$location);
				} else {
					$err = $stmt->errorInfo();
					$this->error('Delete failed with error: '.$err[2]);
				}
			}
		}
	}

	protected function deleteRelatedFiles($id,$location){
		if (!unlink($location)) $this->error('Failed to delete file');
		$i=1;
		while (file_exists($filename=$this->documentTextDirectory.$id.'_'.$i.'.txt')){
			if (!unlink($filename)) $this->error('Failed to delete text cache for page '.$i.'. Stopping.');
			$i++;
		}
		return 'Deleted item '.$id;
	}

	/**
	 * @var PDO
	 */
	protected $db;
	protected $metadataTable;

	function __construct(){
		global $db;
		$this->db = $db;
		$this->table = 'file';
		$this->metadataTable = 'metadata';
		$this->allowedExtensions = array('pdf');
		$this->cacheDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR;
		$this->documentDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$documentCacheFolder.DIRECTORY_SEPARATOR;
		$this->documentTextDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$documentTextCacheFolder.DIRECTORY_SEPARATOR;
		mb_regex_encoding('UTF-8');
		$this->contextLength = 150;
	}
}

