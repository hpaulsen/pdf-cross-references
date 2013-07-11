<?php

require_once Config::$libraryDirectory.DIRECTORY_SEPARATOR.'Rest.php';

class CrossReference extends Rest {
	protected $table = 'cross_reference';

	function get(){
		if (isset($_GET['metadata']) && $_GET['metadata']==true){
			$stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM '.$this->table);
			$params = null;
		} elseif (isset($_GET['summary']) && $_GET['summary']==true){
			$q = <<<EOS
SELECT
	f.doc_id AS source,
	cr.referenced_file_id AS reference,
	COUNT(*) AS count
FROM
	cross_reference cr
	JOIN file f ON cr.source_file_id = f.id
WHERE
	cr.referenced_file_id <> f.doc_id
GROUP BY
	f.doc_id,
	cr.referenced_file_id
ORDER BY
	f.doc_id,
	cr.referenced_file_id
EOS;
			$stmt = $this->db->prepare($q);
			$params = null;
		} elseif (isset($_GET['gexf']) && $_GET['gexf']==true){
			header('Content-Type: text/xml; charset=utf-8');
			echo $this->getGexf();
			exit;
		} elseif (isset($_GET['types']) && $_GET['types']==true){
			$stmt = $this->db->prepare('SELECT doc_type AS type FROM file UNION SELECT referenced_file_type AS type FROM cross_reference ORDER BY type');
			$params = null;
		} elseif (isset($_GET['id'])){
			if (isset($_GET['page'])){
				$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE id=:id AND page_number=:page');
				$params = array('id'=>(int)$_GET['id'],'page'=>(int)$_GET['page']);
			} else {
				$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE id=:id');
				$params = array('id'=>(int)$_GET['id']);
			}
		} elseif (isset($_GET['file_id'])) {
			if (isset($_GET['page'])){
				$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE source_file_id=:file_id AND page_number=:page');
				$params = array('file_id'=>(int)$_GET['file_id'],'page'=>(int)$_GET['page']);
			} else {
				$stmt = $this->db->prepare('SELECT * FROM '.$this->table.' WHERE source_file_id=:file_id');
				$params = array('file_id'=>(int)$_GET['file_id']);
			}
		} else {
			$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
			$count = isset($_GET['count']) ? (int)$_GET['count'] : 15;
			$stmt = $this->db->query('SELECT * FROM '.$this->table.' LIMIT '.$start.', '.$count);
			$params = null;
		}
		if ($stmt->execute($params)){
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$err = $stmt->errorInfo();
			$this->error($err[2]);
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
		$filename = Config::$cacheDirectory.DIRECTORY_SEPARATOR.Config::$documentTextCacheFolder.DIRECTORY_SEPARATOR.$documentId.'.txt';
		if (!file_exists($filename)) $this->error('Error - could not locate parsed version of document ("'.$filename.'").');

		$handle = fopen($filename,'r');

		$prevLine1 = '';
		$prevLine2 = '';
		$prevLine3 = '';
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
					$context = $prevLine3.' '.$prevLine2.' '.$prevLine1.' '.$line;
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
			if (strlen($line)>2){
				$prevLine3 = $prevLine2;
				$prevLine2 = $prevLine1;
				$prevLine1 = $line;
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

	protected function getGexf(){

		$date = date('Y-m-d');
		$doc = <<<EOS
<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" version="1.2" xmlns:viz="http://www.gephi.org/gexf/viz">
	<meta lastmodifieddate="$date">
		<creator>nistpdf</creator>
		<description>Cross Reference Analysis</description>
	</meta>
	<attributes class="node" mode="static">
		<attribute id="strength" title="Strength" type="integer"/>
		<attribute id="doc_type" title="Type" type="string"/>
	</attributes>
	<graph mode="static" defaultedgetype="directed">
		<nodes>
		{{nodes_here}}
		</nodes>
		<edges>
		{{edges_here}}
		</edges>
	</graph>
</gexf>
EOS;
		// Check filters
		$restrict = '';
		if (isset($_GET['restrict']) && $_GET['restrict'] !== ''){
			$restrict = preg_replace('/[^\d\w\,]/','',$_GET['restrict']);
			$arr = explode(',',$_GET['restrict']);
			$restrict = "'".implode("','",$arr)."'";
		}

		$nodes = $this->gexfDocList($restrict);
		$doc = preg_replace('/\{\{nodes_here\}\}/',$nodes,$doc);

		$references = $this->gexfReferences($restrict);
		$doc = preg_replace('/\{\{edges_here\}\}/',$references,$doc);

		return $doc;
	}

	protected $uniqueColors = array(
		array(0,0,255),
		array(0,255,0),
		array(255,0,0),
		array(0,255,255),
		array(255,0,255),
		array(255,255,0),
		array(255,255,255),
		array(0,0,192),
		array(0,192,0),
		array(192,0,0),
		array(0,192,192),
		array(192,0,192),
		array(192,192,0),
		array(192,192,192),
	);

	protected function gexfDocList($restrict){
		$restrict1 = ''; $restrict2 = '';
		if ($restrict != ''){
			$restrict1 = 'WHERE doc_type IN ('.$restrict.')';
			$restrict2 = 'WHERE referenced_file_type IN ('.$restrict.')';
		}
		$q = <<<EOS
SELECT doc_id AS doc, doc_type AS type FROM file $restrict1
UNION
SELECT referenced_file_id AS doc, referenced_file_type AS type FROM cross_reference $restrict2
ORDER BY doc
EOS;
		$stmt = $this->db->prepare($q);
		if (!$stmt->execute()){
			$err = $stmt->errorInfo();
			$this->error($err[2]);
		}
		$result = '';
		$typeColors = array();
		while (false !== ($row = $stmt->fetch(PDO::FETCH_ASSOC))){
			$id = $row['doc'];
			$type = $row['type'];
			if (!isset($typeColors[$type])){
				$typeColors[$type] = $this->uniqueColors[count($typeColors)];
			}
			$r = $typeColors[$type][0];
			$g = $typeColors[$type][1];
			$b = $typeColors[$type][2];
			$result .= <<<EOS
<node id="$id" label="$id">
	<viz:color r="$r" g="$g" b="$b"/>
	<attvalues>
		<attvalue for="doc_type" value="$type"/>
	</attvalues>
</node>
EOS;
		}
		return $result;
	}

	protected function gexfReferences($restrict){
		if ($restrict != ''){
			$restrict = 'AND f.doc_type IN ('.$restrict.') AND cr.referenced_file_type IN ('.$restrict.')';
		}
		$q = <<<EOS
SELECT
	f.doc_id AS source,
	cr.referenced_file_id AS reference,
	COUNT(*) AS count
FROM
	cross_reference cr
	JOIN file f ON cr.source_file_id = f.id
WHERE
	cr.referenced_file_id <> f.doc_id $restrict
GROUP BY
	f.doc_id,
	cr.referenced_file_id
ORDER BY
	f.doc_id,
	cr.referenced_file_id
EOS;
		$stmt = $this->db->prepare($q);
		if (!$stmt->execute()){
			$err = $stmt->errorInfo();
			$this->error($err[2]);
		}
		$result = '';
		$i=0;
		while (false !== ($row = $stmt->fetch(PDO::FETCH_ASSOC))){
			$src = $row['source'];
			$target = $row['reference'];
			$strength = $row['count'];
			$result .= <<<EOS
<edge id="$i" source="$src" target="$target">
	<attvalues>
		<attvalue for="strength" value="$strength"/>
	</attvalues>
</edge>
EOS;
			$i++;
		}
		return $result;
	}

	protected $db;

	function __construct(){
		global $db;
		$this->db = $db;
	}

}