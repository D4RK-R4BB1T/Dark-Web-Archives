<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $this->SiteName; ?></title>
	<link rel="shortcut icon" href="<?php echo $this->SiteFaviconPath ?>" type="image/x-icon">
	<link rel="stylesheet" href="<?php echo $this->SiteStylesheetPath; ?>" type="text/css">
	<?php if( isset($this->inlineStylesheet) ) echo '<style>' . $this->inlineStylesheet . '</style>'; ?>
	<style>body:after{content:'';position:fixed;top:0;right:0;bottom:0;left:0;background:#FBFBF8;z-index:90;transition:all 200ms;pointer-events:none}@keyframes loader{from,to{transform:scale(1)}50%{transform:scale(1.1)}}.spinner{position:fixed;line-height:100vh;z-index:999;transition:all 200ms;pointer-events:none}.spinner>svg{width:100vw;animation:loader 1s infinite}</style>
</head>
<body<?= $this->serverLoadStats ? ' data-info="' . $this->serverLoadStats . '"' : false; ?>>
	<?php /* <div class="spinner"><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 535 534" version="1.1"><path fill="#<?php echo $this->SitePrimaryColor; ?>" opacity="1" d="M270.5 45.5C302.1 126.8 313.6 215.7 302.9 302.3 344.2 243.2 400.8 194.9 465.4 163.1 439.2 240.3 389.7 310.8 322.1 357.1 382.2 333.8 448.7 328.2 512.2 338.9 459.3 375 395.7 396.7 331.3 396.6 370.4 406.9 407.4 426.7 437.1 454.2 380 455.9 321.9 441.2 274.2 409.3 269.1 438 275.1 467.4 283.3 495 280.6 495.5 277.9 496.1 275.2 496.5 262.7 469.5 261.7 438.9 263.9 409.8 216.4 440.9 159.1 456.2 102.4 454.1 132.3 426.7 169.1 406.9 208.2 396.5 145.4 395.8 83.5 374.1 31.8 338.8 90.8 329 152.3 333 209 352.4 144.9 305.9 97.6 237.7 72.5 162.9 135.5 194.4 191.2 241 232.2 298.4 221.6 212.7 235.7 124.4 270.5 45.5Z"/></svg></div> */ ?>
	<div class="container">
		<?php /*if (!$this->Member) { 
		
			$login_feedback = Session::get('feedback_negative');
			$login_username = Session::get('login_username');
			$login_prehashed = Session::get('login_prehashed');
			$login_return = isset($_GET['return']) ? $_GET['return'] : Session::get('login_return');
		
			unset($_SESSION['feedback_negative'], $_SESSION['login_username'], $_SESSION['login_prehashed']);
		
		?>
		<input type="checkbox" id="login-modal" hidden<?php echo isset($_GET['login']) ? ' checked' : false ?>>
		<div class="modal">
			<label for="login-modal"></label>
			<div>
				<?php if($login_feedback['general']) foreach( $login_feedback['general'] as $general_feedback ) { ?>
				<div class="notification red">
					<a>
						<i class="fa-exclamation-triangle"></i>
						<div>
							<div>
								<span><?php echo $general_feedback; ?></span>
							</div>
						</div>
					</a>
					<a class="close" href="?login">&times;</a>
				</div>
				<?php } ?>
				<form class="rows-10" action="<?php echo URL; ?>login/login/" method="post">
					<input type="hidden" name="return" value="<?php echo $this->currentPath ?>">
					<?php echo $this->AccessPrefix ? '<input type="hidden" name="prefix" value="' . $this->AccessPrefix . '">' : false ?>
					<label class="row"><p class="note centered">Log in or register to continue</p></label>
					<label class="row text<?php echo isset($login_feedback['username']) ? ' invalid' : false ?>">
						<input class="big prepend" type="text" placeholder="Username" required name="username"<?php echo $login_username ? ' value="'.$login_username.'"' : ' autofocus' ?> tabindex="1">
						<i class="fa-user"></i>
						<?php if( isset($login_feedback['username']) ) { ?>
						<div class="note"><strong>Error:</strong> <?php echo $login_feedback['username'] ?></div>
						<?php } ?>
					</label>
					<label class="row text<?php echo isset($login_feedback['password']) ? ' invalid' : false ?>">
						<input class="big prepend" type="password" placeholder="Password" required name="password"<?php echo $login_username ? ' autofocus' : false ?> tabindex="2">
						<i class="fa-lock"></i>
						<?php if( isset($login_feedback['password']) ) { ?>
						<div class="note"><strong>Error:</strong> <?php echo $login_feedback['password'] ?></div>
						<?php } ?>
					</label>
					<div class="row captcha" style="background-image: url(<?php echo URL; ?>login/showCaptcha?<?php echo time(); ?>);">
						<a href="?login"><i class="fa-refresh"></i></a>
					</div>
					<label class="row text<?php echo isset($login_feedback['captcha']) ? ' invalid' : false ?>">
						<input class="big prepend" type="text" name="captcha" placeholder="What's the word?" required tabindex="3">
						<i class="fa-shield"></i>
						<?php if (isset($login_feedback['captcha'])){ ?>
						<p class="note"><?php echo $login_feedback['captcha'] ?></p>
						<?php } ?>
					</label>
					<div class="row cols-5">
						<div class="col-8"> 
							<label>
								<input name="action" class="btn wide big" value="Log In" type="submit">
							</label>
						</div>
						<div class="col-4"> 
							<div class="magic-button">
								<input type="password" name="password_confirm" id="password_confirm" placeholder="confirm password">
								<button type="submit" name="action" class="btn wide big green">Register</button>
								<label for="password_confirm" class="btn wide big blue">Register</label>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php } else { ?>
		<input type="checkbox" hidden id="support-modal">
		<div class="modal">
			<label for="support-modal"></label>
			<div>
				<form method="post" class="rows-5" action="<?php echo URL . 'account/send_message/'; ?>">
					<input type="hidden" name="recipient_alias" value="<?php echo ALIAS_SUPPORT; ?>">
					<input type="hidden" name="auto_delete" value="0">
					<label class="row select">
						<select name="subject" required>
							<option disabled selected>I need help with...</option>
							<option value="I need help with &quot;buying&quot;">Buying</option>
							<option value="I need help with &quot;selling&quot;">Selling</option>
							<option value="I need help with &quot;signing a multisig transaction&quot;">Signing a multisig transaction</option>
						</select>
					</label>
					<label class="row textarea">
						<textarea rows="10" name="content" placeholder="Please describe your issue in as much detail as possible"></textarea>
					</label>
					<button type="submit" class="row btn wide">Submit Ticket</button>
					<label><p class="note">A member of support will contact you within 24 hours.</p></label>
				</form>
			</div>
		</div>
		<?php } */ ?>
		<header>
			<div class="top">
				<div class="sites">
					<?php foreach($this->sites as $site) { ?><a<?php echo $site['Available'] ? ' target="_blank" href="' . $site['URL'] . '"' : false; ?> class="<?php echo $site['color'] ?>"><?php echo $site['Domain'] ?></a><?php } ?>
				</div>
				<a class="logo" href="<?php echo URL; ?>">
					<?php echo $this->SiteLogo ?>
				</a>
				<nav>
					<div>
						<a href="<?php echo URL; ?>">Home</a>
						<a href="<?php echo URL . 'blogs/'; ?>">Blog Posts</a>
					</div>
					<div>
						<a href="<?php echo URL . 'account/messages'; ?>">Messages<?php echo $this->MessageCount ? '<span>' . $this->MessageCount . '</span>' : FALSE ?></a>
						<a href="<?php echo URL . 'account/settings/'; ?>">Settings</a>
						<a href="<?php echo URL . 'login/logout/'; ?>">Log Out</a>
					</div>
				</nav>
			</div>
		</header>
		<?php if( file_exists(VIEWS_PATH . $folder . '/prepend.php') ) require VIEWS_PATH . $folder . '/prepend.php'; ?>
		<section id="main" class="view-<?php echo $folder ?>">
			<?php $this->renderNotifications(array('Urgent')); ?>
			<?php if( file_exists(VIEWS_PATH . $folder . '/header.php') ) require VIEWS_PATH . $folder . '/header.php'; ?>
