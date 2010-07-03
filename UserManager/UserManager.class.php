<?php

/*
This application was written by Michael Diamond and DigitalGemstones.com �2008
It is released for any and all not-for-profit use.
If you wish to use this script to directly or indirectly make a profit, you must first contact and receive permission from Michael Diamond.
http://www.DigitalGemstones.com/contact.php

VERSION: 1.0.2
*/

/*
UserManager class is a self contained way of tracking and controlling users, fully functional, no database required.
*/
class UserManager
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
  var $permissionFail = 'You are logged in, but do not have adequate permissions to view this page.';
  var $levels = array('GUEST' => UserManager::GUEST, 'USER' => UserManager::USER, 'SUPERUSER' => UserManager::SUPERUSER, 'ADMIN' => UserManager::ADMIN);
  var $user = array(); // populated in constructor
  var $loadedUser = false;
  const GUEST = 0;
  const USER = 2;
  const SUPERUSER = 4;
  const ADMIN = 10;
  
  /*
  Abstract class that unifies how XMLUserManager and MySQLUserManager behave
  */
  function UserManager($file)
  {
    if(!isset($_SESSION['usermanager__level']))
    {
      $_SESSION['usermanager__id'] = 0;
      $_SESSION['usermanager__level'] = self::GUEST;
      $_SESSION['usermanager__username'] = '';
    }
      
		$this->user = array(
			'id' = $_SESSION['usermanager__id'],
			'level' = $_SESSION['usermanager__level'],
			'username' = $_SESSION['usermanager__username']
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
	function loadCurUser(){
		if($this->loadedUser){
			return;
		}
		$this->loadedUser = true;
		// NOT IMPLEMENTED AT THIS LEVEL
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
  function getUser($id)
  {
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
  /*
  $attribute: attribute to lookup from the user fields array (or username, password, or level)
  $value: value of the attribute to look for
  searches for users who have their $attribute set to $value and returns an array of all matches.
  */
  function lookupAttribute($attribute, $value, $justID=false)
  {
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
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
    $user = $users[0];
    if($username == $user['username'] && $user->password == $this->hash($password, $user['password']))
    {
      $_SESSION['usermanager__level'] = $user['level'];
      $_SESSION['usermanager__username'] = $user['username'];
      $_SESSION['usermanager__id'] = $user['id'];
      return true;
    }
    return false;
  }
  
  /*
  $minPermission: minimum permission level to view page
  call function at beginning of page, if user is logged in
  (and has appropriate permissions) page will load as normal.
  If not, user is prompted to log in, and remainder of page does
  not load until logged in.
  
  IMPORTANT: Note that this function uses $_SERVER['REQUEST_METHOD'] == "POST" to test if POST information was submitted.
  in order to prevent any code in your page from thinking /it's/ forms were submitted, $_SERVER['REQUEST_METHOD'] is set to 'GET'
  upon successful login.  If you use a different method to test if a form has been submitted, you should add an additional test to ensure
  it is in fact your form, and not the login form, which was submitted.
  */
  function require_login($minPermission = UserManager::USER)
  {
    if($this->user['level'] >= $minPermission)
      return;
      
    $error = false;
    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      if($this->login($_POST['user'], $_POST['pass']) && $this->user['level'] >= $minPermission)
      {
        $_SERVER['REQUEST_METHOD'] = "GET"; // so as not to throw off any other scripts checking against post method
        return;
      }
      else $error = true;
    }
    if($this->user['level'] > self::GUEST && $this->user['level'] < $minPermission)
    {
      $error = true;
      $notice = $this->permissionFail;
    }
    else $notice = $this->loginFail;
    
    $this->header('Login Required');
    
    echo '  <div class="usermanager_auth">'.
    (($error) ? '    <div class="usermanager_notice">'.$notice.'</div>' : '')
    .'    <form action="'.$_SERVER['PHP_SELF'].'" method="post">
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
    $_SESSION['usermanager__username'] = '';
    $_SESSION['usermanager__id'] = 0;
    
    $this->user = array(
			'id' = $_SESSION['usermanager__id'],
			'level' = $_SESSION['usermanager__level'],
			'username' = $_SESSION['usermanager__username']
		);
  }
  
  /*
  $minPermission: minimum permission level
  returns true if current user has or exceeds
  the minimum permission level passed.
  */
  function hasPerm($minPermission = self::USER)
  {
    return $this->user['level'] >= $minPermission;
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
    
    echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">
      <table border="1" cellspacing="0" cellpadding="3">
        <tr>
          <th>Delete?</th>
          <th>Username</th>
          <th>New Password</th>
          <th>Level</th>';
   foreach($this->userFields as $field)
    {
      echo '<th>'.$field.'</th>';
    }
    echo '</tr>';

  foreach ($this->getAllUsers() as $user) {
    $this->unserializeUser($user); // This shouldn't need to actually return anything, since the object it being modified.
    echo '<tr valign="top">
    <td><input type="checkbox" name="user['.htmlentities($user['id']).'][delete]" value="true" /></td>
    <td><input type="text" name="user['.htmlentities($user['id']).'][username]" value="'.htmlentities($user->username).'" /></td>
    <td><input type="password" name="user['.htmlentities($user['id']).'][password]" /></td>
    <td><select name="user['.htmlentities($user['id']).'][level]">';
    foreach($this->levels as $name => $value)
    {
      if($value > self::GUEST)
        echo '<option value="'.$value.'" '.(((int)$user->level == $value) ? 'selected="selected" ' : '').'>'.$name.'</option>';
    }
    echo '</select></td>';
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
    foreach($this->userFields as $field)
    {
      echo '<td><input type="text" name="newuser['.$i.']['.$field.']" /></td>';
    }
    echo "</tr>\n";
  }
  echo '  <tr><td colspan="'.(4+count($this->userFields)).'">
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
  $array: array of other user values
  $autocommit: updates file when function executes
  Adds a new user, with the username and password set,
  with permission level $level (should be passed one of the
  class constants), $array is an associative array of all the
  values in the $userFields array.  Set $autocommit to false
  if you want to add more than one user at once.  If $autocommit
  is false, you must call commitChanges() to update the underlying data.
  
  returns false if username already exists, else returns true.
  
  Suggest extending this function in order to
  serialize and format for storage custom parameters
  */
  function addUser($username, $password, $level, $array, $autocommit = true)
  {
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
  /*
  $origPass: original password of current user
  $newPass: password to change to if user authenticates
  $autocommit: updates file when function executes
  Attempts to change the password of the current user.  If they authenticate ($origPass matches the stored password)
  then their password will be changed, and the method will return true.  Else it will return false.
  */
  function changePassword($origPass, $newPass, $autocommit = true){
  	// NOT IMPLEMENTED AT THIS LEVEL
  }
  
  /*
  $id: id of user to modify
  $username: new username
  $password: new password
  $level: new permission level
  $array: array of new user values
  $autocommit: updates file when function executes
  Modifies the values of a existing user, with the username and password set,
  with permission level $level (should be passed one of the
  class constants), $array is an associative array of all the
  values in the $userFields array.  Set $autocommit to false
  if you want to modify more than one user at once.  If $autocommit
  is false, you must call commitChanges() to update the underlying data.
  
  Suggest extending this function in order to
  serialize and format for storage custom parameters
  */
  function modifyUser($id, $username, $password, $level, $array, $autocommit = true)
  {
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
  /*
  $id: id of user to delete
  $autocommit: updates file when function executes
  deletes user with id passed.  Set $autocommit to false
  if you want to delete more than one user at once.  If $autocommit
  is false, you must call commitChanges() to update the underlying data.
  */
  function deleteUser($id, $autocommit = true)
  {
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
  /*
  Commits changes made with autocommit set to false
  */
  function commitChanges()
  {
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
  //////////////////
  // Private Functions
  //////////////////
  
  /* Private function for manageUsers() to get all users */
  function getAllUsers(){
  	// NOT IMPLEMENTED AT THIS LEVEL
  }
  
  /* Private function for manageUsers() to handle updating the user data */
  function updateUsers(){
    // NOT IMPLEMENTED AT THIS LEVEL
  }
  
  function hash($string,$salt=''){
  	
  }
}

?>