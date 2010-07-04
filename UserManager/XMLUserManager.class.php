<?php

require_once('UserManager.class.php');

class XMLUserManager extends UserManager
{
  var $file;
  var $simple;
  var $dom;
  
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
    if(!file_exists($file)){
    	$success = file_put_contents($file,
'<?xml version="1.0" encoding="iso-8859-1"?>
<users>
  <nextid>1</nextid>
</users>'
);
			if(!$success){
				$caller = next(debug_backtrace());
    		trigger_error('Failed to create XML database file \''.$file.'\' in <strong>'.$caller['function'].'</strong> called from <strong>'.$caller['file'].'</strong> on line <strong>'.$caller['line'].'</strong>'."\n<br />error handler", E_ERROR);
			}
    }
    $this->simple = simplexml_load_file($file);
  }
  
  public function loadCurUser(){
  	
  }
  
  public function getUser($id){
  	
  }
  
  public function lookupAttribute($attribute, $value, $justID=false){
  	
  }
  
  public function addUser($username, $password, $level, $array, $autocommit = true){
  	
  }
  
  public function changePassword($origPass, $newPass, $autocommit = true){
  	
  }
  
  public function modifyUser($id, $username, $password, $level, $array, $autocommit = true){
  	
  }
  
  public function deleteUser($id, $autocommit = true){
  	
  }
  
  public function commitChanges(){
  	
  }
  
  public function getAllUsers(){
  	
  }
  
  public function updateUsers(){
  	
  }
}

?>