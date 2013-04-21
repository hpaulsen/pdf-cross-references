<?php

/**
 * Rest
 *
 * Creates a simple REST interface
 */

class Rest {

	public function handle() {
		$method = $_SERVER['REQUEST_METHOD'];
		if (method_exists($this,$method)){
			$data = $this->$method();
		} else {
			$this->error('Requested method "'.$method.'" not implemented for this class',404);
		}
		$this->respond($data);
	}

	protected function respond($data){
		$this->setStatusHeader();
		if (count($this->errors) > 0){
			echo json_encode($this->errors);
		} else {
			echo json_encode($data);
		}
	}

	public function error($message,$status=500,$fatal=true,$type='error'){
		$this->responseStatusCode = $status;
		$this->errors[] = array('type'=>$type,'message'=>$message);
		if ($fatal){
			$this->respond(null);
			exit;
		}
	}

	public function warning($message){
		$status = $this->responseStatusCode; // don't change it
		$this->error($message,$status,false,'warning');
	}

	/**
	 * This is unnecessary in php >= 5.4
	 *
	 * @param $status int
	 */
	public function setStatusHeader(){
		switch ($this->responseStatusCode){
			case 400:
				header('HTTP/1.0 400 Bad Request');
				break;
			case 404:
				header('HTTP/1.0 404 Not Found');
				break;
			case 500:
				header('HTTP/1.0 500 Internal Server Error');
				break;
		}
	}

	protected $errors;
	protected $responseStatusCode;

	function __construct(){
		$this->errors = array();
		$this->responseStatusCode = 200;
	}
}
