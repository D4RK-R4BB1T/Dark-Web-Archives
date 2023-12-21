<?php
class Chart extends Controller {
	function __construct(){
		parent::__construct(FALSE, TRUE, FALSE, TRUE);
	}
	
	function __call($filename, $arguments){
		if (!$this->User->IsVendor)
			die;
		
		$accountModel = $this->loadModel('Account');
		return $accountModel->renderUserQueryGraph($filename);
	}
}
