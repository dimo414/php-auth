<?php

/*
This application was written by Michael Diamond and DigitalGemstones.com ©2008
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
  var $file;
  var $simple;
  var $dom;
  var $userFields = array();
  var $loginFail = 'Incorrect username or password.';
  var $permissionFail = 'You are logged in, but do not have adequate permissions to view this page.';
  var $levels = array('GUEST' => UserManager::GUEST, 'USER' => UserManager::USER, 'SUPERUSER' => UserManager::SUPERUSER, 'ADMIN' => UserManager::ADMIN);
  var $fileMD5;
  const GUEST = 0;
  const USER = 2;
  const SUPERUSER = 4;
  const ADMIN = 10;
  
  /*
  $file: path to xml file
  ensure xml file is not publicly accessible
  
  Suggest extending this function in order to
  add additional permission levels and user fields.
  */
  function UserManager($file)
  {
    $this->file = $file;
    $this->simple = simplexml_load_file($file);
    $this->fileMD5 = md5($file);
    
    if(!isset($_SESSION['usermanager_'.$this->fileMD5.'_level']))
    {
      $_SESSION['usermanager_'.$this->fileMD5.'_level'] = self::GUEST;
      $_SESSION['usermanager_'.$this->fileMD5.'_username'] = '';
      $_SESSION['usermanager_'.$this->fileMD5.'_id'] = 0;
    }
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
  Returns current user's ID.
  */
  function getCurUserID()
  {
    return $_SESSION['usermanager_'.$this->fileMD5.'_id'];
  }
  
  /*
  Returns current user's username.
  */
  function getCurUsername()
  {
    return $_SESSION['usermanager_'.$this->fileMD5.'_username'];
  }
  
  /*
  Returns current user's user level
  */
  function getCurUserLevel()
  {
    return $_SESSION['usermanager_'.$this->fileMD5.'_level'];
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
    $users = $this->simple->xpath('user[@id='.htmlentities($id).']');
    $user = $this->unserializeUser($users[0]);
    $array = array('username' => (string)$user->username,
                 'password' => (string)$user->password,
                 'level'    => (string)$user->level);
    foreach($this->userFields as $field)
    {
      $array[$field] = isset($user->$field) ? (string)$user->$field : '';
    }
    return $array;
  }
  
  /*
  $attribute: attribute to lookup from the user fields array (or username, password, or level)
  $value: value of the attribute to look for
  searches for users who have their $attribute set to $value and returns an array of all matches.
  Note that this is a linear search, and is not efficient enough to reguarly retreive data from.
  Note also that since this function only returns a id, and not a user's full informaiton, it is not
  efficent to use this function heavily for search.  A future release will contain a getUserByAttribute()
  function to limit cycles through the document for cases such as that.
  
  Suggest extending this function in order to serialize any attributes you would need serialized.
  */
  function lookupAttribute($attribute, $value)
  {
    $array = array();
    foreach($this->simple->user as $user)
    {
      if($user->$attribute == $value)
      {
        $array[] = $user['id'];
      }
    }
    return $array;
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
    // It may be more efficient to XPath the user, and then check the password
    // Find out if XPath is more efficient than iterating
    foreach($this->simple->user as $user)
    {
      if($username == (string)$user->username && $user->password == crypt($password, $user->password))
      {
        $_SESSION['usermanager_'.$this->fileMD5.'_level'] = (string)$user->level;
        $_SESSION['usermanager_'.$this->fileMD5.'_username'] = (string)$user->username;
        $_SESSION['usermanager_'.$this->fileMD5.'_id'] = (string)$user['id'];
        return true;
      }
    }
    return false;
  }
  
  /*
  $username: username to attempt to log in
  $password: password to attempt to log in
  $failcount: number of aceptible failures before sleep() gets called
  to integrate with your site, pass a username and password entered by a client,
  if correct, will log user in and return true,
  if incorrect, will return false AND after $failcount will start
  adding a sleep call - scaling based on the number of failed
  attempts - to the execution of the function, delaying future page loads and hindering
  brute force attacks.
  */
  function loginSleep($username, $password, $failcount = 3)
  {
  	// must figure out where to get $clientFailCount from...
  	$clientFailCount = 0;
  	if($clientFailCount > $failcount)
  	  sleep(pow(2,$clientFailCount));
  	return login($username, $password);
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
    if($this->getCurUserLevel() >= $minPermission)
      return;
      
    $error = false;
    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      if($this->login($_POST['user'], $_POST['pass']) && $this->getCurUserLevel() >= $minPermission)
      {
        $_SERVER['REQUEST_METHOD'] = "GET"; // so as not to throw off any other scripts checking against post method
        return;
      }
      else $error = true;
    }
    if($_SESSION['usermanager_'.$this->fileMD5.'_level'] > self::GUEST && $this->getCurUserLevel() < $minPermission)
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
    $_SESSION['usermanager_'.$this->fileMD5.'_level'] = 0;
    $_SESSION['usermanager_'.$this->fileMD5.'_username'] = '';
    $_SESSION['usermanager_'.$this->fileMD5.'_id'] = 0;
  }
  
  /*
  $minPermission: minimum permission level
  returns boolean if current user has or exceeds
  the minimum permission level passed.
  */
  function hasPerm($minPermission = self::USER)
  {
    return $_SESSION['usermanager_'.$this->fileMD5.'_level'] >= $minPermission;
  }
  
  /*
  Does nothing by default
  
  Suggest extending this function in order to
  format and unserialize the output
  $user parameter is an object, each user field stored as an attribute
  */
  function unserializeUser($user)
  {
    return $user;
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

  foreach ($this->simple->user as $user) {
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
  is false, you must call commitDOMChanges() to update the XML file.
  
  returns false if username already exists, else returns true.
  
  Suggest extending this function in order to
  serialize and format for storage custom parameters
  */
  function addUser($username, $password, $level, $array, $autocommit = true)
  {
    $usernameMatches = $this->lookupAttribute('username', $username);
    if(count($usernameMatches) > 0)
    return false;
    $nextId = (int)$this->simple->nextid[0];
    $this->simple->nextid[0] = $this->simple->nextid[0] + 1;
            
    $this->loadDom();
    $root = $this->dom->documentElement;
    $nextIdNode = $root->getElementsByTagName('nextid')->item(0);
    $nextIdNode->nodeValue = $nextId+1;
    $xUser = $root->appendChild($this->dom->createElement('user'));
    $xUser->setAttribute('id', $nextId);
    $xUser->appendChild($this->dom->createElement('username', $username));
    $xUser->appendChild($this->dom->createElement('password', crypt($password)));
    $xUser->appendChild($this->dom->createElement('level', $level));
    foreach($this->userFields as $field)
    {
      $xUser->appendChild($this->dom->createElement($field, $array[$field]));
    }
    
    if($autocommit)
      $this->commitDOMChanges();
    return true;
  }
  
  /*
  $origPass: original password of current user
  $newPass: password to change to if user authenticates
  $autocommit: updates file when function executes
  Attempts to change the password of the current user.  If they authenticate ($origPass matches the stored password)
  then their password will be changed, and the method will return true.  Else it will return false.
  */
  function changePassword($origPass, $newPass, $autocommit = true){
  	$this->loadDom();
    $xpath = new DOMXPath($this->dom);
    $xUser = $xpath->query('user[@id='.htmlentities($this->getCurUserID()).']')->item(0);
    $xPassword = $xUser->getElementsByTagName('password')->item(0);
    
    if ($xPassword->nodeValue == crypt($origPass, $xPassword->nodeValue))
    {
      $xPassword->nodeValue = crypt($newPass);
      if($autocommit)
      	$this->commitDOMChanges();
      return true;
    }
    return false;
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
  is false, you must call commitDOMChanges() to update the XML file.
  
  Suggest extending this function in order to
  serialize and format for storage custom parameters
  */
  function modifyUser($id, $username, $password, $level, $array, $autocommit = true)
  {
    $this->loadDom();
    $xpath = new DOMXPath($this->dom);
    $xUser = $xpath->query('user[@id='.htmlentities($id).']')->item(0);
    $xUsername = $xUser->getElementsByTagName('username')->item(0);
    $xUsername->nodeValue = $username;
    
    if (trim($password) != '')
    {
      $xPassword = $xUser->getElementsByTagName('password')->item(0);
      $xPassword->nodeValue = crypt($password);
    }
    
    $xLevel = $xUser->getElementsByTagName('level')->item(0);
    $xLevel->nodeValue = $level;
    
    foreach($this->userFields as $field)
    {
      $tag = $xUser->getElementsByTagName($field)->item(0);
      $tag->nodeValue = $array[$field];
    }
    
    if($autocommit)
      $this->commitDOMChanges();
  }
  
  /*
  $id: id of user to delete
  $autocommit: updates file when function executes
  deletes user with id passed.  Set $autocommit to false
  if you want to delete more than one user at once.  If $autocommit
  is false, you must call commitDOMChanges() to update the XML file.
  */
  function deleteUser($id, $autocommit = true)
  {
    $this->loadDom();
    $xpath = new DOMXPath($this->dom);
    $xUser = $xpath->query('user[@id='.htmlentities($id).']')->item(0);
    $xUser->parentNode->removeChild($xUser);
    if($autocommit)
      $this->commitDOMChanges();
  }
  
  /*
  Commits changes made to the file, and reloads
  simplexml and dom.
  */
  function commitDOMChanges()
  {
    $this->dom->formatOutput = true;
    unlink($this->file);
    $this->dom->save($this->file);
    $this->dom = null;
    // $this->loadDom(); // I don't think this is necisary, since any use of $dom calls loadDom() first anyways
    $this->simple = simplexml_load_file($this->file);
  }
  
  //////////////////
  // Private Functions
  //////////////////
  
  /* Private function for manageUsers() */
  private function updateUsers()
  {
    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      $maxId = (int)$this->simple->nextid;
      $this->loadDom();
      $root = $this->dom->documentElement;
      
     // Update entries
      foreach ($root->getElementsByTagName('user') as $xUser)
      {
        $id = $xUser->getAttribute('id');
        if (isset($_POST['user'][$id]))
        {
          $pUser = $_POST['user'][$id];
          if(isset($pUser['delete']) && $pUser['delete'] == 'true') {
            $root->removeChild($xUser);
          }
          else
          {
            $array = array();
            foreach($this->userFields as $field)
            {
              $array[$field] = isset($pUser[$field]) ? $pUser[$field] : '';
            }
            $this->modifyUser($id, $pUser['username'], $pUser['password'], $pUser['level'], $array, false);
          }
        }
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
      $this->commitDOMChanges();
    }
  }
  
  /*
  Loads DOM of xml file into memory on
  pages that need it.
  */
  private function loadDom()
  {
    if(empty($this->dom))
    {
      $this->dom = new DOMDocument();
      $this->dom->preserveWhiteSpace = false;
      $this->dom->load($this->file);
    }
  }
}

?>