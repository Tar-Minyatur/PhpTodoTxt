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
$todos->get(4)->done();
$todos->get(5)->addProject('myFancyProject'); 
```

Write tasks to a file:

```php
$todos->writeToFile(\SplFileInfo("todo.txt"));
```

## License

This code is offered open-source under the [GPLv3 license](LICENSE).
