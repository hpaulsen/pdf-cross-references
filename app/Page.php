<?php

require_once Config::$libraryDirectory.DIRECTORY_SEPARATOR.'Rest.php';

class Page extends Rest {
	/** @var string Set in constructor */
	protected $table;
	/** @var PDO */
	protected $db;
	/** @var string Set in constructor */
	protected $documentTextDirectory;

	/**
	 * Returns a list of pages for a given fileId or the text for a particular page if "page" is also provided
	 *
	 * @return mixed
	 */
	function get(){
		if (isset($_GET['file_id'])){
			$fileId = $_GET['file_id'];
			if (isset($_GET['page'])){
				$page = $_GET['page'];
				return $this->getPageText($fileId,$page);
			} else {
				return $this->getPageList($fileId);
			}
		} else {
			$this->error('Required parameter "file_id" not found');
		}
	}

	function put(){
		$data = json_decode(file_get_contents('php://input'));
		if (!isset($data->file_id))
			$this->error('Required member item "file_id" is missing. '.print_r($_SERVER,true),400);
		if (!isset($data->page))
			$this->error('Required member item "page" is missing. '.print_r($_SERVER,true),400);
		if (!isset($data->include))
			$this->error('Required member item "include" is missing.');
		if (!isset($data->is_glossary))
			$this->error('Required member item "is_glossary" is missing.');

		$stmt = $this->db->prepare('UPDATE `'.$this->table.'` SET include=:include, is_glossary=:isGlossary WHERE file_id=:fileId AND page=:page');
		if ($stmt->execute(array('include'=>$data->include,'isGlossary'=>$data->is_glossary,'fileId'=>$data->file_id,'page'=>$data->page))){
			return $data;
		} else {
			$err = $stmt->errorInfo();
			$this->error('Save failed with error: '.$err[2]);
		}
	}

	protected function getPageText($fileId,$page){
		$filename = $this->documentTextDirectory.$fileId.'_'.$page.'.txt';
		if (!file_exists($filename))
			$this->error('Page text not found');
		else
			return array(array('file_id'=>$fileId,'page'=>$page,'text'=>file_get_contents($filename)));
	}

	protected function getPageList($fileId){
		$stmt = $this->db->prepare('SELECT *, (SELECT COUNT(*) FROM cross_reference WHERE file_id=:fileId AND page_number=p.page) AS num_references FROM page AS p WHERE file_id=:fileId');
		if ($stmt->execute(array('fileId'=>$fileId))){
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$err = $stmt->errorInfo();
			$this->error('Failed to load page list: '.$err[2]);
		}
	}

	function __construct(){
		global $db;
		$this->db = $db;
		$this->table = 'page';
		$this->documentTextDirectory = Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$documentTextCacheFolder.DIRECTORY_SEPARATOR;
	}
}