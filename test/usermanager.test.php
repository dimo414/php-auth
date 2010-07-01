<?php

require_once('../UserManager/UserManager.class.php');

session_start();
$_user = new UserManager('BasicUserManager.xml');
$_user->require_login();







?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head><title>Good</title></head>
	<body>
		<h1>All Clear</h1>
		<p>If you were prompted for a password and then saw this page, you're all set.</p>
		<p><a href="/clearSession.php">Clear Session</a></p>
	</body>
</html>