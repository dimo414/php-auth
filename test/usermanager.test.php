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

if(!isset($_GET['test']))
{
	$tests = array('hash','validate','lookup','getuser','loaduser','adduser','changepass','modifyuser','deleteuser','getall','manageusers','login','logout');
  $_user->header('Select A Test');
  echo '<h2>Select A Test</h2>
  <ul>';
  foreach($tests as $test)
    echo '<li><a href="?test='.$test.'">'.$test.'</a></li>';
  echo '</ul>';
  
  
  $_user->footer();
  exit;
}

// TEST HASH
if($_GET['test'] == 'hash'){
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
}

// TEST VALIDUSER
if($_GET['test'] == 'validate'){
	if(!isset($_GET['user'])){
		echo 'Specify a user GET parameter.';
		exit;
	}
	echo $_user->validUsername($_GET['user']) ? 'VALID' : 'INVALID';
}

// TEST LOOKUPATTRIBUTE
if($_GET['test'] == 'lookup'){
	$_user->addUser('test','test'); // ignore failure if user already exists
	$attr = 'username';
	$valu = 'test';
	$users = $_user->lookupAttribute($attr,$valu,false);
	echo nl2br(print_r($users,true));
	
	$users = $_user->lookupAttribute($attr,$valu,true);
	echo nl2br(print_r($users,true));
}

// TEST GETUSER
if($_GET['test'] == 'getuser'){
	$res = $_user->lookupAttribute('username','test',true);
	$user = $_user->getUser($res[0]);
	echo nl2br(print_r($user,true));
}

// TEST LOADUSER
if($_GET['test'] == 'loaduser'){
	$_user->login('test','test');
	echo nl2br(print_r($_user->user,true));
	$_user->loadCurUser();
	echo nl2br(print_r($_user->user,true));
	$_user->logout();
	echo nl2br(print_r($_user->user,true));
}

// TEST ADDUSER
if($_GET['test'] == 'adduser'){
	$time = time();
	$res = $_user->addUser('test_'.$time,$time,UserManager::USER);
	echo $res ? 'Added new user' : 'Failed to add new user';
}

// TEST CHANGEPASSWORD
if($_GET['test'] == 'changepass'){
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
}

// TEST MODIFYUSER
if($_GET['test'] == 'modifyuser'){
	$res = $_user->lookupAttribute('username','test');
	$user = $res[0];
	echo nl2br(print_r($user,true));
	$_user->modifyUser(array('id' => $user['id'], 'username' => 'tested'));
	$user = $_user->getUser($user['id']);
	echo nl2br(print_r($user,true));
	$_user->modifyUser(array('id' => $user['id'], 'username' => 'test', 'password' => 'test'));
	$user = $_user->getUser($user['id']);
	echo nl2br(print_r($user,true));
}

// TEST DELETEUSER
if($_GET['test'] == 'deleteuser'){
	$_user->addUser('name','pass');
	$user = $_user->lookupAttribute('username','name',true);
	echo 'trying to remove '.$user[0].'<br />';
	$res = $_user->deleteUser($user[0]);
	echo $res ? 'Removed user' : 'Failed to remove user';
}

// TEST GETALLUSERS
if($_GET['test'] == 'getall'){
	$users = $_user->getAllUsers();
	echo nl2br(print_r($users,true));
}

// TEST MANAGEUSERS
if($_GET['test'] == 'manageusers'){
	$_user->manageUsers(true);
}

// TEST REQUIRELOGIN
if($_GET['test'] == 'login'){
	$_user->require_login(UserManager::ADMIN);
	echo 'Successful login.';
}

// TEST LOGOUT
if($_GET['test'] == 'logout'){
	$_user->logout();
	header('location: '.$_SERVER['SCRIPT_NAME']);
}

?>