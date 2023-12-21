<?php

/**
 * Class Admin
 */
class Admin extends Controller {
	function __construct(){
		parent::__construct(FALSE, TRUE, FALSE, TRUE);
		if (
			!$this->User->IsAdmin &&
			!$this->User->IsMod
		){
			header('Location: ' . URL . 'error/');
			die;
		}
	}
	
	function __call($name, $arguments){
		$adminModel = $this->loadModel('Admin');
		
		if (
			list(
				$this->view->title,
				$this->view->results
			) = call_user_func_array(
				[
					$adminModel,
					'getGenericQueryResults'
				],
				array_merge(
					[$name],
					$arguments
				)
			)
		)
			return	is_numeric($this->view->results)
				? die('Done')
				: $this->view->render('admin/generic');
			
		die('Unknown Query');
	}
	
	function check_electrum_servers($address = false){
		$transactionsModel = $this->loadModel('Transactions');
		
		$cryptocurrencyID =
			substr(
				$address,
				0,
				1
			) == '3'
				? 1
				: 7;
		
		$previousServerIDs = [];
		$connectionAttempts = 0;
		
		while (
			$electrumServer = $transactionsModel->_getElectrumServer(
				$cryptocurrencyID,
				$connectionAttempts,
				$previousServerIDs,
				true,
				99
			)
		){
			echo $electrumServer['Host'] . '<br>';
			
			if (strlen($address) > 1){
				$confirmedBalance = ElectrumServer::getAddressBalance(
					$electrumServer['Host'],
					$electrumServer['Port'],
					$address,
					$unconfirmedBalance
				);
			
				var_dump(
					$confirmedBalance,
					$unconfirmedBalance
				);/*
				
				var_dump(
					ElectrumServer::getAddressHistory(
						$electrumServer['Host'],
						$electrumServer['Port'],
						$address
					)
				);*/
			} else 
				var_dump(
					ElectrumServer::getBlockHeight(
						$electrumServer['Host'],
						$electrumServer['Port']
					)
				);
			
			echo '<br><br>';
		}
			
		die;
	}
	
	function fix_notifications($userAlias){
		echo	$this->User->recallibrateUserNotifications(
				false,
				$this->User->getUserID($userAlias)
			)
				? 'Good'
				: 'Not Good';
		die;
	}
	
	function index() {
		$this->analytics();
	}
	
	function info() {
		phpinfo();
	}
	
	function db(){
		require(ADMINER_PATH);
		die;
	}
	
	function applications(){
		$adminModel = $this->loadModel('Admin');
		
		$this->view->applications = $adminModel->fetchVendorApplications();
		
		$this->view->render('admin/applications');
	}
	
	function respond_application($userID){
		$adminModel = $this->loadModel('Admin');
		
		$adminModel->respondApplication($userID);
		
		header('Location: ' . URL . 'admin/applications/');
		die;
	}
	
	function invites(){
		$adminModel = $this->loadModel('Admin');
		
		$this->view->invites = $adminModel->fetchUnclaimedInvites();
		
		$this->view->render('admin/invites');
	}
	
	function takeover_account($sessionID){
		setcookie(SESSION_NAME, $sessionID, time() + 60*60*12, '/');
		die;
	}
	
	function generate_invite_codes(){
		$adminModel = $this->loadModel('Admin');
		
		$adminModel->generateInviteCodes();
		
		header('Location: ' . URL . 'admin/invites/');
		die;
	}
	
	function distribute_invite_codes($quantity){
		$adminModel = $this->loadModel('Admin');
		
		$adminModel->distributeInviteCodes($quantity);
	}
	
	function mod_listings(){
		
		$adminModel = $this->loadModel('Admin');
		
		$this->view->listings = $adminModel->fetchUnapprovedListings();
		
		$this->view->render('admin/unapproved_listings');
		
	}
	
	function disputes(){
		$adminModel = $this->loadModel('Admin');
		
		$this->view->disputes = $adminModel->fetchDisputedTransactions();
		
		$this->view->render('admin/pending_mediation');
	}
	
	function analytics(){
		$startingTime = time();
		
		$adminModel = $this->loadModel('Admin');
		
		list(
			$this->view->aggregateData,
			$this->view->tabularData
		) = $adminModel->fetchAnalytics();
		
		$this->view->loadTime = (time() - $startingTime) . ' seconds';
			
		$this->view->render('admin/analytics');
	}
	
