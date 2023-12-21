<!doctype html>
<html<?= $this->AccessPrefix ? ' lang="' . $this->AccessPrefix . '"' : false?>>
<head>
<meta charset="UTF-8">
<title><?= $this->SiteName; ?></title>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $this->SiteFaviconPath ?>">
<link rel="stylesheet" href="<?php echo $this->SiteStylesheetPath; ?>">
<?php if ( isset( $this->customStylesheet ) || isset($this->customStylesheet_First) ) { ?>
<style>
<?php echo isset( $this->customStylesheet ) ? $this->customStylesheet : false; ?>
<?php echo isset($this->customStylesheet_First) && $this->first ? $this->customStylesheet_First : false ?>
</style>
<?php } ?>
<style>body:after{content:'';position:fixed;top:0;right:0;bottom:0;left:0;background:#F7FAF9;z-index:90;transition:all 200ms;pointer-events:none}</style>
</head>
<body class="narrow">
	<?php /* if (!$this->incognitoMode){ ?><div class="spinner"><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 535 534" version="1.1"><path fill="#<?php echo $this->SitePrimaryColor; ?>" opacity="1" d="M270.5 45.5C302.1 126.8 313.6 215.7 302.9 302.3 344.2 243.2 400.8 194.9 465.4 163.1 439.2 240.3 389.7 310.8 322.1 357.1 382.2 333.8 448.7 328.2 512.2 338.9 459.3 375 395.7 396.7 331.3 396.6 370.4 406.9 407.4 426.7 437.1 454.2 380 455.9 321.9 441.2 274.2 409.3 269.1 438 275.1 467.4 283.3 495 280.6 495.5 277.9 496.1 275.2 496.5 262.7 469.5 261.7 438.9 263.9 409.8 216.4 440.9 159.1 456.2 102.4 454.1 132.3 426.7 169.1 406.9 208.2 396.5 145.4 395.8 83.5 374.1 31.8 338.8 90.8 329 152.3 333 209 352.4 144.9 305.9 97.6 237.7 72.5 162.9 135.5 194.4 191.2 241 232.2 298.4 221.6 212.7 235.7 124.4 270.5 45.5Z"/></svg></div><?php }*/ ?>
	<section class="header logo centered view-<?php echo $folder ?>">
		<?= $this->SiteLogo; ?>
	</section>
