<?php

/*
This tool was developed by Michael Diamond (http://www.DigitalGemstones.com)
and is released freely for personal and corporate use under the licence which can be found at:
http://digitalgemstones.com/licence.php
and can be summarized as:
You are free to use this software for any purpose and may redistribute the software 
in its original form or modified as you see fit, as long as any credit comments
in the code remain unchanged, and you do not release it under a different licence, or
remove the copyright notice in the files.

VERSION: 1.5.0
*/

/*
UserManager class is an abstract PHP user managment implementation, designed to very easily be added
into a website.  As of this writing there are two implementations, XMLUserManager, and MySQLUserManager.

XMLUserManager is ideal to very quickly create a user system on a website.  It requires no database,
and no configuration.
MySQLUserManager is simiarly very quick to set up, but is slightly more complicated, and of course
requires a database.  For websites expecting larger numbers of users however, it is likely to be more robust.
*/
require_once('bitmasker.class.php');

abstract class UserManager
{
  /*
  To add custom user attributes extend $userFields with an array of 
  the names of the attributes.
  To add user permission levels, extend with a new constant, and add
  it to the $levels array with the name of the level as the key, and the
  int value of the key as the value.
  */
  var $userFields = array();
  var $loginFail = 'Incorrect username or password.';
  var $permissionFail = 'You are logged in as %s, but do not have adequate permissions to view this page.';
  var $levels = array('GUEST' => UserManager::GUEST, 'USER' => UserManager::USER, 'SUPERUSER' => UserManager::SUPERUSER, 'ADMIN' => UserManager::ADMIN);
  var $user = array(); // populated in constructor
  var $groups = null;
  
  private $manage_errors = array();
  
  const GUEST = 0;
  const USER = 2;
  const SUPERUSER = 4;
  const ADMIN = 10;
  
  /*
  Abstract class that unifies how XMLUserManager and MySQLUserManager behave
  */
  function __construct()
  {
    if(!isset($_SESSION['usermanager__level']))
    {
      $_SESSION['usermanager__id'] = 0;
      $_SESSION['usermanager__level'] = self::GUEST;
      $_SESSION['usermanager__groups'] = 0;
      $_SESSION['usermanager__username'] = '';
    }
      
		$this->user = array(
			'id' => $_SESSION['usermanager__id'],
			'level' => $_SESSION['usermanager__level'],
			'groups' => $_SESSION['usermanager__groups'],
			'username' => $_SESSION['usermanager__username']
		);
  }
  
  //////////////////
  // Formatting Functions
  //////////////////
  
  /*
  Header code output when require_login() interrupts normal execution
  
  Suggest extending this function in order to
  create a custom set of header code to be output
  when the script needs to generate a valid XHTML page
  */
  function header($title)
  {
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"><head><title>'.$title.'</title></head><body>';
  }
  
  /*
  Footer code output when require_login() interrupts normal execution
  
  Suggest extending this function in order to
  create a custom set of footer code to be output
  when the script needs to generate a valid XHTML page
  */
  function footer()
  {
    echo '</body></html>';
  }
  
  //////////////////
  // Getter Functions
  //////////////////
  
  /*
  Loads extra details about the current user not stored
  in session information.  This is anything other than
  username, level, and id.  The values will be populated
  in $this->user field.
  */
  public function loadCurUser(){
  	// if not logged in
  	if($this->user['level'] == UserManager::GUEST){
  		return false;
  	}
  	// since password is not stored in sessions, its existance
  	// indicates the full user has already been loaded
  	if(!isset($this->user['password'])){
  		$this->user = $this->getUser($this->user['id']);
  	}
  	return true;
  }
  
  /*
  $num: numerical user level
  takes a user level and returns user level title.
  */
  function userLevel($num)
  {
    foreach($this->levels as $name => $value)
    {
      if($value == $num)
        return $name;
    }
    return $num;
  }
  
  /*
  $id: User id
  returns associative array of user's attributes.
  */
  abstract public function getUser($id);
  
  /*
  $attribute: attribute to lookup from the user fields array (or username, password, or level)
  $value: value of the attribute to look for
  searches for users who have their $attribute set to $value and returns an array of all matches.
  */
  abstract public function lookupAttribute($attribute, $value, $justID=false);
  
