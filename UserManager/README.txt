NEED A LICENSE HERE

--------------------------------------------------------------------------------

CONTENTS
1. About
2. Requirements
3. Features
4. Future Features
5. Known Bugs
6. Default Usage
   1. Initialization
   2. Creating Users
   3. Login and Checking Permission
   4. Additional Methods Worth Noting
      1. logout
      2. getCurUserID
      3. getCurUserName
      4. getCurUserLevel
      5. getUser
      6. userLevel
      7. modifyUser
      8. deleteUser
7. Advanced Usage
   1. Attributes and Constructor
   2. Extending Header and Footer
   3. Serializing Data
   4. Additional Tricks
   5. A Note on Philosophy
8. Included Files
9. Version History

--------------------------------------------------------------------------------

ABOUT
Version 1.0.2 - check http://www.DigitalGemstones.com/script/auth/UserManager.php for up to date versions.
The UserManager class is a totally self contained user management system.  Include the class in your website, and you have with a few short lines of code a fully operational login system, including multiple user levels (default User, SuperUser, and Admin) and the ability to lock web pages from users without appropriate permissions - similar to .htaccess and .htpassword locking, except integrated with your website rather than a series of intrusive 401 login prompts.

--------------------------------------------------------------------------------

REQUIREMENTS
Should be functional with a default install of PHP5, no database required.
Has been tested on an install of Uniform Server http://www.uniformserver.com/

--------------------------------------------------------------------------------

FEATURES
* XML Backend, no database or setup required.
* Customizable user permission levels.
* Customizable storage fields
* Integrates seamlessly with your website.
* One function call locks a page to anyone without the proper permission.
* Self contained user management page.
* Add and maintain users yourself, or use the pre-built manager.

--------------------------------------------------------------------------------

FUTURE FEATURES
* Optionally lock login with too many failed attempts.
* Log system to track when users log in, from where, and which pages they visit.
* Create an updateXML function to resolve Known Bug #1.
* Change how header() is called to differentiate between login pages and manage user pages.
* Compartmentalize serialization into one, independent function.
* Create a getUserByAttribute() function for more efficient search.

If you should extend the class to include any of these features, or add any other useful tools, please let me know
http://www.DigitalGemstones.com/contact.php

--------------------------------------------------------------------------------

KNOWN BUGS
* If the $userFields array is modified after records have been added to the XML file, you will not be able to add those new attributes to the user.
  The logical solution to me is to create a updateXML function which will bring all entries in the file up to speed.  Until then, simply do not change the user attributes once users have been created.
  
--------------------------------------------------------------------------------

DEFAULT USAGE
This section describes how to use the class right out of the box.  As is, UserManager has four user levels, Guest, User, Superuser, and Admin, and stores each user's username, password (encrypted), and level.  If you don't want to read through this, all public functions in the UserManager class are fully documented, and if after reading any of this you're not sure quite how something works, it's very likely the comments in the class will explain everything.

INITIALIZATION
  UserManager uses PHP Sessions to store the current visitor's information.  So before anything else, call session_start().  Then create an instance of the UserManager class by passing the constructor the location of the XML file.  You should either move the users.xml file to a safer location, either outside the web root, or with .htaccess permissions..  Even though passwords are encrypted, it is still highly irresponsible and dangerous to leave the xml file world-readable.
  
  ----------
  session_start();
  include 'usermanager.class.php';
  $_user = new UserManager('users.xml');
  ----------
  
  That's all it takes to initialize the UserManager.  To make everything much easier, your best plan would be to place these three lines of code in your common include file.  For the remainder of the readme, it will be assumed that calling "include 'common.inc.php';" will call the three lines of code listed above.
  
CREATING USERS
  There are two different ways to create and manage your website's users.  The first is through the built in manageUsers() function and the second is manually, with addUser(), modifyUser(), and deleteUser().
  
  To use the manageUsers() function, place the following in a file (admin.php or something similar):
  ----------
  <?php
  $_user->manageUsers(true); // set to false once you've created an administrator account, so that only admins can manage users.
  ?>
  ----------
  
  This will output a form to modify existing users, and add additional users.  If you don't have any users created already, you will just see three empty slots to create your users.  The 'true' parameter passed to manageUsers() overrides the protection built into the function - normally if you are not logged in as an Administrator, you will not be able to access the user managment page.  Since you haven't created any administrator accounts, you need to override this lock.
  
  IMPORTANT: make sure to remove the parameter to manageUsers() before a live launch of your site.  If override is not set, manageUsers is safe from unauthorized access.
  
  The second way to work with users is manually.  This should primarily be used if you have your own register page for users to register themselves.
  
  By calling addUser("USERNAME", "PASSWORD", UserManager::ADMIN, array()); where USERNAME and PASSWORD are your desired username and password, you will create a new user with ADMIN level permissions.  (the other default user levels are UserManager::USER and UserManager::SUPERUSER.  UserManager::GUEST also exists, but should not be used to create a user).  addUser() can be integrated with your own registration page by doing any error checks you want to make and then passing the registration information straight to addUser().  addUser() will return false if the username already exists, and will otherwise register the user.
  
  If you plan on calling addUser() more than once per execution, add an additional parameter, false, to the function call.  This disables committing the values to the file until you call commitDOMChanges() which will write all changes made to the dom, which is much faster than writing and rebuilding the dom model with every addition.

