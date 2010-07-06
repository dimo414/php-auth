<?php

require_once('UserManager.class.php');

class MySQLUserManager extends UserManager
{
	var $db;
	var $table;
	private $add;
	private $modify;
	private $delete;
	
	function __construct($db,$table)
  {
  	parent::__construct();
  	
  	$this->db = $db;
  	$this->table = $table;
  }
  
  public function createTableString(){
  	$query = 'CREATE TABLE `'.$this->table.'` (
`id` INT( 8 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 255 ) NOT NULL ,
`password` CHAR( 61 ) NOT NULL ,
`level` TINYINT( 2 ) UNSIGNED NOT NULL ,
';
foreach($this->userFields as $field)
    {
      $query .= '`'.$field.'` VARCHAR( 255 ) NOT NULL ,
';
    }
	return $query . 'UNIQUE (
`username`
)
) ENGINE = MYISAM';
  }
  
  public function createTable(){
  	$this->db->query($this->createTableString());
  }
  
  public function getUser($id){
  	$query = 'SELECT * FROM `'.$this->table.'` WHERE `id` = \'%s\'';
  	$result = $this->db->query(sprintf($query,$this->db->real_escape_string($id)));
  	if($result->num_rows == 0)
  		return false;
  	
  	return $result->fetch_assoc();
  }
  
  public function lookupAttribute($attribute, $value, $justID=false){
  	$query = 'SELECT %s FROM `'.$this->table.'` WHERE `%s` = \'%s\';';
  	$result = $this->db->query(sprintf($query,($justID ? '`id`' : '*'),$this->db->real_escape_string($attribute),$this->db->real_escape_string($value)));
  	$ret = array();
  	while($row = $result->fetch_assoc()){
  		$ret[] = ($justID ? $row['id'] : $row);
  	}
  	return $ret;
  }
  
  public function addUser($username, $password, $level = UserManager::USER, $array = array(), $autocommit = true){
  	$result = $this->db->query('SELECT * FROM `'.$this->table.'` WHERE `username` = \''.$this->db->real_escape_string($username).'\';');
  	if($result->num_rows > 0)
  		return false;
  		
    $newuser = array();
    $newuser['username'] = $username;
    $newuser['password'] = $this->hash($password);
    $newuser['level'] = $level;
    foreach($this->userFields as $field)
    {
      $newuser[$field] = (isset($array[$field]) ? $array[$field] : '');
    }
    $this->add[] = $newuser;
    
    if($autocommit)
      $this->commitChanges();
    return true;
  }
  
  public function changePassword($origPass, $newPass, $autocommit = true){
  	if($this->user['level'] == UserManager::GUEST)
  		return false; // cannot change password without being logged in
  		
  	$this->loadCurUser();
  	$query_stub = 'UPDATE `'.$this->table.'` SET `password` =  \'%s\' WHERE `id` = %s AND `password` = \'%s\' LIMIT 1;';
  	$query = sprintf($query_stub, $this->hash($newPass), $this->user['id'], $this->hash($origPass,$this->user['password']));
  	$this->db->query($query);
  	
  	if($this->db->affected_rows == 0)
  		return false;
  		
    if($autocommit)
    	$this->commitChanges();
    return true;
  }
  
  public function modifyUser($arr, $autocommit = true){
  	if(!isset($arr['id'])){
    	$this->error('Must include \'id\' attribute when modifying user');
    	return;
    }
    if(isset($arr['password'])){
    	$arr['password'] = $this->hash($arr['password']);
    }
    // this removes any non-valid entries from the array before we put it in a query
    $array = array();
    foreach($arr as $field => $value)
    {
    	if(isset($this->userField[$field]) || in_array($field,array('username','password','level')))
	      $array[$field] = $arr[$field];
    }
    
    $query_stub = 'UPDATE `'.$this->table.'` SET %s WHERE `id` = %s LIMIT 1;';
    $set_str = '';
    foreach($array as $field => $value){
    	$set_str .= sprintf("`%s` = '%s', ",$this->db->real_escape_string($field),$this->db->real_escape_string($value));
    }
    $query = sprintf($query_stub,substr($set_str,0,-2),(int)$arr['id']);
    $this->db->query($query);
    
    if($this->db->affected_rows == 0)
  		return false;
    
    if($autocommit)
      $this->commitChanges();
    return true;
  }
  
  public function deleteUser($id, $autocommit = true){
  	$query = 'DELETE FROM `'.$this->table.'` WHERE `id` = %s LIMIT 1;';
  	$this->db->query(sprintf($query,$this->db->real_escape_string($id)));
  	
  	if($this->db->affected_rows == 0)
  		return false;
    
    if($autocommit)
      $this->commitChanges();
    return true;
  }
  
  public function commitChanges(){
  	if(count($this->add) > 0){
	  	$add_query = 'INSERT INTO `'.$this->table.'` (`username`, `password`, `level`%s) VALUES %s;';
	  	
	  	$extra = '';
	  	foreach($this->userFields as $field)
	    {
	      $extra .= ', `'.$field.'`';
	    }
			
			$values = '';
	  	foreach($this->add as $add){
	  		$value = '(';
	  		foreach($add as $val){
	  			$value .= "'".$this->db->real_escape_string($val)."', ";
	  		}
	  		$values .= substr($value,0,-2).'), ';
	  	}
	  	$values = substr($values,0,-2);
	  	
	  	$add_query = sprintf($add_query,$extra,$values);
	  	
	  	$this->db->query($add_query);
	  }
  }
  
  public function getAllUsers(){
  	$query = 'SELECT * FROM `'.$this->table.'`;';
  	$result = $this->db->query($query);
  	$res = array();
  	
  	while($row = $result->fetch_assoc()){
  		$res[] = $row;
  	}
  	return $res;
  }
  
  public function updateUsers(){
  	
  }
}

?>