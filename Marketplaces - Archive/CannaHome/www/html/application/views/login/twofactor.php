<?php
	$authorize = $_SESSION['twoFA'];
	$loginFeedback = $_SESSION["feedback_negative"];
	
	$this->renderFeedbackMessages();
?>
	<section id="main">
	<form class="rows-10 login-form" action="<?php echo URL; ?>login/login_pgp/" method="post">
		<div class="row">
			<label class="label">
				<a class="tooltip left">Decrypt this message with PGP</a>
				<div><p>Your account has PGP authentication enabled. To continue, decrypt the message below to find your one-time authentication code. Paste this code in the box below.</p></div><span> to authenticate:</span>
			</label>
			<label class="textarea">
				<textarea readonly rows="15"><?php echo $authorize['message'] ?></textarea>
			</label>
		</div>
		<label class="row text<?php echo isset($loginFeedback['authentication_code']) ? ' invalid' : false ?>">
			<input class="big prepend" type="text" name="authentication_code" placeholder="Authentication Code" required autofocus>
			<i class="<?php echo Icon::getClass('KEY', true); ?>"></i>
			<?php if( isset($loginFeedback['authentication_code']) ) { ?>
			<div class="note"><strong>Error:</strong> <?php echo $loginFeedback['authentication_code'] ?></div>
			<?php } ?>
		</label>
		<label class="row">
			<input name="action" class="btn wide big" value="Authenticate" type="submit">
		</label>
	</form>
</section>
<?php unset($_SESSION['feedback_negative']); ?>
