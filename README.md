# E_mysqli

Replacement for default Mysqli class to allow viewing a parameterized query with the arguments inserted into the string

View the [changelog](CHANGELOG.md)

## Usage
Provides similar functionality to that found in the PDO sister project:

[E_PDOStatement](https://github.com/noahheck/E_PDOStatement)

Not being able to view a complete version of the query to be executed on the server after statement parameters have been interpolated can be frustrating.

__EMysqli__ aims to ease this burden by providing developers the ability to view what would be an example of the query executed on the server:

```php
$query 		= "INSERT INTO registration SET name = ?, email = ?";
$stmt 		= $mysqli->prepare($query);

$name 		= $_POST['name'];
$email 		= $_POST['email'];

$stmt->bind_param("ss", $name, $email);

$stmt->execute();

echo $stmt->fullQuery;

```

The result of this will be:
```sql
INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'
```

When used correctly, the interpolated values are escaped appropriately according to character set in use on the database server:

```sql
INSERT INTO registration SET name = 'Sue O\'Reilly', email = 'sue.o@example.com'
```

It's also possible to view the interpolated query string without executing the query:

```php
$query 		= "INSERT INTO registration SET name = ?, email = ?";
$stmt 		= $mysqli->prepare($query);

$name 		= $_POST['name'];
$email 		= $_POST['email'];

$stmt->bind_param("ss", $name, $email);

$fullQuery 	= $stmt->interpolateQuery();// INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'
```

## Further Enhancements

A (fortunate ?) side effect of the way __EMysqli__ performs it's work also allows you to bind multiple parameters individually, helpful if your query string is generated in separate method/function calls.

This is accomplished by binding the parameters individually:

```php
$name 		= $_POST['name'];
$email 		= $_POST['email'];

$stmt->bind_param("s", $name);
$stmt->bind_param("s", $email);
```

#### Note
Using either of these two methods stores the bound parameters as references to their runtime variables preventing the need to rebind parameters, which is the default method for handling bound parameters in mysqli:

```php
$name 		= "John Doe";
$email 		= "john.doe@example.com";

$stmt->bindParam("s", $name);
$stmt->bindParam("s", $email);

$stmt->execute(); // INSERT INTO registration SET name = 'John Doe', email = 'john.doe@example.com'

$name 		= "Sue O'Reilly";
$email 		= "sue.o@example.com";

$stmt->execute(); // INSERT INTO registration SET name = 'Sue O\'Reilly', email = 'sue.o@example.com'
```

## Installation
Install via composer:

```
composer require noahheck/e_mysqli
```

## Configuration
__E_mysqli__ extends both the `mysqli` and `mysqli_stmt` classes. Your `mysqli` object creation process will simply need to be updated to generate an instance of `EMysqli\EMysqli` instead:

```php
<?php

require_once "path/to/vendor/autoload.php";

$mysqli 	= new EMysqli\EMysqli($dbHost, $dbUser, $dbPassword, $dbName);

```

That's all there is to it. Your `$mysqli` object should function the same as it has.

## Feedback Request

The [E_PDOStatement](https://github.com/noahheck/E_PDOStatement) project has received some good feedback, and a common request was to offer the same or similar functionality to users still using mysqli. Though I have no practical experience using the `myslqi` extension, I have researched how this might be possible and this is what I have managed to come up with.

As I have no production quality application code to test this extension on, any feedback regarding performance in a production setting would be appreciated. Bugs, new feature requests and pull requests are of course welcome.
