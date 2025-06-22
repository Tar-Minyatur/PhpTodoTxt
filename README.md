# PHPTodoTxt [![Run Test [Composer]](https://github.com/Tar-Minyatur/PhpTodoTxt/actions/workflows/php.yml/badge.svg)](https://github.com/Tar-Minyatur/PhpTodoTxt/actions/workflows/php.yml)

PHP library for the [todo.txt](http://todotxt.org/) unstructured todo list format.

## Features

* Robustly parses the todo.txt
* Provides an easy-to-use model to access and modify tasks
* Task description is available als "clean" version without trailing tags
* Projects, contexts and meta data are available separately as arrays

## Usage

Use composer to add as dependency:

    $ composer require tshw/php-todo-txt

> [!WARNING]
> The library is currently not yet published, so you'll have to add it manually for now.

Create an object of the main class by reading tasks from a file:

```php
<?php
require 'vendor/autoload.php';

$todos = \PhpTodoTxt\TodoTxt::readFromFile(new \SplFileInfo("todo.txt"));
// or if don't want to think about the SPL
$todos = \PhpTodoTxt\TodoTxt::readFromFile("todo.txt");
```

List all tasks:

```php
foreach ($todos as $task) {
   echo $task->getText();
   echo $task->isDone() ? 'Done' : 'TODO';
}
```

Modify tasks:

```php
$todos[4]->done();
$todos[5]->addProject('myFancyProject'); 
```

Write tasks to a file:

```php
$todos->writeToFile(\SplFileInfo("todo.txt"));
```

Add task:

```php
$task = (new Task("Another task", "F"));
$todos->addTask($task);
```

Move task within the list (this displaces tasks further down in the list, changing their keys!):

```php
$todos->moveTask($task, 2);
```

## FAQ

### Can I use PHPTodoTxt to store tasks in a database?

Absolutely. For now, the easiest way is to use `toStringArray()` and `fromStringArray()` and then you have to implement
storing and reading to/from the database yourself:

```php
$tasks = readTasksFromDatabase();  // must return an array of strings, one task per line
$todos = TodoTxt::fromStringArray($tasks);
// do whatever with the list
$tasks = $todos->toStringArray();
writeTasksToDatabase($tasks);
```

## License

This code is offered open-source under the [GPLv3 license](LICENSE).
