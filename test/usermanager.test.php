<?php

require_once('../UserManager/XMLUserManager.class.php');

session_start();
$_user = new XMLUserManager('BasicUserManager.xml');

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

// TEST LOADUSER
/*
$_user->login('test','test');
echo nl2br(print_r($_user->user,true));
$_user->loadCurUser();
echo nl2br(print_r($_user->user,true));
$_user->logout();
echo nl2br(print_r($_user->user,true));
*/

// TEST GETUSER
/*
$user = $_user->getUser(1);
echo nl2br(print_r($user,true));
*/

// TEST LOOKUPATTRIBUTE
/*
$attr = 'username';
$valu = 'user';
$users = $_user->lookupAttribute($attr,$valu,false);
echo nl2br(print_r($users,true));

$users = $_user->lookupAttribute($attr,$valu,true);
echo nl2br(print_r($users,true));
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
$user = $_user->getUser(5);
echo nl2br(print_r($user,true));
$_user->modifyUser(array('id' => 5, 'username' => 'tested'));
$user = $_user->getUser(5);
echo nl2br(print_r($user,true));
$_user->modifyUser(array('id' => 5, 'username' => 'test', 'password' => 'test'));
$user = $_user->getUser(5);
echo nl2br(print_r($user,true));
*/

// TEST DELETEUSER
/*
$_user->addUser('name','pass');
$user = $_user->lookupAttribute('username','name');
echo 'trying to remove '.$user[0]['id'];
$_user->deleteUser($user[0]['id']);
*/

// TEST GETALLUSERS
/*
$users = $_user->getAllUsers();
echo nl2br(print_r($users,true));
*/

// TEST MANAGEUSERS
///*
$_user->manageUsers(true);
//*/




?>