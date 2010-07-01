<?php

require_once('../UserManager/UserManager.class.php');

session_start();
$_user = new UserManager('BasicUserManager.xml');

$user = 'test';
$pass = 'test';
$level = UserManager::USER;

$_user->addUser($user,$pass,$level,array());
$_user->login($user,$pass);

$login_id = $_user->getCurUserID();
if($login_id <= 0){
	echo '<p>ERROR: Unexpected user ID</p>';
	exit;
}

$login_user = $_user->getCurUsername();
if($user != $login_user){
	echo '<p>ERROR: Unexpected username</p>';
	exit;
}

$login_level = $_user->getCurUserLevel();
if($level != $login_level){
	echo '<p>ERROR: Unexpected level</p>';
	exit;
}

$login_user_obj = $_user->getUser($login_id);
// What to test?

$_user->logout();

if($_user->hasPerm($level)){
	echo '<p>ERROR: Should have no permissions</p>';
	exit;
}

$_user->loginSleep($user,$pass);

if(!$_user->hasPerm($level)){
	echo '<p>ERROR: Should have permission</p>';
	exit;
}

$_user->changePassword($pass,$pass.$pass);

$_user->deleteUser($login_id);

$_user->require_login();

$_user->manageUsers();

?>