<?php
	$feedback_negative = Session::get('feedback_negative');
	
	$authorize_params = Session::get('authorize');
	$prehashed = isset($authorize_params['authorize_prehashed']) ? true : false;
	//Session::set('authorize', null);
?>
<section class="container">
	<?php $this->renderFeedbackMessages(); ?>
    <form class="rows-15" action="<?php echo URL.$this->action; ?>/" method="post">
    	<h3><?php echo $this->title; ?></h3>
        <label class="<?php echo $this->pgp_only ? 'hidden ' : false ?>row text<?php echo isset($feedback_negative['username']) ? ' invalid' : false ?>">
            <input class="big prepend rounded" type="<?php echo $this->pgp_only ? 'hidden' : 'text' ?>" placeholder="Your Username" required name="authorize_username" tabindex="1"<?php echo isset($authorize_params['authorize_username']) ? ' value="'.$authorize_params['authorize_username'].'"' : ''; ?>>
            <i class="fa-user"></i>
            <?php if( isset($feedback_negative['username']) ) { ?>
            <div class="note"><strong>Error:</strong> <?php echo $feedback_negative['username'] ?></div>
            <?php } ?>
        </label>
        <label class="<?php echo $this->pgp_only ? 'hidden ' : false ?>row text<?php echo isset($feedback_negative['password']) ? ' invalid' : false ?>">
            <input class="big prepend rounded" type="<?php echo $this->pgp_only ? 'hidden' : 'password' ?>" placeholder="Your Password" required name="authorize_password" tabindex="2"<?php echo isset($authorize_params['authorize_password']) ? ' value="'.$authorize_params['authorize_password'].'"' : ''; ?>>
            <i class="fa-lock"></i>
            <?php if( isset($feedback_negative['password']) ) { ?>
            <div class="note"><strong>Error:</strong> <?php echo $feedback_negative['password'] ?></div>
            <?php } ?>
        </label>
        <?php /*?><label class="<?php echo $this->pgp_only ? 'hidden ' : false ?>row checkbox label">
        	<input type="checkbox" name="authorize_prehashed"<?php echo $prehashed ? ' checked' : '' ?>><i></i>Use pre-hashed values
            <a class="tooltip inline">What is this?</a><div><p>If you fear that our servers have been compromised, you can choose to use pre-hashed credentials instead of plaintext values. Algorithms are as follows:</p><div class="cols-15"><div class="col-6"><strong>Username:</strong> <em>sha-1</em></div><div class="col-6"><strong>Password:</strong> <em>sha-512</em></div></div><p>Note that this <strong>is not</strong> an anti-phishing mechanism.</p></div>
        </label><?php */?>
        <?php if ( !empty($this->pgp_message) ){ ?>
        <div class="row">
        	<label class="label">
                <a class="tooltip left">Decrypt this message with PGP</a><div><p>Your account has PGP authentication enabled. To continue, decrypt the message below to find your one-time authentication code. Paste this code in the box below.</div><span> to authenticate:</span>
            </label>
            <label class="textarea rounded pgp">
                <textarea rows="19" spellcheck="false" onclick="this.focus();this.select()"><?php echo $this->pgp_message; ?></textarea>
            </label>
        </div>
        <label class="row text<?php echo isset($feedback_negative['authentication_code']) ? ' invalid' : false; ?>">
        	<input class="big prepend rounded" type="text" placeholder="Authentication Code" required name="authentication_code" tabindex="3">
            <i class="fa-shield"></i>
            <?php if (isset($feedback_negative['authentication_code'])) { ?>
            <p class="note"><?php echo $feedback_negative['authentication_code']; ?></p>
            <?php } ?>
        </label>
        <?php } ?>
        <label class="row"><input name="authorizing" class="btn wide big color rounded <?php echo $this->button[1] ?>" type="submit" value="<?php echo $this->button[0] ?>"></label>
        <p class="row centered">Changed your mind? <a href="<?php echo $this->return_destination; ?>">Go back &#8617;</a></p>
    </form>
</section>