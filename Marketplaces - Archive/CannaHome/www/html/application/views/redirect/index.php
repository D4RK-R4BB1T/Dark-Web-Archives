<?php

$loaderScreens = [
	[
		'name'		=> 'Simple',
		'original'	=> "
			body{
				margin:0;
				background: #FBFBF8;
			}
			.spinner {
				display: inline-block;
				position: absolute;
				width: 64px;
				height: 64px;
				left: 50%;
				top: 50%;
				margin: -32px 0 0 -32px;
			}
			.spinner div {
				position: absolute;
				top: 27px;
				width: 11px;
				height: 11px;
				border-radius: 50%;
				background: #a44680;
				animation-timing-function: cubic-bezier(0, 1, 1, 0);
			}
			.spinner div:nth-child(1) {
				left: 6px;
				animation: a1 0.6s infinite;
			}
			.spinner div:nth-child(2) {
				left: 6px;
				animation: a2 0.6s infinite;
			}
			.spinner div:nth-child(3) {
				left: 26px;
				animation: a2 0.6s infinite;
			}
			.spinner div:nth-child(4) {
				left: 45px;
				animation: a3 0.6s infinite;
			}
			@keyframes a1 {
				0% {
					transform: scale(0);
				}
				100% {
					transform: scale(1);
				}
			}
			@keyframes a3 {
				0% {
					transform: scale(1);
				}
				100% {
					transform: scale(0);
				}
			}
			@keyframes a2 {
				0% {
					transform: translate(0, 0);
				}
				100% {
					transform: translate(19px, 0);
				}
			}",
		'style'		=> 'body{margin:0;background:#FBFBF8}.spinner{display:inline-block;position:absolute;width:64px;height:64px;left:50%;top:50%;margin:-32px 0 0 -32px}.spinner div{position:absolute;top:27px;width:11px;height:11px;border-radius:50%;background:#a44680;animation-timing-function:cubic-bezier(0, 1, 1, 0)}.spinner div:nth-child(1){left:6px;animation:a1 0.6s infinite}.spinner div:nth-child(2){left:6px;animation:a2 0.6s infinite}.spinner div:nth-child(3){left:26px;animation:a2 0.6s infinite}.spinner div:nth-child(4){left:45px;animation:a3 0.6s infinite}@keyframes a1{0%{transform:scale(0)}100%{transform:scale(1)}}@keyframes a3{0%{transform:scale(1)}100%{transform:scale(0)}}@keyframes a2{0%{transform:translate(0, 0)}100%{transform:translate(19px, 0)}}',
		'spinner'	=> '<div></div><div></div><div></div><div></div>'
	]
	/*[
		'name'		=> 'Standard',
		'style'		=> "body:after{content:'';position:fixed;top:0;right:0;bottom:0;left:0;background:#FBFBF8;z-index:90;transition:all 200ms;pointer-events:none}@keyframes loader{from,to{transform:scale(1)}50%{transform:scale(1.1)}}.spinner{position:fixed;line-height:100vh;z-index:999;transition:all 200ms<div></div><div></div><div></div><div></div>,
		'spinner'	=> '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 535 534"><path fill="#' . $this->SitePrimaryColor . '" d="M270.5 45.5c31.6 81.3 43 170.2 32.4 256.8C344 243.3 400.7 195 465.3 163 439.2 240.4 389.7 311 322 357c60.2-23.2 126.7-28.8 190.2-18-53 36-116.5 57.7-181 57.6 39.2 10.3 76.2 30 106 57.6-57.2 1.7-115.3-13-163-45-5 28.8 1 58.2 9 85.8l-8 1.5c-12.5-27-13.5-57.6-11.3-86.7-47.6 31-105 46.4-161.6 44.3 30-27.3 66.7-47 105.8-57.5-62.8-.7-124.7-22.4-176.4-57.7 59-9.8 120.5-5.8 177.2 13.6C145 306 97.6 237.7 72.5 163c63 31.4 118.7 78 159.7 135.4-10.6-85.7 3.5-174 38.3-253z"/></svg>'
	]
	[
		'name'		=> 'Colorful',
		'style'		=> "
			body{margin:0}
			@keyframes rotateHue{0%{filter:hue-rotate(0deg)}to{filter:hue-rotate(360deg)}}
			.spinner {
				position: fixed;
				line-height: 100vh;
				z-index: 999;
				transition: all 200ms;
				pointer-events: none;
				width: 100%;
				text-align: center;
			}
			.spinner::before {
				content: '';
				background-color: #E9E9D8;
				padding: 10px;
				position: absolute;
				left: calc(50% - 145px/2 - 10px);
				top: calc(50% - 145px/2 - 10px);
				width: 145px;
				height: 145px;
				border: solid 1px #D7D7B7;
			}
			.spinner > img {
				vertical-align: middle;
				animation: rotateHue 2s linear infinite;
			}
			body::after {
				content: '';
				position: fixed;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				z-index: 90;
				transition: all 200ms;
				pointer-events: none;
				background: #FBFBF8;
			}
		",
		'spinner'	=> '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJEAAACRCAMAAAD0BqoRAAAAElBMVEUAAABSmFyYUmynRET3mAD///92T2rwAAAABXRSTlMA+Pj4+FVtCwoAAACoSURBVHgB7dm1AQNBDEXBxf5LNhSwoDP75oWKJtZP+Smle+UpEREREW1E1MMNRT3cQRERERERUY42FpVoXyAiIiIiIurTvkZERERERDSIiIhoHBHRn/yPiIiIiIjstERERN8uIvrPpPaU0r36lIiINiIiIurhhqIe7qCIiIiIiCh8G4tqoD8REREREREREREREREREREREYUjGkVERET0izstEREREdEV60b4cUlZ8WsAAAAASUVORK5CYII=">' //<div class="titles"><div>Cannabis</div><div>Growers &amp;</div><div>Merchants</div><div>Co-op</div></div>'
	],
	/*[
		'name'		=> 'Lines',
		'style'		=> "
			body{
				margin:0;
				background: #FBFBF8;
			}
			.spinner {
				position: fixed;
				top: -5vh;
				line-height: 100vh;
				z-index: 999;
				transition: all 200ms;
				pointer-events: none;
				width: 100%;
				text-align: center;
			}
			.spinner::after {
				content: '';
				background-color: #E9E9D8;
				padding: 10px;
				position: absolute;
				left: calc(50% - 145px/2 - 10px);
				top: calc(50% - 145px/2 - 10px);
				width: 145px;
				height: 145px;
				border: solid 1px #D7D7B7;
			}
			.spinner > img {
				position: relative;
				z-index: 1;
				vertical-align: middle;
			}
			.bb, .bb::before, .bb::after {
				position: absolute;
			}

			.bb {
				width: 167px;
				height: 167px;
				left: calc(50% - 145px/2 - 10px);
				top: calc(50% - 145px/2 - 10px);
			}
			.bb::before, .bb::after {
				content: '';
				top: 0;
				bottom: 0;
				left: 0;
				right: 0;
				z-index: -1;
				margin: -8%;
				box-shadow: inset 0 0 0 4px;
				animation: clipMe 8s linear infinite;
			}
			.bb::before {
				animation-delay: -4s;
			}

			@keyframes clipMe {
				0%,
				100% {
					clip: rect(0px, 193.72px, 3px, 0px);
					color: #569a60;
				}
				25% {
					clip: rect(0px, 3px, 193.72px, 0px);
					color: #f79a07;
				}
				50% {
					clip: rect(190.72px, 193.72px, 193.72px, 0px);
					color: #a94949;
				}
				75% {
					clip: rect(0px, 193.72px, 193.72px, 190.72px);
					color: #5d7b89;
				}
			}
		",
		'spinner'	=> '<div class="bb"></div><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJEAAACRCAMAAAD0BqoRAAAAElBMVEUAAABWmmBde4mpSUn3mgf///+xhIPXAAAAAXRSTlMAQObYZgAAAKhJREFUeAHt2bUBA0EMRcHF/ks2FLCgM/vmhYom1k/5KaV75SkRERERbUTUww1FPdxBERERERFRjjYWlWhfICIiIiIi6tO+RkRERERENIiIiGgcEdGf/I+IiIiIiOy0RERE3y4i+s+k9pTSvfqUiIg2IiIi6uGGoh7uoIiIiIiIKHwbi2qgPxERERERERERERERERERERERhSMaRURERPSLOy0RERER0RXrRvhxSVnxawAAAABJRU5ErkJggg==">' //<div class="titles"><div>Cannabis</div><div>Growers &amp;</div><div>Merchants</div><div>Co-op</div></div>'
	],
	[
		'name'		=> 'Shuffling',
		'style'		=> "
			body{
				margin:0;
				background: #FBFBF8;
			}
			@keyframes moveOne {
				from,
				to {
					transform: translate(0,0);
				}
				25% {
					transform: translate(0,74px)
				}
				50% {
					transform: translate(74px,74px);
				}
				75% {
					transform: translate(74px,0px);
				}
			}
			@keyframes moveTwo {
				from,
				to {
					transform: translate(0,0);
				}
				25% {
					transform: translate(-74px,0)
				}
				50% {
					transform: translate(-74px,74px);
				}
				75% {
					transform: translate(0,74px);
				}
			}
			@keyframes moveThree {
				from,
				to {
					transform: translate(0,0);
				}
				25% {
					transform: translate(0,-74px)
				}
				50% {
					transform: translate(-74px,-74px);
				}
				75% {
					transform: translate(-74px,0);
				}
			}
			@keyframes moveFour {
				from,
				to {
					transform: translate(0,0);
				}
				25% {
					transform: translate(74px,0)
				}
				50% {
					transform: translate(74px,-74px);
				}
				75% {
					transform: translate(0,-74px);
				}
			}
			.spinner {
				position: fixed;
				top: -5vh;
				line-height: 100vh;
				z-index: 999;
				transition: all 200ms;
				pointer-events: none;
				width: 100%;
				text-align: center;
			}
			.spinner > div {
				width: 70px;
				height: 70px;
				display: inline-block;
				vertical-align: middle;
				box-shadow: 0 0 5px rgba(0,0,0,.3) inset;
				position: relative;
				animation: moveOne 2.5s cubic-bezier(.6,.01,.4,1) infinite;
				background-image: url(data:image/png;base64,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);
				background-position: 0 0;
				top: -37px;
				left: 68px;
				font-family: 'CoopIcons';
				line-height: 70px;
				font-size: 40px;
			}
			.spinner > div:nth-child(2) {
				background-position: 70px 0;
				top: -37px;
				left: 72px;
				animation-name: moveTwo;
			}
			.spinner > div:nth-child(3) {
				background-position: 0 70px;
				left: 2px;
				top: 37px;
				animation-name: moveThree;
			}
			.spinner > div:nth-child(4) {
				background-position: 70px 70px;
				left: -142px;
				top: 37px;
				animation-name: moveFour;
			}
		",
		'spinner'	=> '<div></div><div></div><div></div><div></div>'
	],
	[
		'name'		=> 'Lineup',
		'style'		=> "
			body{
				margin:0;
				background: #FBFBF8;
			}
			@keyframes bounceUp{
				0%,
				33.33333%,
				100% { transform: translateY(0) }
				16.66667% { transform: translateY(-10px) }
			}
			.spinner {
				position: fixed;
				top: -5vh;
				line-height: 100vh;
				z-index: 999;
				transition: all 200ms;
				pointer-events: none;
				width: 100%;
				text-align: center;
			}
			.spinner > div {
				width: 70px;
				height: 70px;
				display: inline-block;
				background-position: 0 0;
				background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJEAAACRCAMAAAD0BqoRAAAAElBMVEUAAABWmmBde4mpSUn3mgf///+xhIPXAAAAAXRSTlMAQObYZgAAAKhJREFUeAHt2bUBA0EMRcHF/ks2FLCgM/vmhYom1k/5KaV75SkRERERbUTUww1FPdxBERERERFRjjYWlWhfICIiIiIi6tO+RkRERERENIiIiGgcEdGf/I+IiIiIiOy0RERE3y4i+s+k9pTSvfqUiIg2IiIi6uGGoh7uoIiIiIiIKHwbi2qgPxERERERERERERERERERERERhSMaRURERPSLOy0RERER0RXrRvhxSVnxawAAAABJRU5ErkJggg==);
				vertical-align: middle;
				margin-right: 5px;
				animation: bounceUp 3s ease-in-out infinite;
				box-shadow: 0 0 5px rgba(0,0,0,.3) inset;
			}
			.spinner > div:nth-child(2) {
				background-position: 70px 0;
				animation-delay: .6s;
			}
			.spinner > div:nth-child(3) {
				background-position: 0 70px;
				animation-delay: 1.2s;
			}
			.spinner > div:nth-child(4) {
				background-position: 70px 70px;
				margin-right: 0;
				animation-delay: 1.8s;
			}
		",
		'spinner'	=> '<div></div><div></div><div></div><div></div>'
	],
	[
		'name'		=> 'Nasa',
		'style'		=> "
			body{margin:0}
			@keyframes loader{from,to{transform:scale(1)}50%{transform:scale(1.1)}}
			.spinner {
				position: fixed;
				line-height: 100vh;
				z-index: 999;
				transition: all 200ms;
				pointer-events: none;
				width: 100%;
				text-align: center;
			}
			.spinner::after {
				content: '';
				display: inline-block;
				height: 100%;
				vertical-align: middle;
				margin-left: -0.25em;
			}
			.spinner > img {
				display: inline-block;
				animation: loader 1s infinite;
				vertical-align: middle;
			}
			body::after {
				content: '';
				position: fixed;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				background: #E4EFFB;
				z-index: 90;
				transition: all 200ms;
				pointer-events: none;
			}
		",
		'spinner'	=> '<img src="data:image/png;base64,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">'
	]
	/**/
];
$loaderScreen = $loaderScreens[mt_rand(0, count($loaderScreens) - 1)];