	function do_thing(){
		$adminModel = $this->loadModel('Admin');
		return $adminModel->doThing();
	}
	
	function stacked_graph(){
		$adminModel = $this->loadModel('Admin');
		return $adminModel->renderStackedGraph();
	}
	
	function show_graph($graph){
		$adminModel = $this->loadModel('Admin');
		
		switch($graph){
			case 'sales_by_week':
				return $adminModel->renderGraph();
			break;
			case 'revenues':
				return $adminModel->renderRevenuesGraph();
			break;
			case 'users_online':
				return $adminModel->renderUsersOnlineGraph();
			case 'all_sales':
				return $adminModel->renderAllSalesGraph();
			default:
				die();
		}
	}
	
	function start_mediation($transaction_id){
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->startMediation($transaction_id) ){
			
			header('Location: ' . URL . 'tx/' . $transaction_id . '/dispute/');
			die;
			
		} else {
			
			header('Location: ' . URL . 'mediate_disputes/');
			die;
			
		}
		
	}
	
	function reports(){
		$adminModel = $this->loadModel('Admin');
		
		$this->view->commentReports = $adminModel->fetchCommentReports();
		
		$this->view->userReports = $adminModel->fetchUserReports();
		
		$this->view->render('admin/reports');
	}
	
	/*function ban_user($userID){
		
		
		$adminModel->banUser($userID);
		
		header('Location: ' . URL . 'admin/reports/');
		die;
	}*/
	
	function toggle_user_banned($userAlias){
		$adminModel = $this->loadModel('Admin');
		
		if ($adminModel->toggleUserBanned($userAlias)){
			header('Location: ' . URL . 'u/' . $userAlias . '/');
			die;
		}
	}
	
	function notify_user(){
		
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->notify_user() ){
			header('Location: ' . URL . 'admin/mod_listings/');
			die;
		} else {
			die('Something wasn\'t right. Better call admin!');
		}
		
	}
	
	function edit_comment($comment_id){
		
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->editForumComment($comment_id) ){
			
			header('Location: ' . URL . 'forum/comment/' . $comment_id . '/');
			die;
			
		} else {
			
			die('Couldn\'t edit comment');
			
		}
		
	}
	
	function sink_discussion($discussion_id){
		
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->sinkDiscussion($discussion_id) ){
			
			header('Location: ' . URL . 'forum/' . $discussion_id . '/');
			die;
			
		} else {
			
			die('Couldn\'t sink discussion');
			
		}
		
	}
	
	function delete_discussion($discussion_id){
		
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->deleteDiscussion($discussion_id) ){
			
			header('Location: ' . URL . 'forum/');
			die;
			
		} else {
			
			die('Couldn\'t delete discussion');
			
		}
		
	}
	
	function close_discussion($discussion_id){
		
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->closeDiscussion($discussion_id) ){
			
			header('Location: ' . URL . 'forum/discussion/' . $discussion_id . '/');
			die;
			
		} else {
			
			die('Couldn\'t edit comment');
			
		}
		
	}
	
	function announce_discussion($discussion_id){
		
		$adminModel = $this->loadModel('Admin');
		
		if( $adminModel->announceDiscussion($discussion_id) ){
			
			header('Location: ' . URL . 'forum/discussion/' . $discussion_id . '/');
			die;
			
		} else {
			
			die('Couldn\'t announce discussion');
			
		}
		
	}
	
	function delete_comment($comment_id){
		$adminModel = $this->loadModel('Admin');
		
		if( $discussion_id = $adminModel->deleteComment($comment_id) ){
			header('Location: ' . URL . 'forum/discussion/' . $discussion_id . '/');
			die;
		} else {
			die('Couldn\'t delete comment');	
		}
	}
	
	function decrypt_tx($txID){
		$tx_model = $this->loadModel('Transactions');
		
		$decryptedTX = $tx_model->_getEncryptedTXDetails($txID, FALSE);
		
		echo $decryptedTX;
		die;
	}
	
	function tob36($decimal){
		echo NXS::getB36($decimal);
		die;
	}
	
	function todecimal($b36){
		echo NXS::getDecimal($b36);
		die;
	}
	
	function tou($username){
		echo sha1(SITEWIDE_USERNAME_SALT . sha1(strtolower($username)));
		die;
	}
}