  //////////////////
  // Processing Functions
  //////////////////
  
  /*
  $username: username to attempt to log in
  $password: password to attempt to log in
  to integrate with your site, pass a username and password entered by a client,
  if correct, will log user in and return true,
  if incorrect, will return false.
  */
  function login($username, $password)
  {
    $users = $this->lookupAttribute('username',$username);
    if(count($users) == 0)
    	return false;
    $user = $users[0];
    if($username == $user['username'] && $user['password'] == $this->hash($password, $user['password']))
    {
      $_SESSION['usermanager__id'] = $user['id'];
      $_SESSION['usermanager__level'] = $user['level'];
      $_SESSION['usermanager__groups'] = $user['groups'];
      $_SESSION['usermanager__username'] = $user['username'];
      
      $this->user = array(
				'id' => $_SESSION['usermanager__id'],
				'level' => $_SESSION['usermanager__level'],
				'groups' => $_SESSION['usermanager__groups'],
				'username' => $_SESSION['usermanager__username']
			);
		
      return true;
    }
    return false;
  }
  
  /*
  call function at beginning of page, if user is logged in
  (and has appropriate permissions) page will load as normal.
  If not, user is prompted to log in, and remainder of page does
  not load until logged in.
  
  $minPermission: minimum permission level to view page
  $inGroup: user must be a member of one or more groups listed
  $allGroups: user must be a member of all groups listed
  
  IMPORTANT: Note that this function uses $_SERVER['REQUEST_METHOD'] == "POST" to test if POST information was submitted.
  in order to prevent any code in your page from thinking /it's/ forms were submitted, $_SERVER['REQUEST_METHOD'] is set to 'GET'
  upon successful login.  If you use a different method to test if a form has been submitted, you should add an additional test to ensure
  it is in fact your form, and not the login form, which was submitted.
  */
  function require_login($minPermission = self::USER, $inGroup = null, $allGroups = null)
  {
    if($this->hasPerm($minPermission,$inGroup,$allGroups))
      return;
      
    $error = false;
    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      if($this->login($_POST['user'], $_POST['pass']) && $this->hasPerm($minPermission,$inGroup,$allGroups))
      {
        $_SERVER['REQUEST_METHOD'] = "GET"; // so as not to throw off any other scripts checking against post method
        return;
      }
      else $error = true;
    }
    if($this->user['level'] > self::GUEST && !$this->hasPerm($minPermission,$inGroup,$allGroups))
    {
      $error = true;
      $notice = $this->permissionFail;
    }
    else $notice = $this->loginFail;
    
    $this->header('Login Required');
    
    echo '  <div class="usermanager_auth">'.
    (($error) ? '    <div class="usermanager_notice">'.sprintf($notice,$this->user['username']).'</div>' : '')
    .'    <form action="" method="post">
      <table>
        <tr>
          <td>Username:</td>
          <td><input type="text" name="user" /></td>
        </tr>
        <tr>
          <td>Password:</td>
          <td><input type="password" name="pass" /></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input type="submit" value="Submit" /></td>
        </tr>
      </table>
    </form>
  </div>';
  
    $this->footer();
    
