#E_mysqli

Drop in replacement for default mysqli class allowing devs to view an interpolated version of 
a parameterized query

##Usage
Provides similar functionality to that found in the PDO sister project:

[E_PDOStatement](https://github.com/noahheck/E_PDOStatement)

Not being able to view a complete version of the query to be executed on the server after 
statement parameters have been interpolated can be frustrating.

__E_mysqli__ aims to ease this burden by providing developers the ability to view what would 
be an example of the query executed on the server:

```php
$query 	= "INSERT INTO registration SET name = ?, email = ?";
$stmt 	 = $mysqli->prepare($query);

$name 	 = $_POST['name'];
$email 	= $_POST['email'];

$stmt->bind_param("ss", $name, $email);

$stmt->execute();

echo $stmt->fullQuery;

```

The result of this will be:
```sql
INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'
```

When used correctly, the interpolated values are escaped appropriately according to character 
set in use on the database server:

```sql
INSERT INTO registration SET name = 'Sue O\'Reilly', email = 'sue.o@example.com'
```

It's also possible to view the interpolated query string without executing the query:

```php
$query 	= "INSERT INTO registration SET name = ?, email = ?";
$stmt 	 = $mysqli->prepare($query);

$name 	 = $_POST['name'];
$email 	= $_POST['email'];

$stmt->bind_param("ss", $name, $email);

$fullQuery 	= $stmt->interpolateQuery();// INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'
```

##Further Enhancements

Using __E_mysqli__ also allows you to bind multiple parameters individually, helpful if your 
query string is generated in separate method/function calls.

This is accomplished by binding the parameters individually:

```php
$name 	 = $_POST['name'];
$email 	= $_POST['email'];

$stmt->bind_param("s", $name);
$stmt->bind_param("s", $email);
```

or as an array:

```php
$params = array();
$params[] = $_POST['name'];
$params[] = $_POST['email'];

$stmt->bind_param("ss", $params);
```
####Note
Using either of these two methods stores the bound parameters as references to their runtime
variables preventing the need to rebind parameters, which is the default method for handling 
bound parameters in mysqli:

```php
$name 	= "John Doe";
$email 	= "john.doe@example.com";

$stmt->bindParam("s", $name);
$stmt->bindParam("s", $email);

$stmt->execute(); // INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'

$name 	= "Sue O'Reilly";
$email 	= "sue.o@example.com";

$stmt->execute(); // INSERT INTO registration SET name = 'Sue O\'Reilly', email = 'sue.o@example.com'
```

The default functionality of mysqli_stmt::bind_param in which all parameters are passed by 
reference is not possible (yet) in order to store a local reference of the bound parameter, 
allowing the value to be interpolated into the query string:

```php
$name 	= "John Doe";
$email 	= "john.doe@example.com";

$stmt->bindParam("s", $name, $email);

$stmt->execute(); // INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'

$name 	= "Sue O'Reilly";
$email 	= "sue.o@example.com";

$stmt->execute(); // INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'
```
In order to accomodate a variable number of function arguments, the
[func_get_args()](http://www.php.net/func_get_args) method is used, which doesn't support 
variable access by reference.

When php 5.6 is released (currently in beta/RC status), variable access by reference will be
possible in variable argument functions via the `...`token/splat operator. For more 
information, see the [manual page](http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list).

##Installation
Download the file...put it into a suitable location in your application directory.

##Configuration
__E_mysqli__ extends both the `mysqli` and `mysqli_stmt` classes, both of which are included. Your `mysqli` object creation process will need to be updated to generate an instance of `E_mysqli` instead:

```php
<?php

require_once "E_mysqli.php";

$mysqli 	= new E_mysqli($dbHost, $dbUser, $dbPassword, $dbName);

?>
```

That's all there is to it. Your `$mysqli` object should function the same as it has (aside 
from the variables by reference issue noted above). 

##Feedback Request

The [E_PDOStatement](https://github.com/noahheck/E_PDOStatement) project has received some 
good feedback, and a common request was to offer the same or similar functionality to users 
still using mysqli. Though I have no practical experience using the `myslqi` extension, in an 
effort to help expand the adoption of more secure processes (particularly for those still 
using the `mysql` extension) and acceptance of object oriented programming in PHP, I have 
researched how this might be possible and this is what I have managed to come up with.

As I have no production quality application code to test this extension on, any feedback 
regarding performance in a production setting would be appreciated. Bugs, new feature 
requests and pull requests are of course welcome.