<?php
$login_feedback = Session::get('feedback_negative');
$login_username = isset($_POST['username']) ? $_POST['username'] : Session::get('login_username');
$login_password = isset($_POST['password']) ? $_POST['password'] : FALSE;
$login_prehashed = Session::get('login_prehashed');
$login_return = isset($_GET['return']) ? $_GET['return'] : Session::get('login_return');
$login_invite_code = isset($_POST['invite_code']) ? $_POST['invite_code'] : Session::get('login_invite_code');

$time = time();

if( isset($_GET['other_alias']) )
      unset($_SESSION['reserved_username']);

$registerAttempt = false;
if(
	isset($_SESSION['register_attempt']) &&
	$registerAttempt = $_SESSION['register_attempt']
)
	unset($_SESSION['register_attempt']);

$failedRegister = isset($_SESSION["feedback_negative"]['failed_register']) && $_SESSION["feedback_negative"]['failed_register'];

$this->renderFeedbackMessages(); ?>
<section id="main">	
	<input form="login-form" name="user_action" value="login" id="user_action-login"<?php echo $registerAttempt ? false : ' checked'; ?> type="radio" hidden="">
	<input form="login-form" name="user_action" value="register" id="user_action-register"<?php echo $registerAttempt ? ' checked' : false; ?> type="radio" hidden="">
	<form class="rows-10 formatted login-form<?php echo $this->inviteOnly ? ' invite-only' : false; ?>" id="login-form" action="<?php echo URL; ?>login/login/" method="post">
		<label class="switch-toggle" for="user_action-login"></label>
		<label class="switch-toggle" for="user_action-register"></label>
		<div class="switch-button" data-login_label="Log In" data-register_label="Register a new account"></div>
		<?php if ( isset( $_SESSION['reserved_username']) && $reservation = $_SESSION['reserved_username'] ) { ?>
		<div class="modal undismissable">
			<div>
				<a class='close' href="/login/?other_alias">&times;</a>
				<div class="rows-10">
					<div class="row">
						<p class="color-blue">This username has been reserved for an existing member of our community.</p>
					</div>
					<div class="row">
						<label class="label">Decrypt this message to verify PGP public key:</span></label>
						<label class="textarea">
							<textarea readonly rows="15"><?php echo $reservation['message'] ?></textarea>
						</label>
					</div>
					<label class="row text<?php echo isset($feedback_negative['reserved_pgp_code']) ? ' invalid' : false ?>">
						<input class="prepend" type="text" placeholder="Authentication Code" name="reserved_pgp_code">
						<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
						<?php if ( isset($feedback_negative['reserved_pgp_code']) ) { ?>
						<p class="note"><?php echo $feedback_negative['reserved_pgp_code']; ?></p>
						<?php } ?>
					</label>
					<button name="action" type="submit" value='Register' class="row btn wide color">Verify</button>
				</div>
			</div>
		</div>
		<?php } ?>
		<label class="row text display-register<?php echo isset($login_feedback['invite_code']) ? ' invalid' : false ?>">
			<input type="text" class="prepend big" pattern="[a-zA-Z0-9]{10}" placeholder="<?= $this->inviteOnly ? 'Enter your invite code' : 'Invite code (optional)' ?>" name="invite_code"<?php echo $login_invite_code ? ' value="' . $login_invite_code . '"' : false; ?> tabindex="1">
			<i class="<?= Icon::getClass('STAR', true); ?>"></i>
			<?php if( isset($login_feedback['invite_code']) ) { ?>
			<div class="note"><strong>Error:</strong> <?php echo $login_feedback['invite_code'] ?></div>
			<?php } ?>
		</label>
		<input type="hidden" name="return" value="<?php echo $this->currentPath ?>">
		<?php echo $this->AccessPrefix ? '<input type="hidden" name="prefix" value="' . $this->AccessPrefix . '">' : false ?>
		<label class="row text display-register display-login<?php echo isset($login_feedback['username']) ? ' invalid' : false ?>">
			<input class="big prepend" type="text" placeholder="Username" required name="username"<?php echo $login_username ? ' value="'.$login_username.'"' : ' autofocus' ?> tabindex="2">
			<i class="<?php echo Icon::getClass('USER'); ?>"></i>
			<?php if( isset($login_feedback['username']) ) { ?>
			<div class="note"><strong>Error:</strong> <?php echo $login_feedback['username'] ?></div>
			<?php } ?>
		</label>
		<label class="row text display-register display-login<?php echo isset($login_feedback['password']) ? ' invalid' : false ?>">
			<input class="big prepend" type="password" placeholder="Password" required name="password"<?php echo $login_username ? ' autofocus' : false ?> <?php echo isset($_SESSION['reserved_username']) ? ' value="'.$_SESSION['reserved_username']['stored_password'].'"' : false ?> tabindex="3">
			<i class="<?php echo Icon::getClass('LOCK'); ?>"></i>
			<?php if( isset($login_feedback['password']) ) { ?>
			<div class="note"><strong>Error:</strong> <?php echo $login_feedback['password'] ?></div>
			<?php } ?>
		</label>
		<?php if ( !isset($_SESSION['reserved_username']) ) { ?>
		<div class="row captcha display-register display-login" style="background-image: url(<?php echo URL; ?>login/showCaptcha?<?php echo time(); ?>)">
			<a href="?login"><i class="<?php echo Icon::getClass('REFRESH'); ?>"></i></a>
		</div>
		<label class="row text display-register display-login<?php echo isset($login_feedback['captcha']) ? ' invalid' : false ?>">
			<input class="big prepend" type="text" name="captcha" placeholder="What's the word?" required tabindex="4">
			<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
			<?php if (isset($login_feedback['captcha'])){ ?>
			<p class="note"><?php echo $login_feedback['captcha'] ?></p>
			<?php } ?>
		</label>
		<?php } ?>
		<div class="row cols-5 display-login action-buttons">
			<div class="col-8"> 
				<label>
					<button name="action" type="submit" class="btn wide big green">Log In</button>
				</label>
			</div>
			<div class="col-4"> 
				<label class="btn wide big <?php echo $failedRegister ? 'yellow' : 'red'; ?>" for="user_action-register"><?php echo $failedRegister ? 'Click Here' : 'Register'; ?></label>
			</div>
		</div>
		<div class="row cols-5 display-register action-buttons">
			<div class="col-8"> 
				<label>
					<button name="action" type="submit" class="btn wide big green">Log In</button>
				</label>
			</div>
			<div class="col-4"> 
				<div class="magic-button">
					<input type="password" name="password_confirm" id="password_confirm" placeholder="confirm password">
					<button type="submit" name="action" class="btn wide big <?php echo $failedRegister ? 'yellow' : 'red'; ?>"><?php echo $failedRegister ? 'Click Here' : 'Register'; ?></button>
					<label for="password_confirm" class="btn wide big <?php echo $failedRegister ? 'yellow' : 'red arrow-right'; ?>"><?php echo $failedRegister ? 'Click Here' : 'Register'; ?></label>
				</div>
			</div>
		</div>
		<?php /*if ($this->inviteOnly) { ?>
		<p class="display-register">Need an invite code? <a target="_blank" href="<?= INVITE_REQUEST_SITE_URL; ?>">Click here</a> to request one.</p>
		<?php }*/ ?>
	</form>
</section>

<script>
document.getElementById('login-form').addEventListener(
	"submit",
	function(e){
		var passwordConfirm = document.getElementById('password_confirm');
		var passwordConfirmValue = passwordConfirm.value;
		if(
			document.getElementById('user_action-register').checked &&
			(
				passwordConfirmValue==null ||
				passwordConfirmValue==""
			)
		){
			e.preventDefault();
			passwordConfirm.focus();
		}
	}
);
</script>
<?php 
unset(
	$_SESSION['feedback_negative'],
	$_SESSION['login_username'],
	$_SESSION['login_prehashed'],
	$_SESSION['login_invite_code']
);

?>