    exit;
  }
  
  /*
  Logs user out.
  */
  function logout()
  {
    $_SESSION['usermanager__level'] = 0;
    $_SESSION['usermanager__groups'] = 0;
    $_SESSION['usermanager__username'] = '';
    $_SESSION['usermanager__id'] = 0;
    
    $this->user = array(
			'id' => $_SESSION['usermanager__id'],
			'level' => $_SESSION['usermanager__level'],
			'groups' => $_SESSION['usermanager__groups'],
			'username' => $_SESSION['usermanager__username']
		);
  }
  
  /*
  $minPermission: minimum permission level
  $inGroup: user must be member of at least one group
  $allGroups: user must be a member of all groups listed
  returns true if current user has or exceeds
  the minimum permission level passed.
  */
  function hasPerm($minPermission = self::USER, $inGroup = null, $allGroups = null)
  {
    $perm = $this->user['level'] >= $minPermission;
    if($perm && $inGroup != null){
      $perm = $this->groups->union($this->user['groups'],$this->groups->arrayToMask($inGroup)) > 0;
    }
    if($perm && $allGroups != null){
      $allMask = $this->groups->arrayToMask($allGroups);
      $perm = $this->groups->union($this->user['groups'],$allMask) == $allMask;
    }
    return $perm;
  }
  
  /*
  $override: when true, anyone can access user management table
  Outputs table to manage users.  Only accessible
  if logged in as an administrator (or $override is true).
  */
  function manageUsers($override = false)
  {
    if(!$override)
      $this->require_login(self::ADMIN);
   
    $this->updateUsers();
   
    $this->header('Manage Users');
    
    $this->print_errors();
       
    echo '<form action="" method="post">
      <table border="1" cellspacing="0" cellpadding="3">
        <tr>
          <th>Delete?</th>
          <th>Username</th>
          <th>New Password</th>
          <th>Level</th>
          <th>Groups</th>';
   foreach($this->userFields as $field)
    {
      echo '<th>'.$field.'</th>';
    }
    echo '</tr>';

  foreach ($this->getAllUsers() as $user) {
    echo '<tr valign="top">
    <td><input type="checkbox" name="user['.htmlentities($user['id']).'][delete]" value="true" /></td>
    <td><input type="text" name="user['.htmlentities($user['id']).'][username]" value="'.htmlentities($user['username']).'" /></td>
    <td><input type="password" name="user['.htmlentities($user['id']).'][password]" /></td>
    <td><select name="user['.htmlentities($user['id']).'][level]">';
    foreach($this->levels as $name => $value)
    {
      if($value > self::GUEST)
        echo '<option value="'.$value.'" '.(((int)$user['level'] == $value) ? 'selected="selected" ' : '').'>'.$name.'</option>';
    }
    echo '</select></td>';
    echo '<td>TODO</td>';
    foreach($this->userFields as $field)
    {
      echo '<td><input type="text" name="user['.htmlentities($user['id']).']['.$field.']" value="'.htmlentities($user->$field).'" /></td>';
    }
    echo "</tr>\n";
  }
  for ($i = 0; $i < 3; $i++) {
    echo '<tr valign="top">
    <td>&nbsp;</td>
    <td><input type="text" name="newuser['.$i.'][username]" /></td>
    <td><input type="password" name="newuser['.$i.'][password]" /></td>
    <td><select name="newuser['.$i.'][level]">';
    foreach($this->levels as $name => $value)
    {
      if($value > self::GUEST)
        echo '<option value="'.$value.'">'.$name.'</option>';
    }
    echo '</select></td>';
    echo '<td>TODO</td>';
    foreach($this->userFields as $field)
    {
      echo '<td><input type="text" name="newuser['.$i.']['.$field.']" /></td>';
    }
    echo "</tr>\n";
  }
  echo '  <tr><td colspan="'.(5+count($this->userFields)).'">
      <input type="submit" value="Update" />
      <input type="reset" value="Reset" />
    </td></tr>
  </table>
</form>';
    
    $this->footer();
  }
  
  /*
  $username: username to add
  $password: password to add
  $level: permission level for user
  $groups: groups mask, or array of groups - be sure to convert to mask if passed an array
  $array: array of other user values
  $autocommit: updates file when function executes
  Adds a new user, with the username and password set,
  with permission level $level (should be passed one of the
  class constants), $array is an associative array of all the
  values in the $userFields array.  Set $autocommit to false
  if you want to add more than one user at once.  If $autocommit
  is false, you must call commitChanges() to update the underlying data.
  
  returns false if username already exists or it appears the operation failed,
  else a truthy value is returned - the id of the new user, if availible, or true if not.
  */
  abstract public function addUser($username, $password, $level = UserManager::USER, $groups = 0, $array = array(), $autocommit = true);
  
  /*
  $origPass: original password of current user
  $newPass: password to change to if user authenticates
  $autocommit: updates file when function executes
  Attempts to change the password of the current user.  If they authenticate ($origPass matches the stored password)
  then their password will be changed, and the method will return true.  Else it will return false.
  */
  abstract public function changePassword($origPass, $newPass);
  
  /*
  $user: an associateive array of values to set - id attribute is required
  $autocommit: updates file when function executes
  Modifies the values of a existing user, taking an associative array
  describing the user attributes to change.  'id' is a required index
  Set $autocommit to false if you want to modify more than one user
  at once.  If $autocommit is false, you must call commitChanges()
  to update the underlying data.
  
  returns false if it appears the operation failed, else returns true.
  */
  abstract public function modifyUser($user, $autocommit = true);
  
  /*
  $id: id of user to delete
  $autocommit: updates file when function executes
  deletes user with id passed.  Set $autocommit to false
  if you want to delete more than one user at once.  If $autocommit
  is false, you must call commitChanges() to update the underlying data.
  
  returns false if it appears the operation failed, else returns true.
  */
  abstract public function deleteUser($id, $autocommit = true);
  
  /*
  Commits changes made which have not yet been committed because $autocommit was set to false in the previous call(s)
  */
  abstract public function commitChanges();
  
  /*
  Runction for manageUsers() to get all users
  Availible for public access if such a list is desired
  */
  abstract public function getAllUsers();
  
  //////////////////
  // Internal Functions
  //////////////////
  
  /* Internal function for manageUsers() to handle updating the user data */
  public function updateUsers(){
  	if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      $toDel = array();
     // Update entries
      foreach ($this->getAllUsers() as $user)
      {
      	$id = (string)$user['id'];
        if (isset($_POST['user'][$id]))
        {
          $pUser = $_POST['user'][$id];
          if(isset($pUser['delete']) && $pUser['delete'] == 'true') {
          	$toDel[] = $user['id'];
          }
          else
          {
            $array = array('id' => $id, 'username' => $pUser['username'], 'level' => $pUser['level']);
            if(!empty($pUser['password']))
              $array['password'] = $pUser['password'];
            foreach($this->userFields as $field)
            {
              $array[$field] = isset($pUser[$field]) ? $pUser[$field] : '';
            }
            $this->modifyUser($array, false);
          }
        }
      }
      // this is necessary, because deleting while looping over users breaks out of the loop
      foreach($toDel as $id){
      	$this->deleteUser($id);
      }
      // New Entries
      foreach ($_POST['newuser'] as $pUser)
      {
        if(isset($pUser['username']) && trim($pUser['username']) != '' &&
           isset($pUser['password']) && trim($pUser['password']) != '')
        {
          $array = array();
          foreach($this->userFields as $field)
          {
            $array[$field] = isset($pUser[$field]) ? $pUser[$field] : '';
          }
          $this->addUser($pUser['username'], $pUser['password'], $pUser['level'], $array, false);
        }
      }
      $this->commitChanges();
    }
  }
  
  /*
  The password hashing function to be used.  Can be extended if you feel it is not satisfactory.
  
  Designed off of crypt(), but with major improvements including potentially
  stronger salts and hashes and no silent failures on small text lengths.
  */
  public function hash($text,$salt=''){
	  $saltlength = 20;
  	
  	if($salt == ''){
	    $chars = '';
	    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';    
	    for ($p = 0; $p < $saltlength; $p++) {
	        $chars .= $characters[mt_rand(0, strlen($characters)-1)];
	    }
	    $salt = $chars;
	  } else {
	  	$pair = explode(':',$salt);
	  	$salt = $pair[0];
	  }
	  
	  return $salt.':'.sha1($salt.$text);
  }
  
  /*
  The function deciding what a valid username is.
  Could require usernames be email addresses by extending this function, for instance.
  */
  public function validUsername($username){
  	return preg_match('/^[a-zA-Z0-9\_-]+$/',$username);
  }
  
  /*
  Triggers a PHP error.  This is only used when the class is being used incorrectly, not when an error occours.
  */
  protected final function usage_error($message){
  	$back = debug_backtrace();
		$caller = $back[count($back)-1];
		trigger_error($message.' in  <strong>'.$caller['file'].'</strong> on line <strong>'.$caller['line'].'</strong>'."\n<br />Triggered",E_USER_ERROR);
  }
  
  /*
  Logs an error from manageUsers for display.
  */
  protected function manage_error($message){
		$this->manage_errors[] = '<div class="userMngrError">'.$message.'</div>';
  }
  
  /*
  Displays the errors generated from andy calls to manage_error()
  */
  protected function print_errors(){
    foreach($this->manage_errors as $error){
	    echo $error;
	  }
	}
}

?>