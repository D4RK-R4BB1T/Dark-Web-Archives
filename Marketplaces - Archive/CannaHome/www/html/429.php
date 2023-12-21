<?php

$session_name = 'lemon';   // Set a custom session name 
$secure = TRUE;

// This stops JavaScript being able to access the session id.
$httponly = true;

// Gets current cookies params.
$cookieParams = session_get_cookie_params();
session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);

// Sets the session name to the one set above.
session_name($session_name);

session_start();   

$_SESSION = array();
	
// get session parameters 
$params = session_get_cookie_params();

// Delete the actual cookie. 
setcookie(session_name(),'', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);

// Destroy session 
session_destroy();
?><!DOCTYPE html>
<html lang="en-US"><head>
<meta charset="UTF-8">
<title>429</title>
</head>
<body>
<h2>429. Too many requests.</h2>
</section>
</body></html>