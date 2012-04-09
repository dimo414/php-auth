<?php

/*
This tool was developed by Michael Diamond (http://www.DigitalGemstones.com)
and is released freely for personal and corporate use under the licence which can be found at:
http://digitalgemstones.com/licence.php
and can be summarized as:
You are free to use this software for any purpose as long as Digital Gemstones is credited,
and may redistribute the software in its original form or modified as you see fit, 
as long as any credit comments in the code remain unchanged.

VERSION: 1.5.0
*/

// Documentation can be found in the README and in the UserManager class
require_once('UserManager.class.php');

class MySQLUserManager extends UserManager
{
	var $db;
	var $table;
	private $add;
	private $modify;
	private $delete;
	
	// expects a mysqli object connecting the user to the correct database
	// and the table name you would like to use
	function __construct($db,$table = 'users')
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
  
  public function changePassword($origPass, $newPass){
  	if($this->user['level'] == UserManager::GUEST)
  		return false; // cannot change password without being logged in
  		
  	$this->loadCurUser();
  	$query_stub = 'UPDATE `'.$this->table.'` SET `password` =  \'%s\' WHERE `id` = %s AND `password` = \'%s\' LIMIT 1;';
  	$query = sprintf($query_stub, $this->hash($newPass), $this->user['id'], $this->hash($origPass,$this->user['password']));
  	$this->db->query($query);
  	
  	if($this->db->affected_rows == 0)
  		return false;
  		
    return true;
  }
  
  public function addUser($username, $password, $level = UserManager::USER, $array = array(), $autocommit = true){
    if(!$this->validUsername($username))
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
      return $this->commitChanges();
    return true;
  }
  
  public function modifyUser($arr, $autocommit = true){
  	if(!isset($arr['id'])){
    	$this->usage_error('Must include \'id\' attribute when modifying user');
    	return false;
    }
    if(isset($arr['password'])){
    	$arr['password'] = $this->hash($arr['password']);
    }
    if(isset($arr['username'])){
	    if(!$this->validUsername($arr['username']))
	    	return false;
    }
    // this removes any non-valid entries from the array and structures the data
    // in a way easily converted into an SQL query
    $array = array();
    foreach($arr as $field => $value)
    {
    	if(in_array($field,$this->userFields) || in_array($field,array('username','password','level')))
	      $this->modify[$field][(int)$arr['id']] = $arr[$field];
    }
    
    /*
    $query_stub = 'UPDATE `'.$this->table.'` SET %s WHERE `id` = %s LIMIT 1;';
    $set_str = '';
    foreach($array as $field => $value){
    	$set_str .= sprintf("`%s` = '%s', ",$this->db->real_escape_string($field),$this->db->real_escape_string($value));
    }
    $query = sprintf($query_stub,substr($set_str,0,-2),(int)$arr['id']);
    $this->db->query($query);
    
    if($this->db->affected_rows == 0)
  		return false;
  	*/
    
    if($autocommit)
      return $this->commitChanges();
    return true;
  }
  
  public function deleteUser($id, $autocommit = true){
  	$this->delete[] = $id;
    
    if($autocommit)
      return $this->commitChanges();
    return true;
  }
  
  public function commitChanges(){
  	$all_good = true;
  	//
  	// ADD
  	//
  	if(count($this->add) > 0){
  		// check for username collisions
  		$unique_stub = "SELECT `username` from `".$this->table."` WHERE `username` IN ('%s');";
  		$users = array();
  		foreach($this->add as $add){
  			$users[] = $this->db->real_escape_string($add['username']);
  		}
  		$result = $this->db->query(sprintf($unique_stub,implode("','",$users)));
  		
  		if($result->num_rows == 0){ // no conflicts
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
		  	
		  	if($this->db->affected_rows != count($this->add)){
		  		$all_good = false;
		  	}
		  	$this->add = array();
		  } else { // collision detected
		  	$all_good = false;
		  	$names = array();
		  	while($row = $result->fetch_assoc()){
		  		$names[] = $row['username'];
		  	}
		  	$this->manage_error('Failed to add users, username collisions on: '.implode(', ',$names));
		  }
	  }
	  //
	  // MODIFY
	  //
	  if(count($this->modify) > 0){
	  	// check for username collisions
	  	$unique_stub = "SELECT `username` FROM `".$this->table."` WHERE %s;";
	  	$id_uname_stub = "(`id` != %s AND `username` = '%s')";
	  	$id_uname = array();
	  	foreach($this->modify['username'] as $id => $name){
	  		$id_uname[] = sprintf($id_uname_stub,$id,$this->db->real_escape_string($name));
	  	}
	  	$result = $this->db->query(sprintf($unique_stub,implode(' OR ',$id_uname)));
	  	
	  	if($result->num_rows == 0){ // no conflicts
				$query_stub = "UPDATE `".$this->table."` SET \n%s\nWHERE `id` IN (%s);";
				$set_stub = '`%1$s` = CASE `id`
%2$s
ELSE %1$s
END';
				$when_stub = "WHEN %s THEN '%s'";
				
				$sets = array();
				$ids = array();
				foreach($this->modify as $field => $changes){
					$whens = array();
					foreach($changes as $id => $value){
						$ids[$id] = true;
						$whens[] = sprintf($when_stub,$id,$this->db->real_escape_string($value));
					}
					$whens = implode("\n",$whens);
					$sets[] = sprintf($set_stub,$field,$whens);
				}
				$ids = array_keys($ids);
				$query = sprintf($query_stub,implode(",\n",$sets),implode(',',$ids));
				
				$this->db->query($query);
				
				// can't easily determine how many rows were /supposed/ to be changed
		  	$this->modify = array();
		  } else { // there was a collision
		  	$all_good = false;
		  	$names = array();
		  	while($row = $result->fetch_assoc()){
		  		$names[] = $row['username'];
		  	}
		  	$this->manage_error('Failed to modify users, username collisions on: '.implode(', ',$names));
		  }
	  }
	  //
	  // DELETE
	  //
	  if(count($this->delete) > 0){
	  	$query = 'DELETE FROM `'.$this->table.'` WHERE %s;';
	  	$ids = '';
	  	foreach($this->delete as $del){
	  		$ids = sprintf('`id` = %s OR ',(int)$del);
	  	}
	  	
	  	$this->db->query(sprintf($query,substr($ids,0,-4)));
	  	
	  	if($this->db->affected_rows != count($this->delete)){
	  		$all_good = false;
	  		$this->manage_error('Failed to delete one or more records.');
	  	}
	  	$this->delete = array();
	  }
	  
	  if(!$all_good && $this->db->errno){
	  	$this->manage_error($this->db->errno.': '.$this->db->error);
	  }
	  // where possible, return the insert id
	  return $all_good ? ($this->db->insert_id ? $this->db->insert_id : true) : false;
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
}

?>