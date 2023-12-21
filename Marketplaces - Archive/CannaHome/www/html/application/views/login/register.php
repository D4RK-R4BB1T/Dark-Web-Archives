<?php
	$feedback_negative = Session::get('feedback_negative');
	$username = Session::get('register_username');
	$prehashed = null !== Session::get('register_prehashed') ? true : false;
	Session::set('register_username', null);
	Session::set('register_prehashed', null);
?>
<a class="btn corner" href="<?php echo URL ?>">Browse Marketplace</a>
<section class="container">
	<?php $this->renderFeedbackMessages(); ?>
    <form class="rows-15" action="<?php echo URL; ?>register/register_action/" method="post">
        <label class="row text<?php echo isset($feedback_negative['username']) ? ' invalid' : false ?>">
            <input class="big prepend" type="text" placeholder="Username" required name="username"<?php echo $username ? ' value="'.$username.'"' : ' autofocus' ?> tabindex="1">
            <i class="fa-user"></i>
            <?php if( isset($feedback_negative['username']) ) { ?>
            <div class="note"><strong>Error:</strong> <?php echo $feedback_negative['username'] ?></div>
            <?php } else { ?>
			<div class="note">Purely for authentication. This is <strong>NOT</strong> your alias.<a class="tooltip inline">EXPLAIN</a>
            	<div>
                	<p>Your <strong>username</strong> is used for login only. Keep this secret!
                    <p>Once registered, you may add a <strong>public alias</strong> to your account. Your alias will be visible to everyone across the entire marketplace in messages, comments, profiles, etc.</p>
                    <p>For security reasons, your username should be different from your public alias.</p>
                </div>
            </div>
            <?php } ?>
        </label>
        <label class="row text<?php echo isset($feedback_negative['password']) ? ' invalid' : false ?>">
            <input class="big prepend" type="password" placeholder="Password" required name="password"<?php echo $username ? ' autofocus' : false ?> tabindex="2">
            <i class="fa-lock"></i>
            <?php if( isset($feedback_negative['password']) ) { ?>
            <div class="note"><strong>Error:</strong> <?php echo $feedback_negative['password'] ?></div>
            <?php } ?>
        </label>
        <?php /*?><label class="row checkbox label">
        	<input type="checkbox" name="prehashed"<?php echo $prehashed ? ' checked="checked"' : '' ?>><i></i>Register with pre-hashed values
            <a class="tooltip inline">What is this?</a><div><p>If you fear that our servers have been compromised, you can choose to register with pre-hashed credentials instead of plaintext values. Algorithms are as follows:</p><div class="cols-15"><div class="col-6"><strong>Username:</strong> <em>sha-1</em></div><div class="col-6"><strong>Password:</strong> <em>sha-512</em></div></div><p>Note that this <strong>is not</strong> an anti-phishing mechanism.</p><p>If you don't know what hashing is, leave this blank.</p></div>
        </label><?php */?>
        <div class="row captcha" style="background-image: url(<?php echo URL; ?>register/showCaptcha?<?php echo time(); ?>);"></div>
        <label class="row text<?php echo isset($feedback_negative['captcha']) ? ' invalid' : false ?>">
            <input class="big prepend" type="text" name="captcha" placeholder="Type character from the image" required tabindex="3">
            <i class="fa-shield"></i>
            <?php if( isset($feedback_negative['captcha']) ) { ?>
            <div class="note"><strong>Error:</strong> <?php echo $feedback_negative['captcha'] ?></div>
            <?php } ?>
        </label>
        <label class="row">
            <input class="btn wide big color" type="submit" value="Sign Up">
        </label>
    </form>
</section>
<p class="row">Already registered? <a href="<?php echo URL; ?>login/">Log in <i class="fa-long-arrow-right"></i></a></p>