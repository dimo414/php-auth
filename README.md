# UserManager

*Version 1.5.0*

The UserManager class is a self-contained user management system, written in PHP. Include the class
in your website, and you have with a few short lines of code a fully operational login system,
including multiple user levels (default User, SuperUser, and Admin) and the ability to lock web
pages from users without appropriate permissions - similar to .htaccess and .htpassword locking,
except integrated with your website rather than a series of intrusive 401 login prompts.
	
There are two versions, `XMLUserManager` and `MySQLUserManager`, both of which extend `UserManager`,
and allow you to chose where your user information is stored.

`XMLUserManager` is the original tool, providing user management in a lightweight XML file, without
requiring a database or any other configuration. `MySQLUserManager` is a newer variant which is
backed by a database and therefore supports larger numbers of users, and is ideal if your site is
already using a MySQL database.  Both classes implement the same interface therefore switching from
one to the other is as easy as changing which class you initialize.

## Requirements

`XMLUserManager` is functional with a default install of PHP5, a MySQL database is required to use `MySQLUserManager`, and the [`mysqli` database driver](http://php.net/manual/en/book.mysqli.php) is
used to communicate with the database.

Primarily tested with [Uniform Server](http://www.uniformserver.com/).

## Features

* XML Backend: no database or setup required.
* MySQL Backend: easy to setup, scales well.
* Customizable user permission levels.
* Customizable storage fields.
* Integrates seamlessly with your website.
* One function call locks a page to anyone without the proper permission.
* Self contained user management page.
* Add and maintain users yourself, or use the pre-built manager.

### Potential Features

* `ManageUsers` should be divided into separate pages in order to deal with larger user bases.
  
## Usage

This section describes how to use the class right out of the box.  As is, `UserManager` has four
user levels, Guest, User, Superuser, and Admin, and stores each user's username, password
(encrypted), and level. In addition to this documentation the public functions in the `UserManager`
provide more specific implementation details.

### Initialization

UserManager uses PHP Sessions to store the current visitor's information.  So before anything else,
you must call `session_start()`.  Then create an instance of either the `XMLUserManager` class by
passing the constructor the location of the XML file, or the `MySQLUserManager` class by passing a
`mysqli` database connection, and the name of a table the class should work with.  If using the
`XMLUserManager` be sure to specify a location outside the web root or hidden from public view. Even
though passwords are encrypted the XML file should not be world-readable.
	
```
include 'XMLUserManager.class.php';
session_start();
$user = new XMLUserManager('users.xml');
```

```
include 'MySQLUserManager.class.php';
session_start();
$db = new mysqli(HOST,USER,PASS,DB);
$user = new MySQLUserManager($db);
```
	
That's all it takes to initialize the UserManager.  You should generally place these setup
commands in a common include file. For the remainder of this document, it will be assumed that
calling `include 'common.inc.php';` will call the lines of code above.

#### Database Setup

Using `MySQLUserManager` requires slightly more setup than `XMLUserManager`, but it is still quite
straightforward.  The first time you construct the `UserManager` you will need to create the
user table. `MySQLUserManager` can generate the necessary `CREATE TABLE` query, which you can either
print and run manually, or have it invoke directly if the database connection has the appropriate
permissions.
	
If the user on the connection passed to `MySQLUserManager` has `CREATE` permission you can call
`createTable()`, this will execute the create command and construct the necessary table. Otherwise
you can call `echo createTableString();` to get the schema and invoke it yourself.
	
`MySQLUserManager` cannot predict what data you may want to search by. Looking up by username and id
are fast with the default command, but if you extend the class (more below) be aware that
`lookupAttribute()` is only efficient when run against properly indexed data. Therefore you should
modify the table or the `CREATE` instruction as necessary - a decent rule of thumb would be to index
any column you intend to search by, but `MySQLUserManager` does not eliminate the need for good
database layout.
  
### Creating Users

There are two different ways to create and manage your website's users. The first is through the
built-in `manageUsers()` UI and the second is manually, with `addUser()`, `modifyUser()`, and
`deleteUser()`.
  
The easiest way is to use the `manageUsers()` function, which generates a user-management page.
Place the following in a file (`admin.php` or something similar):

```
<?php
include 'common.inc.php';
// IMPORTANT - set this to false once you've created an administrator account
// so that only admins can manage users. When true the manageUsers UI is
// accessible to all visitors.
$user->manageUsers(true);
?>
```
  
This will generate a form to modify existing users and add additional users. If you don't have any
users created already you will just see three empty slots to create new users. The `true` parameter
passed to `manageUsers()` above overrides the protection built into the function - normally if you
are not logged in as an Administrator, you will not be able to access the user management page.
Since you haven't created any administrator accounts yet you need to override this lock.

IMPORTANT: make sure to remove the parameter to `manageUsers()` before a live launch of your site.
If override is not set, `manageUsers()` is safe from unauthorized access.
  
The second way to work with users is manually.  This would primarily be used if you have your own
register page for users to register themselves.
  
By calling `addUser("USERNAME", "PASSWORD", UserManager::ADMIN, array());` where USERNAME and
PASSWORD are your desired username and password, you will create a new user with ADMIN level
permissions.  (the other default user levels are `UserManager::USER` and `UserManager::SUPERUSER`.  `UserManager::GUEST` also exists, but should not be used to create a user).  `addUser()` can be
integrated with your own registration page by doing any error checks you want to make and then
passing the registration information straight to `addUser()`.  addUser() will return false if the
username already exists, and will otherwise register the user.

For `XMLUserManager`, if you plan on calling `addUser()` more than once per execution add an
additional parameter, `false`, to the function call.  This disables writing the values to the file
until you call `commitDOMChanges()`, which will write all changes back to the file, which is much
faster than writing and rebuilding the DOM model with every addition.

### Login and Checking Permissions

The `require_login()` function can be invoked the top of any page, and only users logged in (with a
certain permission level, if you pass one to the function) will be able to view the contents of the
page. If the visitor is already logged in they will notice no interruption of service, and will not
need to login again.
  
The default UserManager class has a `header()` and a `footer()` function which are called
by `require_login()` (and for that matter `manageUsers()`) which will be used to generate an HTML
login page. The default behavior of these methods is a bare-bones page with no styling. See the
"Extending Header and Footer" section below to change this behavior to match your site's look and
feel, or avoid `require_login()` and create your own login behavior instead.

The `login()` function takes a username and password and returns a boolean. If true, the credentials
were correct and the user is now logged in, if false one of the given credentials was incorrect.
Note that it is a security feature, and not a bug, that *which* credential was incorrect is not
specified. You can create your own login page or form and pass the result to the `login()` method.
  
You may want to output certain content only to logged in users (for instance, perhaps only logged in
users should see a link to post a new topic). For this functionality, use `hasPerm()`. The default
parameter is `UserManager::USER`, but if you want to be more restrictive, for instance displaying a
link to the admin panel only to administrators, simply pass a different user level to the function.
The function returns a boolean if the user has permission or not.  Example:

```
if($_user->hasPerm(UserManager::ADMIN)) {
  echo '<a href="admin.php">Manage Users</a>';
}
```
  
### Get Information About the Current User

The current user's ID, username, and level are stored in the session and are immediately available
to every webpage. This information is stored in the `$user` array. If you need additional
information about the current user, you must call `loadCurUser()`. This will populate the array with
all other information on the current user.
  
### Method Overview

* `logout()`

    Call logout() and the current user will be logged out and reduced to GUEST permission level.

* `loadCurUser()`

    To save reads the current user's extended data is not loaded unless requested. The ID,
	Username, and Level are available in the `$user->user` array immediately. If you need the
	extended data, such as any custom attributes being stored, call `loadCurUser()` to populate the
	`$user->user` array with all the current user's information.

* `getUser()`

    To get information on a user given an ID, you can call `$user->getUser($id)`. This will return
	an associative array of all the details of that user.

* `lookupAttribute()`

    To lookup users by anything other than their ID you can use lookupAttribute. It takes two
	parameters, the attribute to lookup by and a value to match. It will return an array of users
	(which are themselves associative arrays of all the user's attributes).

* `userLevel()`

    Returns the string version of a permission level, pass it the result of `$user-user['level']`,
	for instance.

* `modifyUser()`

    This method takes an associative array of parameters to new values. You can even change the
	username. You must specify the user id (obviously) but all other parameters are optional. What
	you don't specify stays the same.

* `deleteUser()`

    Takes a user id to delete.

* `commitChanges()`

    If you call any of the modifying methods (`addUser`, `modifyUser`, `deleteUser`) and have set 
	`autoCommit` to `false` you need to call `commitChanges()` to process all the buffered commits
	at once.  Note that internally when `autoCommit` is `true` modifications call `commitChanges()`,
	so if at any point you call a modifying method with `autoCommit` set to true all earlier
	uncommitted modifications will also be submitted.

## Advanced Usage

You can extend the `UserManager` subclasses to enable more custom behavior, including new user
levels, custom header and footer behavior, and more. It is also possible to extend `UserManager`
itself to integrate with a new backend. How to do this is not presently documented, but replicating
the contents of either `XMLUserManager` or `MySQLUserManager` with your backend of choice
(`JSONUserManager`, for instance) will get you most of the way there.

### New Permission Levels and Additional Fields

If you wish to create additional user levels, you must introduce a new constant, such as
`const SUBSCRIBER = 3;` make the name descriptive, and make the value some integer to represent its
rank. Larger numbers have higher permissions. Note that 0 is a guest, a normal user is 2, a super
user is 4, and an administrator is 10. In addition to creating a new constant, you will need to add
this value to the levels array in your constructor:
  
```
$this->levels['SUBSCRIBER'] = self::SUBSCRIBER;
```
  
You can also define permission groups, which do not have a logical hierarchy, but also are not
mutually exclusive. A user can only be one level, and automatically inherits the privileges of any
lower level, but can be a member of any number of groups. This is done by constructing a bitmasker
object (see `bitmasker.class.php` for more) with the names of the groups you would like to exist,
like so:
  
```
$this->groups = new bitmasker('author','editor','publisher');
```

Now you can specify a user's groups directly in `addUser()`, update them with `modifyUser()` (though
you have to directly use the bitmasker object in this case), and most importantly handle permission
checks with `hasPerm()` and `require_login()`.  For instance, to confirm a user is either an author
or a publisher, you can do the following:
  
```
$_user->hasPerm(UserManager::USER,array('author','publisher'));
```

Groups are stored as a bitmask, and as such you'll want to use the bitmask class to modify them or
do more advanced handling of user groups:
  
```
$_user->groups->getValue($_user->user['groups'],'author'); // returns true if user is an author
// sets editor field to true and returns modified mask.  Saving the modified mask is a second step.
$newGroup = $_user->groups->setValue($_user->user['groups'], 'editor', true);
// returns an array of the groups a user is in
$groups = $_user->groups->maskToArray($_user->user['groups']);
```

Another way to extend UserManager is to introduce new attributes for the XML document / DB to store.
Do this by appending an array of the attributes you want to add to the userFields array, for
instance:

```
$this->userFields = array('name', 'email', 'gender', 'ip');
```

**Make sure** you call the parent constructor and pass it the file you want to reference, even if
you don't want to extend either the user levels or the user attributes.
  
### Extending Header and Footer

Extending the `header()` and `footer()` methods will allow you to display your own template when
using the `require_login()` and `manageUsers()` functions. Simply insert your own template header
and footer code into the respective methods. For instance, to work with the template system I
normally use, I do the following:

```
function header($title) {
  global $template;
  $template->header($title);
}

function footer() {
  global $template;
  $template->footer();
}
```

## File Overview

* `UserManager.class.php` - The abstract parent class of both UserManager objects, handles all
  backend-abstract behavior.
* `XMLUserManager.class.php` - The XML-backed UserManager, include this file in your code and create
  an instance of it to use XML.
* `MySQLUserManager.class.php` - The MySQL-backed UserManager, include this file in your code and
  create an instance of it to use XML.
* `bitmasker.class.php` - A bitmap handling utility for storing and working with groups.
* `UserManager.Extended.class.php` - This is an example demonstrating one possible way to extend the
  `UserManager` to draw more out of the application.

## Version History

* 1.5 - `MySQLUserManager` released
  * 1.5.0 - Major robustness improvements, `UserManager` compartmentalized and `MySQLUserManager`
    created
* 1.0 - Initial `UserManager`
  * 1.0.2 - Minor Feature Improvements
  * 1.0.1 - Bug Fixes
  * 1.0.0 - Initial Release

## Copyright and License

©2010-2012 Michael Diamond

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