LOGIN AND CHECKING PERMISSION
  Similar to the Simple Login script, and my original inspiration for this project, is the require_login() function.  Make this function call at the top of any page, and only users logged in (with a certain permission level, if you pass one to the function) will be able to view the contents of the page.  if users are already logged in, they will notice no interruption of service, and will not need to login again.
  
  The default UserManager class has a header() and a footer() function which are called automatically by require_login() (and for that matter manageUsers()) which will generate a valid XHTML document if it needs to output the login window, however this valid XHTML is the absolute bare minimum to make a valid page - no attempt at formatting or additional code is made.  If you don't mind this minor interruption, this function will make securing your pages a lot easier.  If, however, you need to have properly formatted login page, you can either read the next paragraph to make your own login script, or even better, read 'Extending Header and Footer' in the 'Advanced Usage' section to make require_login() output login pages that match your site's template.
  
  If you want to create your own login logic, or have pages that prompt for login with requiring it, you'll want to use the login() function.  The login function takes a username and password and returns a boolean.  If true, they've logged in, if false, one of their credentials was incorrect.  Note that it is a security feature, and not a bug, that /which/ credential was incorrect is indeterminate.
  
  You may want to output certain content only to logged in users (for instance, perhaps only logged in users should see a link to post a new topic).  For this functionality, use hasPerm().  The default parameter is UserManager::USER, but if you want to be more restrictive, for instance displaying a link to the admin panel only to administrators, simply pass a different user level to the function.  The function returns a boolean if the user has permission or not.  Example:
  ----------
  if($_user->hasPerm(UserManager::ADMIN))
  {
    echo '<a href="admin.php">Manage Users</a>';
  }
  ----------
  
ADDITIONAL METHODS WORTH NOTING
  logout
    Call logout() and the current user will be logged out and reduced to GUEST permission level.
  getCurUserID
    Returns the current user's id - works off a session variable, so no file-read overhead.
  getCurUserName
    Returns the current user's username - works off a session variable, so no file-read overhead.
  getCurUserLevel
    Returns the current user's permission level (as an int) - works off a session variable, so no file-read overhead.
  getUser
    returns an array of the attributes of the user who's id is passed.  Note that this function searches the XML file and should not be used for redundant or unnecessary calls.
  userLevel
    Returns the string version of a permission level, pass it the result of getCurUserLevel(), for instance.
  modifyUser
    Just like add user, but takes an additional parameter (first) of the user ID to modify.  Note that this function allows for the username to be changed.  If you do not want to allow this functionality, deny such action in your own code, before calling modifyUser().
  deleteUser
    Takes a user id to delete from the XML file.

--------------------------------------------------------------------------------

ADVANCED USAGE
  This section documents how to properly extend the UserManager class to make it even more powerful and customizable, and a few other more advanced tricks.  It assumes you've read the Default Usage, and so does not document how to use the class itself.  Note that this advanced usage assumes a fair amount of understanding of more advanced object orientation concepts, notably class and function extension.

ATTRIBUTES AND CONSTRUCTOR
  If you wish to create additional user levels, you must introduce a new constant, such as 'const SUBSCRIBER = 3;' make the name descriptive, and make the value some integer to represent its rank.  Larger numbers have higher permissions.  Note that 0 is a guest, a normal user is 2, a super user is 4, and an administrator is 10.
  
  There are two changes you can make to the constructor, the first is in tandem with any new user levels you introduce.  In addition to the constant, you must also add the constant to the $levels array, with the title as the key, and the int value as the value.  It may seem a little backward, and there may be a better way to do it, but that's how it's written for now.
  ----------
  $this->levels['SUBSCRIBER'] = self::SUBSCRIBER;
  ----------
  
  The other change to the constant, and the much more interesting one, is to introduce new attributes for the XML document to store.  Do this by appending an array of the attributes you want to add, for instance:
  ----------
  $this->userFields = array('name', 'email', 'gender', 'ip');
  ----------
  
  MAKE SURE you call the parent constructor and pass it the file you want to reference, even if you don't want to extend either the user levels or the user attributes.
  
