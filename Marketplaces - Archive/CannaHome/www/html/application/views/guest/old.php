<a class="btn corner" href="<?php echo ($this->AccessPrefix ? 'http://' . $this->AccessPrefix . '.' . substr(URL, 7) : URL) . 'login/' . ( (!empty($this->currentPath) && $this->currentPath !== '/' ) ? '?return=' . $this->currentPath : false ) ?>">Login</a>
<section class="container">
	<form method="post" class="rows-15">
			<div class="row formatted"><p class="centered">Please complete a captcha to view this page</p></div>
			<div class="row captcha" style="background-image: url(<?php echo '/public/img/captcha.php?' . time() . '&color=' . $this->color; ?>);"></div>
			<label class="row text<?php echo $this->invalid ? ' invalid' : false ?>">
				<input type="text" class="big" name="captcha" placeholder="Type characters from the image" required autofocus>
			</label>
			<label class="row">
				<input class="btn wide big color" type="submit" value="Enter">
			</label>
	</form>
</section>