if (
	$isIteratve =
		isset($this->iterative) &&
		$this->iterative
)
	$loaderScreen['style'] .= "
		body {
			text-align: center;
		}
		
		[data-status]::before {
			content: attr(data-status);
			position: absolute;
			color: #222;
			left: 0;
			right: 0;
			top: calc(100% - 120px);
			text-align: center;
			font-size: 20px;
			letter-spacing: .5px;
		}
		.progress {
			position: absolute;
			top: calc(100% - 75px);
			left: calc(50vw - 300px/2);
			display: inline-block;
			padding: 10px;
			margin: auto;
			width: 300px;
			background-color: #E9E9D8;
			border: solid 1px rgba(0,0,0,.2);
			box-sizing: border-box;
			z-index: 100;
		}
		b {
			position: absolute;
			left: -1px;
			top: -1px;
			bottom: -1px;
			background-color: hsl(128.8, 28.3%,67.1%);
			border: solid 1px rgba(0,0,0,.1);
		}
		
		[data-total]::after {
			content: ' / 'attr(data-total);
			opacity: .4;
		}
		
		span {
			position: relative;
		}
	";

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="0; url=<?= $this->redirectDestination; ?>">
<title><?= $this->incognitoMode ? INCOGNITO_SITE_TITLE : $this->SiteName; ?></title>
<link rel="shortcut icon" href="<?php echo $this->SiteFaviconPath ?>" type="image/x-icon">
<style><?php echo $loaderScreen['style']; ?></style>
</head>
<body<?= $isIteratve ? ' data-status="' . $this->status . '"' : false ?>><?php 
	if ($isIteratve)
		echo '<div' . ($this->total ? ' data-total="' . $this->total . '"' : false) . ' class="progress"><b style="width:' . $this->percentage . '%"></b><span>' . $this->objectsString . '</span></div>';

if ($this->AccessPrefix !== INCOGNITO_ACCESS_PREFIX){
?><div class="spinner"><?php echo $loaderScreen['spinner']; ?></div><?php } ?></body>
</html>
