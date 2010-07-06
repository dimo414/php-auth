<?php

require_once('../../mysql.class.php');
require_once('../UserManager/XMLUserManager.class.php');
require_once('../UserManager/MySQLUserManager.class.php');

session_start();

if(isset($_GET['type']) && $_GET['type'] == 'xml')
	$_user = new XMLUserManager('BasicUserManager.xml');
else{
	$db = new my_mysqli('localhost','digigem','digigem','users');
	$_user = new MySQLUserManager($db,'users');
	// TEST CREATETABLESTRING
	// $_user->createTable();
}

// TEST HASH
/*
$hash =  $_user->hash('hello');
$rehash = $_user->hash('hello',$hash);

if($hash == $rehash){
	echo 'HASH WORKS<br />';
} else {
	echo 'Hash didn\'t match:<br />
'.$hash.'<br />
'.$rehash.'<br />';
	exit;
}
*/

// TEST LOOKUPATTRIBUTE
/*
$attr = 'username';
$valu = 'test';
$users = $_user->lookupAttribute($attr,$valu,false);
echo nl2br(print_r($users,true));

$users = $_user->lookupAttribute($attr,$valu,true);
echo nl2br(print_r($users,true));
*/

// TEST GETUSER
/*
$user = $_user->getUser(12);
echo nl2br(print_r($user,true));
*/

// TEST LOADUSER
/*
//$_user->addUser('test','test');
$_user->login('test','test');
echo nl2br(print_r($_user->user,true));
$_user->loadCurUser();
echo nl2br(print_r($_user->user,true));
$_user->logout();
echo nl2br(print_r($_user->user,true));
*/

// TEST ADDUSER
/*
$time = time();
$_user->addUser('test_'.$time,$time,UserManager::USER);
*/

// TEST CHANGEPASSWORD
/*
$_user->login('test','test');
echo $_user->user['username'].'<br />';
echo ($_user->changePassword('test','green') ? 'Changed' : 'Failed').'<br />';;
$_user->logout();
echo $_user->user['username'].'<br />';
$_user->login('test','green');
echo $_user->user['username'].'<br />';
echo ($_user->changePassword('green','test') ? 'Changed' : 'Failed').'<br />';;
$_user->logout();
echo $_user->user['username'].'<br />';
*/

// TEST MODIFYUSER
/*
$user = $_user->getUser(15);
echo nl2br(print_r($user,true));
$_user->modifyUser(array('id' => 15, 'username' => 'tested'));
$user = $_user->getUser(15);
echo nl2br(print_r($user,true));
$_user->modifyUser(array('id' => 15, 'username' => 'test', 'password' => 'test'));
$user = $_user->getUser(15);
echo nl2br(print_r($user,true));
*/

// TEST DELETEUSER
/*
$_user->addUser('name','pass');
$user = $_user->lookupAttribute('username','name',true);
echo 'trying to remove '.$user[0];
$_user->deleteUser($user[0]);
*/

// TEST GETALLUSERS
/*
$users = $_user->getAllUsers();
echo nl2br(print_r($users,true));
*/

// TEST MANAGEUSERS
/*
$_user->manageUsers(true);
*/




?>