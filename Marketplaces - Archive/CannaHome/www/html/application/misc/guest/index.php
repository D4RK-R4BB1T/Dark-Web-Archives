<?php

	session_start();

	require_once('../../config/config.php');
	
	$invalid = false;
	
	function getDomain() {
		$exploded = explode('.', $_SERVER['HTTP_HOST']);
		if (count($exploded) > 2)
			return $exploded[1] . '.' . $exploded[2];
		else
			return $exploded[0] . '.' . $exploded[1];
	}
	
	if( $cr = getDomain() == substr(SECONDARY_URL, 7, -1) ){
		$domain = substr(SECONDARY_URL, 7, -1);
		setcookie('ALT_SITE', 'CR', time() + 60*60*12, '/', '.' . $domain );
	} else {
		$domain = substr(PRIMARY_URL, 7, -1);
	}
	
	if( !empty($_POST) && isset($_POST['captcha']) ){
		
		require_once('../../../library/securimage/securimage.php');
		
		$captcha = new Securimage();
		
		if ($captcha->check($_POST['captcha']) == true){
			
			setcookie('GUEST_ADMITTANCE_TOKEN', md5(GUEST_ADMITTANCE_SALT . date('Y-M-d') . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), time() + 60*60*12, '/', '.' . $domain );
			
			setcookie('GUEST_ADMITTANCE_TOKEN_2', md5(GUEST_ADMITTANCE_SALT . date('Y-M-d', strtotime('+1 DAY')) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), time() + 60*60*12, '/', '.' . $domain );
			
			header('Location: '.$_SERVER['REQUEST_URI']);
			
		} else {
			
			$invalid = true;
			
		}
		
	}
	
	$first = empty($_COOKIE['visitor']);
	setcookie("visitor", 1, time()+157680000);
	
	function getCurrentPath(){
		
		$request = parse_url($_SERVER['REQUEST_URI']);
		$path = $request["path"];
		
		$result = trim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $path), '/');
		
		$result = explode('/', $result);
		$max_level = 6;
		if ($max_level < count($result)) {
			unset($result[0]);
		}
		$result = '/'.implode('/', $result);
		
		return substr($result . '/', 1);
		
	}
	
	$currentPath = getCurrentPath();
	
	function getPrefix() {
		$exploded = explode('.', $_SERVER['HTTP_HOST']);
		if (count($exploded) > 2)
			return $exploded[0];
		else
			return false;
	}
	

