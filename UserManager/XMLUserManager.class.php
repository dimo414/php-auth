<?php

require_once('UserManager.class.php');

class XMLUserManager extends UserManager
{
  var $file;
  var $simple;
  
  /*
  $file: path to xml file
  ensure xml file is not publicly accessible
  
  Suggest extending this function in order to
  add additional permission levels and user fields.
  */
  function __construct($file)
  {
  	parent::__construct();
    $this->file = $file;
    $success = is_readable($file);
    if(!file_exists($file)){
    	$success = file_put_contents($file,
'<?xml version="1.0" encoding="iso-8859-1"?>
<users>
  <nextid>1</nextid>
</users>'
);
  	}
		if(!$success || is_dir($file)){
			$this->usage_error('Failed to access/create XML database file \''.$file.'\'');
			return;
		}
		
    $this->simple = simplexml_load_file($file);
  }
  
  public function getUser($id){
  	$users = $this->simple->xpath('user[@id='.htmlentities($id).']');
  	if(count($users) == 0)
  		return false;
    $user = $users[0];
    $array = array('id' => $id,
    							 'username' => (string)$user->username,
                	 'password' => (string)$user->password,
                	 'level'    => (string)$user->level);
    foreach($this->userFields as $field)
    {
      $array[$field] = isset($user->$field) ? (string)$user->$field : '';
    }
    return $array;
  }
  
  public function lookupAttribute($attribute, $value, $justID=false){
  	$array = array();
    foreach($this->simple->user as $user)
    {
      if($user->$attribute == $value)
      {
      	if($justID){
        	$array[] = (string)$user['id'];
        } else {
        	$array[] = array('id' => (string)$user['id'],
    							 'username' => (string)$user->username,
                	 'password' => (string)$user->password,
                	 'level'    => (string)$user->level);
			    foreach($this->userFields as $field)
			    {
			      $array[$field] = isset($user->$field) ? (string)$user->$field : '';
			    }
        }
      }
    }
    return $array;
  }
  
  public function addUser($username, $password, $level = UserManager::USER, $array = array(), $autocommit = true){
  	$usernameMatches = $this->lookupAttribute('username', $username,true);
    if(count($usernameMatches) > 0)
    	return false;
    $nextId = (int)$this->simple->nextid[0];
    $this->simple->nextid[0] = $nextId + 1;
    
    $newuser = $this->simple->addChild('user');
    $newuser->addAttribute('id',$nextId);
    $newuser->addChild('username',$username);
    $newuser->addChild('password',$this->hash($password));
    $newuser->addChild('level',$level);
    foreach($this->userFields as $field)
    {
      $newuser->addChild($field, isset($array[$field]) ? $array[$field] : '');
    }
    
    if($autocommit)
      $this->commitChanges();
    return true;
  }
  
  public function changePassword($origPass, $newPass){
  	if($this->user['level'] == UserManager::GUEST)
  		return false; // cannot change password without being logged in

    $users = $this->simple->xpath('user[@id='.htmlentities($this->user['id']).']');
  	if(count($users) == 0)
  		return false;
    $user = $users[0];
    
    if ((string)$user->password == $this->hash($origPass, (string)$user->password))
    {
      $user->password = $this->hash($newPass);
      
      $this->commitChanges();
      return true;
    }
    return false;
  }
  
  public function modifyUser($arr, $autocommit = true){
    if(!isset($arr['id'])){
    	$this->usage_error('Must include \'id\' attribute when modifying user');
    	return false;
    }
    $users = $this->simple->xpath('user[@id='.htmlentities($arr['id']).']');
  	if(count($users) == 0)
  		return false;
    $user = $users[0];
    
    if(isset($arr['username'])){
    	$usernameMatches = $this->lookupAttribute('username', $arr['username'],true);
    	$count = count($usernameMatches);
	    if($count > 1 || ($count == 1 && $usernameMatches[0] != $arr['id']))
	    	return false;
    	$user->username = $arr['username'];
    }
    if(isset($arr['password'])){
    	$user->password = $this->hash($arr['password']);
    }
    if(isset($arr['level'])){
    	$user->level = (int)$arr['level'];
    }
    foreach($this->userFields as $field)
    {
    	if(isset($arr[$field]))
	      $user->$field = $arr[$field];
    }
    
    if($autocommit)
      $this->commitChanges();
  }
  
  public function deleteUser($id, $autocommit = true){
    $users = $this->simple->xpath('user[@id='.htmlentities($id).']');
  	if(count($users) == 0)
  		return false;
    $user = $users[0];
    
		$oNode = dom_import_simplexml($user);
		$oNode->parentNode->removeChild($oNode);

    
    if($autocommit)
      $this->commitChanges();
  }
  
  public function getAllUsers(){
  	$ret = array();
  	foreach($this->simple->user as $user){
	    $array = array('id' => (string)$user['id'],
	    							 'username' => (string)$user->username,
	                	 'password' => (string)$user->password,
	                	 'level'    => (string)$user->level);
	    foreach($this->userFields as $field)
	    {
	      $array[$field] = isset($user->$field) ? (string)$user->$field : '';
	    }
	    $ret[] = $array;
	  }
	  return $ret;
  }
  
  public function commitChanges(){
  	$this->simple->asXML($this->file);
  }
}

?>