EXTENDING HEADER AND FOOTER
  Extending the header and footer will allow you to display your own template when using the require_login() and manageUsers() functions.  Quite simply, insert your own template header code, and that's that.  For instance, to work with the template system I normally use, you'd do the following:
  ----------
  function header()
  {
    global $_template;
    // Could throw in some test to determine if a login is being prompted, or the admin panel is displaying, which would allow for custom titles.  Future, perhaps.
    $_template->header();
  }
  function footer()
  {
    global $_template;
    $_template->footer();
  }
  ----------

SERIALIZING DATA
  If there is any user data you would want to store, but cannot or should not be stored in its original form, it will need to be serialized (I use the term loosely) before being input into the XML file.  To properly serialize data, you must extend both the addUser() and modifyUser() functions.  For both, you will want your new function to take the exact same parameters, process the array of user attributes, then call the parent function and pass it everything including the modified array.
  ----------
  function addUser($username, $password, $level, $array, $autocommit = true)
  {
    $array['ip'] = trim($array['ip']) != '' ? encode_ip($array['ip']) : '';
    parent::addUser($username, $password, $level, $array, $autocommit);
  }
  ----------
  This example is unlikely, since you do not see the same benefits to compressing an IP address in an XML document as you do in a database, however it works well as an example.
  
  Unserializing is simpler, simply extend the function unserializeUser() to unserialize whatever data had been serialized.  However unlike the array in addUser() and modifyUser(), unserializeUser() will be passed an object, with user attributes as attributes of that object.  For example:
  ----------
  function unserializeUser($user)
  {
    $user->ip = trim($user->ip) != '' ? decode_ip($user->ip) : '';
    return $user;
  }
  ----------
  
  In addition, if you intend to do any searches of serialized data, you'll need to extend lookupAttribute() to serialize the value being looked up before hand.
  
ADDITIONAL TRICKS
  Since you are able to pass the XML file at the time of initialization, you can use more than one XML file in your website, perhaps if you want a completely different set of users for one section that for another.  That said, generally it's probably better to let users login once and stay logged in, which is of course the default operation of the UserManager class.
  
  In addition, and perhaps more useful, you can extend the class more than once, and still work with the same XML file (though like Known Bug #1 says, if your user attributes do not match up with the attributes listed in the file, you could very well run into some very nasty, almost untraceable bugs).  Using multiple different extent ions would allow you, for instance, to output different header and footer code depending on the page (say admin pages have a more detailed header) or perhaps you want to serialize and unserialize data differently depending on the page, or whose logged in.  Additional extent ions of the UserManager class are not the only way to handle these scenarios, but I want to make sure users know the option exists.
  
A NOTE ON PHILOSOPHY
  I'm sure there are users who will, rather than extending the class, just go in and play with my code.  In some sense, that's easier, however easier doesn't always mean better, and this is one of those cases.  There are several reasons why I discourage this.  First off, at a purist level, it makes a lot of sense to keep different code separate and compartmentalized.  If your code and my code start merging, finding errors becomes that much harder.  Furthermore, part of the underlying logic of OOP is to keep modifications and the original as completely separate entities, allowing the developer to set the ground rules for how his code should be used.  Since PHP is not compiled beforehand, there's nothing stopping you from breaking these principles of OOP, but if you should chose to do that, I'd like you to ask yourself why you're thinking of violating this concept.  The only reason one would theoretically need to go into the original class is if there is something actually wrong with the code, in which case I would greatly prefer it if you contacted me, and we worked to improve the code together, rather than simply futzing with it yourself.  
  
  In addition, and this is a far more practical argument, if you leave the original UserManager class untouched, when I roll out updates, all you'll have to do is replace the file.  It's unlikely any of my updates will break how functions are supposed to be extended, and even if that is the case, I'll make sure to mention those changes and how to handle them.  This means implementing these kind of updates will most likely be nearly painless.  
  
  One final note, I strongly discourage trying to extend any functions that are not labeled as extendable in the documentation of the UserManager class.  I've tried to make this class as extendable and customizable as possible, so if a function is not extendable, there's probably a good reason.
  
INCLUDED FILES
  UserManager.class.php
  This is the main class, include it in your code and create an instance of it in order to work with the UserManager.
  users.xml
  This empty, template XML file is all set for immediate launch, simply save it somewhere secure and point the UserManager class at it.
  UserManager.Extended.class.php
  This is a class sampling one possible way to extend the UserManager to draw more out of the application.
  readme.txt
  The file your reading right now, documenting how to use the UserManager.

VERSION HISTORY
1.x.x
  1.0.2 - Minor Feature Improvements
  1.0.1 - Bug Fixes
  1.0.0 - Initial Release