?>
<!doctype html>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<title>Almost there...</title>
<link rel="shortcut icon" type="image/x-icon" href="/public/img/favicon.ico">
<style>
.narrow section.container{position:fixed;top:50%;left:50%;margin-top:-60px;margin-left:-175px;padding:30px 20px;width:350px;background:#FFF;}.header{position:fixed;top:50%;left:50%;margin-top:-240px;margin-left:-150px;width:300px;}
<?php if($first){ echo 'body{opacity:0; -webkit-animation:fadeIn 500ms 1000ms forwards;-moz-animation:fadeIn 500ms 1000ms forwards;-ms-animation:fadeIn 500ms 1000ms forwards;-o-animation:fadeIn 500ms 1000ms forwards;animation:fadeIn 500ms 1000ms forwards;}.container{opacity: 0;-webkit-animation:fadeIn 300ms 2000ms forwards;-moz-animation:fadeIn 300ms 2000ms forwards;-ms-animation:fadeIn 300ms 2000ms forwards;-o-animation:fadeIn 300ms 2000ms forwards;animation:fadeIn 300ms 2000ms forwards;}'; } ?>
</style>
<link rel="stylesheet" type="text/css" href="/public/css/<?php echo $cr ? 'green' : 'nexus' ?>.css">
</head>
<body class="nexus narrow">
	<a class="btn corner" href="<?php echo '/login/' . ( (!empty($currentPath) && $currentPath !== '/' ) ? '?return=' . $currentPath : false ) ?>">Login</a>
	<section class="header">
		<?php if ($cr) { ?>
		<img src="/public/img/cr.png" style="margin-top:30px">
		<?php } else { ?>
		<svg width="70px" height="100px" viewBox="0 0 957 1542" version="1.1" xmlns="http://www.w3.org/2000/svg">
		<g id="#000000ff">
		<path opacity="1.00" d=" M 729.26 0.00 L 734.67 0.00 C 742.79 1.82 741.83 10.63 742.05 17.04 C 742.28 43.66 754.98 70.40 745.55 96.67 C 742.89 103.75 752.15 101.69 756.12 100.87 C 774.70 96.66 795.42 94.02 812.68 104.10 C 819.87 73.53 842.78 51.02 856.77 23.82 C 861.00 17.11 870.76 19.95 872.88 26.95 C 881.82 49.06 879.58 74.30 873.32 96.85 C 869.10 113.28 857.27 128.50 860.60 146.21 C 864.22 165.02 879.97 177.43 892.86 190.14 C 921.31 220.75 948.86 255.86 957.00 297.83 L 957.00 312.12 C 953.46 343.04 923.93 364.23 894.00 364.54 C 864.56 366.00 834.82 364.57 806.04 357.86 C 810.97 389.10 811.37 420.86 816.34 452.09 C 826.38 519.69 844.03 586.00 851.82 653.94 C 853.47 674.05 856.45 694.80 851.55 714.67 C 850.31 720.97 844.08 724.79 838.01 722.24 C 844.21 748.27 849.15 775.13 847.44 801.98 C 847.26 809.53 843.26 820.94 833.54 817.72 C 824.53 851.75 823.51 888.74 817.25 923.86 C 813.61 942.37 805.57 959.57 798.48 976.92 C 794.18 999.16 787.24 1021.01 776.74 1041.13 C 765.94 1062.38 765.38 1086.74 758.42 1109.22 C 750.03 1134.68 748.29 1161.86 739.30 1187.13 C 737.34 1194.29 728.49 1195.28 726.20 1202.08 C 708.92 1235.50 718.52 1274.07 715.10 1309.92 C 712.17 1348.86 699.92 1389.57 715.55 1427.41 C 724.39 1444.78 733.51 1463.86 731.18 1483.88 C 727.95 1506.65 695.19 1512.42 679.97 1498.14 C 659.34 1480.67 656.83 1451.21 657.92 1425.99 C 653.70 1378.87 650.02 1331.49 641.58 1284.92 C 629.51 1259.34 633.82 1231.01 629.56 1203.93 C 623.68 1195.63 617.44 1187.14 615.43 1176.87 C 610.54 1193.27 609.72 1211.34 600.17 1226.02 C 597.15 1231.40 589.78 1232.82 588.39 1239.29 C 575.04 1273.29 582.16 1310.56 578.62 1346.05 C 575.08 1382.76 570.43 1420.67 581.41 1456.61 C 589.93 1474.86 598.22 1494.49 596.76 1515.06 C 596.25 1527.78 587.10 1538.69 574.91 1542.00 L 567.35 1542.00 C 548.24 1539.79 530.58 1527.01 525.69 1507.86 C 520.08 1485.01 520.89 1461.11 517.81 1437.88 C 510.60 1394.47 511.00 1350.06 502.58 1306.89 C 488.66 1280.85 492.64 1250.31 485.54 1222.49 C 471.68 1203.72 465.33 1180.69 461.37 1158.02 C 459.84 1148.05 461.95 1135.83 453.65 1128.34 C 446.36 1121.20 435.38 1122.73 426.27 1124.42 C 409.87 1127.33 393.07 1126.91 376.60 1124.80 C 369.85 1174.26 349.46 1222.57 315.64 1259.62 C 298.97 1265.75 285.36 1282.94 282.73 1301.10 C 278.47 1325.82 281.23 1350.99 280.90 1375.91 C 282.70 1395.84 286.67 1416.43 297.51 1433.57 C 304.54 1446.85 310.53 1460.85 313.51 1475.65 C 317.77 1492.01 300.92 1508.09 285.00 1506.63 C 265.13 1506.81 249.57 1491.06 241.77 1474.08 C 225.62 1441.86 229.16 1404.77 219.43 1370.77 C 210.86 1338.81 195.97 1308.33 193.87 1274.85 C 180.50 1282.35 173.23 1297.71 160.25 1306.28 C 135.34 1323.28 141.40 1357.07 140.79 1382.99 C 139.61 1413.48 144.28 1445.09 160.56 1471.37 C 167.29 1486.08 175.20 1501.59 174.38 1518.14 C 168.03 1536.66 142.32 1540.87 126.10 1533.73 C 100.55 1520.77 91.73 1489.49 89.63 1462.94 C 86.47 1427.75 78.85 1393.07 66.14 1360.08 C 55.16 1328.56 48.67 1293.52 58.85 1260.92 C 56.84 1244.98 65.07 1230.76 69.11 1215.89 C 75.61 1186.04 72.84 1154.90 80.42 1125.24 C 87.66 1091.16 85.21 1055.64 76.39 1022.11 C 67.89 986.93 53.22 953.49 45.93 918.03 C 31.13 879.52 28.74 837.75 27.88 796.92 C 26.31 765.12 32.71 732.63 50.21 705.68 C 37.17 714.16 26.72 726.35 12.88 733.65 C 5.59 734.21 1.63 726.30 0.00 720.24 L 0.00 708.81 C 9.93 649.75 60.18 604.18 116.25 588.41 C 231.41 551.83 353.31 535.77 473.98 543.57 C 490.37 544.32 507.20 545.90 523.23 541.42 C 540.33 536.52 554.53 525.18 567.79 513.77 C 589.80 495.45 608.77 471.63 614.19 442.85 C 621.07 406.65 625.27 370.01 631.57 333.70 C 638.54 261.92 665.82 194.24 683.55 125.00 C 674.39 78.39 696.67 32.11 729.26 0.00 Z" />
		</g>
		</svg>
	    <h1><span>Alpaca</span>Marketplace</h1>
		<?php } ?>
	</section>
	<section class="container">
		<form method="post" class="rows-15">
				<div class="row captcha" style="background-image: url(<?php echo '/public/img/captcha.php?' . time() . ($cr ? '&cr' : false); ?>);"></div>
				<label class="row text<?php echo $invalid ? ' invalid' : false ?>">
					<input type="text" class="big" name="captcha" placeholder="Type characters from the image" required autofocus>
				</label>
				<label class="row">
					<input class="btn wide big color" type="submit" value="Enter">
				</label>
		</form>
    </section>
</body>
</html>