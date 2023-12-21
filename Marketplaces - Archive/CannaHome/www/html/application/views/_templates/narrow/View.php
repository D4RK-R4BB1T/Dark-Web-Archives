<?php 

class templateView extends View {
	
	function __construct(Database $db){
		parent::__construct('narrow', $db);
		$this->db = $db;
	}
	
	public function renderFeedbackMessages(){
		// echo out the feedback messages (errors and success messages etc.),
		// they are in $_SESSION["feedback_positive"] and $_SESSION["feedback_negative"]
		require VIEWS_PATH . '_templates/narrow/feedback.php';

		// delete these messages (as they are not needed anymore and we want to avoid to show them twice
		Session::set('feedback_positive', null);
		Session::set('feedback_negative', null);
	}
}
