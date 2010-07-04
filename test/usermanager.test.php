<?php

require_once('../UserManager/XMLUserManager.class.php');

session_start();
$_user = new XMLUserManager('BasicUserManager.xml');

// TEST HASH
$hash =  $_user->hash('hello');
$rehash = $_user->hash('hello',$hash);

if($hash == $rehash){
	echo 'HASH WORKS<br />';
} else {
	echo "Hash didn't match:<br />
".$hash.'<br />
'.$rehash.'<br />';
	exit;
}

?>