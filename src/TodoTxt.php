<?php

namespace PhpTodoTxt;

use PhpTodoTxt\Models\Todo;

class TodoTxt {

    private string $todoFile = 'todo.txt';

    public function __construct() {
        // TODO: Parse config parameters
    }

    public function list(): array {
        $todos = [];
        foreach (file($this->todoFile) as $line) {
            if (empty($line)) {
                continue;
            }
            $todos[] = Todo::fromString($line);
        }
        return $todos;
    }

}
