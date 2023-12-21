<?php

/**
 * Class Admin
 */
class API extends Controller
{
	function __construct(){
		parent::__construct(FALSE, TRUE, FALSE, TRUE);
	}
	
	function update_user_prefs(
		$preference,
		$value
	){
		$newPrefs = false;
		switch ($preference){
			case 'LiveUpdate':
				$newPrefs['LiveUpdate'] = $value == 1;
			break;
			case 'EnableSound':
				$newPrefs['EnableSound'] = $value == 1;
			break;
		}
		
		if(
			$newPrefs &&
			$newPrefs != $this->User->Attributes['Preferences']
		)
			$this->User->updatePrefs($newPrefs);
			
		die('Done');
	}
	
	function fetch_user_notifications(){
		$accountModel = $this->loadModel('Account');
		
		$accountModel->getDashboardNotifications();
		
		echo json_encode(
			[
				'messages' => $this->User->Info('MessageCount'),
				'notifications' => array_merge(
					$this->User->Notifications->all['Vendor'],
					$this->User->Notifications->all['Dashboard']
				)
			]
		);
		die;
	}
	
	function query_chat_messages($chatID = FALSE){
		if( !$this->User->IsAdmin && !$this->User->IsMod ){			
			header('Location: ' . URL . 'error/');
			die;
		}
		
		$accountModel = $this->loadModel('Account');
		
		$chatMessages = $accountModel->fetchChatMessages(
			$chatID,
			CHAT_MESSAGES_SORT_MODE_DEFAULT,
			API_QUERY_CHAT_MESSAGES_QUANTITY,
			0,
			TRUE,
			TRUE,
			TRUE
		);
		
		echo json_encode($chatMessages);
		die;
	}
}
