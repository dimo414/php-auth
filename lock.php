<?php

session_start(); // If you don't start the session somewhere else

function auth($password)
{
  if(!(isset($_SESSION['digigem_auth']) &&
          $_SESSION['digigem_auth'] == $_SERVER['REMOTE_ADDR'] &&
          isset($_SESSION[$password])))
  {
    $error = false;
    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
      if($_POST['pass'] == $password)
      {
        $_SESSION['digigem_auth'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION[$password] = $password;
        // To ensure any forms behind auth() pages are not accidentally triggered.
        $_SERVER['REQUEST_METHOD'] == "GET"; 
        return;
      }
      else $error = true;
    }
    
    // This is the valid HTML page which will output if user is not authenticated.
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head><title>Please Log In</title></head>

  <body><div class="auth">'.
  (($error) ? '<div class="loginError">Login Failed - Try Again</div>' : '')
  .'<form action="'.$_SERVER['PHP_SELF'].'" method="post">
  <table>
    <tr><td>Password:</td><td><input type="password" name="pass"></td></tr>

    <tr><td>&nbsp;</td><td><input type="submit" value="Submit"></td></tr>
  </table></form></div></body></html>';
    exit;
  }
}
?>