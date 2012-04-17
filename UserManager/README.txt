This tool was developed by Michael Diamond (http://www.DigitalGemstones.com)
and is released freely for personal and corporate use under the licence which can be found at:
http://digitalgemstones.com/licence.php
and can be summarized as:
You are free to use this software for any purpose as long as Digital Gemstones is credited,
and may redistribute the software in its original form or modified as you see fit, 
as long as any credit comments in the code remain unchanged.

--------------------------------------------------------------------------------

CONTENTS
1. About
2. Requirements
3. Features
4. Future Features
5. Default Usage
   1. Initialization
      1. Database Setup
   2. Creating Users
   3. Login and Checking Permission
   4. Current User Information
   5. Additional Methods Worth Noting
      1. logout
      2. loadCurUser
      3. getUser
      4. lookupAttribute
      5. userLevel
      6. modifyUser
      7. deleteUser
      8. commitChanges
6. Advanced Usage
   1. New Permission Levels and Additional Fields
   2. Extending Header and Footer
7. Included Files
8. Version History

--------------------------------------------------------------------------------

ABOUT
	Version 1.5.0 - check http://www.digitalgemstones.com/code/tools/auth/UserManager/ for up to date versions.
	The UserManager class is a totally self contained user management system.  Include the class in your website, and you have with a few short lines of code a fully operational login system, including multiple user levels (default User, SuperUser, and Admin) and the ability to lock web pages from users without appropriate permissions - similar to .htaccess and .htpassword locking, except integrated with your website rather than a series of intrusive 401 login prompts.
	
	There are two versions, XMLUserManager and MySQLUserManager, both of which extend UserManager.  XMLUserManager is the primary tool, providing user managment without a database or any complicated configuration.  MySQLUserManager is a new tool which lets you maintain larger user bases, and is ideal if you are already using a database, but don't have user managment.  Both classes follow the same interface, and therefore switching from one to the other is as easy as changing which class you initialize.

--------------------------------------------------------------------------------

REQUIREMENTS
	Should be functional with a default install of PHP5, no database required (for XMLUserManager, MySQL is obviously required for MySQLUserManager).
	Tested on an install of Uniform Server http://www.uniformserver.com/

--------------------------------------------------------------------------------

FEATURES
	* XML Backend: no database or setup required.
	* MySQL Backend: easy to setup, scales well.
	* Customizable user permission levels.
	* Customizable storage fields.
	* Integrates seamlessly with your website.
	* One function call locks a page to anyone without the proper permission.
	* Self contained user management page.
	* Add and maintain users yourself, or use the pre-built manager.

--------------------------------------------------------------------------------

FUTURE FEATURES
	* ManageUsers should be divided into separate pages in order to deal with larger user bases.
	
	If you should extend the class to include any of these features, or add any other useful tools, please let me know
	http://www.DigitalGemstones.com/contact.php
  
--------------------------------------------------------------------------------

DEFAULT USAGE
	This section describes how to use the class right out of the box.  As is, UserManager has four user levels, Guest, User, Superuser, and Admin, and stores each user's username, password (encrypted), and level.  If you don't want to read through this, all public functions in the UserManager class are fully documented, and if after reading any of this you're not sure quite how something works, it's very likely the comments in the class will explain everything.

INITIALIZATION
	UserManager uses PHP Sessions to store the current visitor's information.  So before anything else, call session_start().  Then create an instance of either the XMLUserManager class by passing the constructor the location of the XML file, or the MySQLUserManager class by passing a mysqli database connection, and the name of a table the class should work with.  If using the XMLUserManager, be sure to specify a location outside the web root or hidden from public view.  Even though passwords are encrypted, it is still highly irresponsible and dangerous to leave the xml file world-readable.
	
	----------
	include 'XMLUserManager.class.php';
	session_start();
	$user = new XMLUserManager('users.xml');
	----OR----
	include 'MySQLUserManager.class.php';
	session_start();
	$db = new mysqli(HOST,USER,PASS,DB);
	$user = new MySQLUserManager($db);
	----------
	
	That's all it takes to initialize the UserManager.  To make everything much easier, your best plan would be to place these three lines of code in your common include file. For the remainder of the readme, it will be assumed that calling "include 'common.inc.php';" will call the lines of code listed above.

DATABASE SETUP
	Using MySQLUserManager requires slightly more setup than XMLUserManager, but it is still quite easy.  The first time you construct the UserManager, you will need to construct the appropriate table.  MySQLUserManager provides the necessary SQL, which it can provide to you, or execute itself if it has enough permission.
	
	If the user on the connection passed to MySQLUserManager has CREATE permission, you can call 'createTable()', this will execute the create command and construct the necessary table.  Alternatively, you can call 'echo createTableString()' to get the command to execute it manually.
	
	MySQLUserManager cannot predict what data you may want to search by.  Looking up by username and id are fast with the default command, but if you extend the class (more below) be aware that lookupAttribute() is only efficent when run against properly indexed data.  Therefore you should modify the table or the CREATE instruction as necessary - a decent rule of thumb would be to index any colum you intend to search by, but MySQLUserManager does not eliminate the need for good database design.
  
CREATING USERS
  There are two different ways to create and manage your website's users.  The first is through the built in manageUsers() function and the second is manually, with addUser(), modifyUser(), and deleteUser().
  
  The easiest way is to use the manageUsers() function, which you can use by placing the following in a file (admin.php or something similar):
  ----------
  <?php
  include 'common.inc.php';
  $user->manageUsers(true); // set to false once you've created an administrator account, so that only admins can manage users.
  ?>
  ----------
  
  This will output a form to modify existing users, and add additional users.  If you don't have any users created already, you will just see three empty slots to create your users.  The 'true' parameter passed to manageUsers() overrides the protection built into the function - normally if you are not logged in as an Administrator, you will not be able to access the user managment page.  Since you haven't created any administrator accounts, you need to override this lock.
  
  IMPORTANT: make sure to remove the parameter to manageUsers() before a live launch of your site.  If override is not set, manageUsers is safe from unauthorized access.
  
  The second way to work with users is manually.  This should primarily be used if you have your own register page for users to register themselves.
  
  By calling addUser("USERNAME", "PASSWORD", UserManager::ADMIN, array()); where USERNAME and PASSWORD are your desired username and password, you will create a new user with ADMIN level permissions.  (the other default user levels are UserManager::USER and UserManager::SUPERUSER.  UserManager::GUEST also exists, but should not be used to create a user).  addUser() can be integrated with your own registration page by doing any error checks you want to make and then passing the registration information straight to addUser().  addUser() will return false if the username already exists, and will otherwise register the user.
  
  If you plan on calling addUser() more than once per execution, add an additional parameter, false, to the function call.  This disables committing the values to the file until you call commitDOMChanges() which will write all changes made to the dom, which is much faster than writing and rebuilding the dom model with every addition.

LOGIN AND CHECKING PERMISSION
  Similar to the Simple Login script, and my original inspiration for this project, is the require_login() function.  Make this function call at the top of any page, and only users logged in (with a certain permission level, if you pass one to the function) will be able to view the contents of the page.  if users are already logged in, they will notice no interruption of service, and will not need to login again.
  
  The default UserManager class has a header() and a footer() function which are called automatically by require_login() (and for that matter manageUsers()) which will generate a valid XHTML document if it needs to output the login window, however this valid XHTML is the absolute bare minimum to make a valid page - no attempt at formatting or additional code is made.  If you don't mind this minor interruption, this function will make securing your pages a lot easier.  If, however, you need to have properly formatted login page, you can either read the next paragraph and make your own login script, or even better, read 'Extending Header and Footer' in the 'Advanced Usage' section to make require_login() output login pages that match your site's look and feel.
  
  If you want to create your own login logic, or have pages that prompt for login with requiring it, you'll want to use the login() function.  The login function takes a username and password and returns a boolean.  If true, they've logged in, if false, one of their credentials was incorrect.  Note that it is a security feature, and not a bug, that /which/ credential was incorrect is indeterminate.
  
  You may want to output certain content only to logged in users (for instance, perhaps only logged in users should see a link to post a new topic).  For this functionality, use hasPerm().  The default parameter is UserManager::USER, but if you want to be more restrictive, for instance displaying a link to the admin panel only to administrators, simply pass a different user level to the function.  The function returns a boolean if the user has permission or not.  Example:
  ----------
  if($_user->hasPerm(UserManager::ADMIN))
  {
    echo '<a href="admin.php">Manage Users</a>';
  }
  ----------
  
CURRENT USER INFORMATION
  The current user's ID, username, and level are stored in the session and are immediatly availible to every webpage.  This information is stored in the $user array.  If you need additional information about the current user, you must call loadCurUser().  This will populate the array with all other information on the current user.
  
ADDITIONAL METHODS WORTH NOTING
  logout
    Call logout() and the current user will be logged out and reduced to GUEST permission level.
  loadCurUser
    To save calls, the current user's extended data is not loaded unless requested.  The ID, Username, and Level are availible in the $user->user array immediatly.  If you need the extended data, like the password hash or any custom attributes being stored, call loadCurUser() to populate the $user->user array with all the current user's information.
  getUser
    To get information on a user given an ID, you can call $user->getUser($id).  This will return an associative array of all the details of that user.
  lookupAttribute
    To lookup users by anything other than their ID you can use lookupAttribute.  It takes two parameters, the attribute to lookup by, and a value to match.  It will return an array of users (which are themselves associative arrays of all the user's attributes).
  userLevel
    Returns the string version of a permission level, pass it the result of $user-user['level'], for instance.
  modifyUser
    This method takes an associative array of parameters to new values.  You can even change the username.  You must specify the user id (obviously) but all other parameters are optional.  What you don't specify stays the same.
  deleteUser
    Takes a user id to delete.
  commitChanges
    If you call any of the modifying methods (addUser, modifyUser, deleteUser) and set autoCommit to false, you need to call commitChanges() to process all the filed commits at once.  Note that internally $autoCommit=true calls commitChanges, so if at any point you call a modifying method with $autoCommit set to true, all earlier uncommitted modifications will also be submitted.

--------------------------------------------------------------------------------

ADVANCED USAGE
  You can extend either UserManager class to enable more custom behavior, including new user levels, custom header and footer behavior, and more.  It is also possible to extend UserManager itself to implement a new backend.  How to do this is not presently documented, but replicating the contents of either XMLUserManager or MySQLUserManager with your backend of choice (JSONUserManager, for instance) will get you most of the way there.

NEW PERMISSION LEVELS AND ADDITIONAL FIELDS
  If you wish to create additional user levels, you must introduce a new constant, such as 'const SUBSCRIBER = 3;' make the name descriptive, and make the value some integer to represent its rank.  Larger numbers have higher permissions.  Note that 0 is a guest, a normal user is 2, a super user is 4, and an administrator is 10.  In addition to creating a new constant, you will want to add this value to the levels array in your constructor:
  
  ----------
  $this->levels['SUBSCRIBER'] = self::SUBSCRIBER;
  ----------
  
  Another way to extend UserManager is to introduce new attributes for the XML document / DB to store.  Do this by appending an array of the attributes you want to add to the userFields array, for instance:
  ----------
  $this->userFields = array('name', 'email', 'gender', 'ip');
  ----------
  
  MAKE SURE you call the parent constructor and pass it the file you want to reference, even if you don't want to extend either the user levels or the user attributes.
  
EXTENDING HEADER AND FOOTER
  Extending the header and footer will allow you to display your own template when using the require_login() and manageUsers() functions.  Simply insert your own template header and footer code into the respective methods.  For instance, to work with the template system I normally use, I do the following:
  ----------
  function header($title)
  {
    global $template;
    $template->header($title);
  }
  function footer()
  {
    global $template;
    $template->footer();
  }
  ----------

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
1.5.x - MySQLUserManager released
  1.5.0 - Major robustness improvements, UserManager compartmentalized and MySQLUserManager created
1.0.x - Initial UserManager
  1.0.2 - Minor Feature Improvements
  1.0.1 - Bug Fixes
  1.0.0 - Initial Release
