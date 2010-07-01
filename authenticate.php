<?php

session_start(); // If you don't start the session somewhere else

// This function tests to see if user is already authenticated
//and if not outputs a form, the page footer, and exit
function authenticate($username, $password)
{
  if(!authentic())
  {
    $error = false;
    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      if($_POST['user'] == $username && $_POST['pass'] == $password)
      {
        $_SESSION['digigem_auth'] = md5($_SERVER['REMOTE_ADDR']);
        $_SERVER['REQUEST_METHOD'] = "GET";
        return;
      }
      else $error = true;
    }
    
//////////////////////////////
// Insert your own page-header code here for a properly formatted page
// global $_template;
// $_template->header("Please Log In");
//////////////////////////////
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head><title>Please Log In</title></head>
<body><div class="auth">';

//////////////////////////////

    echo '  <div class="auth">'.
    (($error) ? '    <div class="smallNotice">Login Failed - Try Again</div>' : '')
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

//////////////////////////////
// Insert your own page-footer code here for a properly formatted page
// $_template->footer();
//////////////////////////////
echo '</div></body></html>';

//////////////////////////////

    exit;
  }
}

// This function returns true if user is authentic
function authentic()
{
  return (isset($_SESSION['digigem_auth']) && 
          $_SESSION['digigem_auth'] == md5($_SERVER['REMOTE_ADDR']));
}

?>