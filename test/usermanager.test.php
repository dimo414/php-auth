<?php

require_once('../UserManager/XMLUserManager.class.php');
require_once('../UserManager/MySQLUserManager.class.php');

session_start();

if(isset($_GET['type'])){
	$_SESSION['um_type'] = $_GET['type'];
}

if(isset($_SESSION['um_type']) && $_SESSION['um_type'] == 'xml')
	$_user = new XMLUserManager('BasicUserManager.xml');
elseif(isset($_SESSION['um_type']) && $_SESSION['um_type'] == 'mysql'){
	// may need to configure
	$db = new mysqli('localhost','users','users','users');
	$_user = new MySQLUserManager($db,'users');
	// TEST CREATETABLE
	// $_user->createTable();
} else {
	$_user = new XMLUserManager('BasicUserManager.xml');
}

if(!isset($_GET['test']))
{
	$types = array('xml','mysql');
	$tests = array('hash','validate','lookup','getuser','loaduser','adduser','changepass','modifyuser','deleteuser','getall','manageusers','login','logout');
	
  $_user->header('Select A Test for '.get_class($_user));
  echo '<h2>Select A Type</h2>
  <ul>';
  foreach($types as $type){
  	echo '<li><a href="?type='.$type.'">'.strtoupper($type).'</a>'.
  	     ($type == $types[0] ? ' (default)' : '')
  	     .'</li>';
  }
  echo '</ul>';
  
  echo '<h2>Select A Test</h2>
  <ul>';
  foreach($tests as $test)
    echo '<li><a href="?test='.$test.'">'.$test.'</a></li>';
  echo '</ul>';
  
  echo '<p>Specify the user/password combiniation with user and pass URL params.</p>';
  
  echo $_user->user['level'] > 0 ? '<p>Currently logged in as: '.$_user->user['username'].'</p>' : '<p>Not logged in</p>';
  
  $_user->footer();
  exit;
}

$t_user = isset($_GET['user']) ? $_GET['user'] : 'test';
$t_pass = isset($_GET['pass']) ? $_GET['pass'] : 'test';

$_user->header('Testing '.get_class($_user));

echo "<pre>\n";

// TEST HASH
if($_GET['test'] == 'hash'){
	echo "TESTING HASH CODE\n";
	$hash =  $_user->hash('hello');
	$rehash = $_user->hash('hello',$hash);
	
	if($hash == $rehash){
		echo 'HASH WORKS';
	} else {
		echo "Hash didn't match:\n".$hash."\n".$rehash;
	}
}

// TEST VALIDUSER
elseif($_GET['test'] == 'validate'){
	echo "TESTING VALID USERNAME\n";
	echo $_user->validUsername($t_user) ? 'VALID' : 'INVALID';
}

// TEST LOOKUPATTRIBUTE
elseif($_GET['test'] == 'lookup'){
	echo "TESTING LOOKUP ATTRIBUTE\n";
	$attr = 'username';
	$valu = $t_user;
	echo "Retrieved users:\n";
	$users = $_user->lookupAttribute($attr,$valu,false);
	print_r($users);
	
	echo "Retrieved IDs:\n";
	$users = $_user->lookupAttribute($attr,$valu,true);
	print_r($users);
}

// TEST GETUSER
elseif($_GET['test'] == 'getuser'){
	echo "TESTING GET USER\n";
	$res = $_user->lookupAttribute('username',$t_user,true);
	if(empty($res)){
		echo $t_user.' not found.';
	} else {
		$user = $_user->getUser($res[0]);
		print_r($user);
	}
}

// TEST LOADUSER
elseif($_GET['test'] == 'loaduser'){
	echo "TESTING LOAD USER\n";
	echo "Logging in as $t_user:$t_pass\n";
	$login = $_user->login($t_user,$t_pass);
	if($login){
		print_r($_user->user);
		echo "Loading full user\n";
		$_user->loadCurUser();
		print_r($_user->user);
		echo "Logged out\n";
		$_user->logout();
		print_r($_user->user);
	} else {
		echo 'Login failed.';
	}
}

// TEST ADDUSER
elseif($_GET['test'] == 'adduser'){
	echo "TESTING ADD USER\n";
	$res = $_user->addUser($t_user,$t_pass,UserManager::USER);
	echo $res ? 'Added new user '.$res : 'FAILED to add new user '.$t_user;
}

// TEST CHANGEPASSWORD
elseif($_GET['test'] == 'changepass'){
	echo "TESTING CHANGE PASSWORD\n";
	echo "Logging in as $t_user:$t_pass\n";
	$login = $_user->login($t_user,$t_pass);
	if($login){
		echo "Cur user: ".$_user->user['username']."\n";
		echo "Changing password: ".($_user->changePassword($t_pass,'green') ? 'Succeded' : 'FAILED')."\n";
		echo "Logging in with new password\n";
		$_user->logout();
		echo "Cur user: ".$_user->user['username']."\n";
		$_user->login($t_user,'green');
		echo "Cur user: ".$_user->user['username']."\n";
		echo "Changing back: ".($_user->changePassword('green',$t_pass) ? 'Succeded' : 'FAILED')."\n";
		$_user->logout();
	} else {
		echo 'Login failed.';
	}
}

// TEST MODIFYUSER
elseif($_GET['test'] == 'modifyuser'){
	echo "TESTING MODIFY USER\n";
	$res = $_user->lookupAttribute('username',$t_user);
	if(empty($res)){
		echo $t_user.' does not exist';
	} else {
		$user = $res[0];
		print_r($user);
		echo "Modifying User\n";
		$_user->modifyUser(array('id' => $user['id'], 'username' => 'tested'));
		$user = $_user->getUser($user['id']);
		print_r($user);
		echo "Modifying User and Password\n";
		$_user->modifyUser(array('id' => $user['id'], 'username' => $t_user, 'password' => $t_pass));
		$user = $_user->getUser($user['id']);
		print_r($user);
	}
}

// TEST DELETEUSER
elseif($_GET['test'] == 'deleteuser'){
	echo "TESTING DELETE USER\n";
	$user = $_user->lookupAttribute('username',$t_user,true);
	echo 'trying to remove '.$user[0]."\n";
	$res = $_user->deleteUser($user[0]);
	echo $res ? 'Removed user' : 'FAILED TO REMOVE USER';
	echo "\nLookup:\n";
	print_r($_user->lookupAttribute('username',$t_user,true));
}

// TEST GETALLUSERS
elseif($_GET['test'] == 'getall'){
	echo "TESTING GET ALL USERS\n";
	$users = $_user->getAllUsers();
	print_r($users);
}

// TEST MANAGEUSERS
elseif($_GET['test'] == 'manageusers'){
	echo "TESTING MANAGE USERS<br/></pre>";
	$_user->manageUsers(true);
	echo '<pre>';
}

// TEST REQUIRELOGIN
elseif($_GET['test'] == 'login'){
	echo "TESTING REQUIRE ADMIN LOGIN\n</pre>";
	$_user->require_login(UserManager::ADMIN);
	echo '<pre>Successful login.';
}

// TEST LOGOUT
elseif($_GET['test'] == 'logout'){
	print_r($_user->user);
	echo "Logging out...\n";
	$_user->logout();
	print_r($_user->user);
	echo 'Successful logout.';
}

else {
	echo 'INVALID TEST';
}

echo '</pre><a href="?">Back</a>';

$_user->footer();

?>