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

Create an object of the main class:

    <?php
    require 'vendor/autoload.php';

    $todo = new PhpTodoTxt\TodoTxt();

List all tasks:

    foreach ($todo->list() as $task) {
       echo $task->getText();
       echo $task->isDone() ? 'Done' : 'TODO';
    }

## Known Issues

* At the moment it is expected that a `todo.txt` is in the same folder. In future versions this will be a config parameter.

## License

This code is offered open-source under the [GPLv3 license](LICENSE).
