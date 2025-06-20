<?php

namespace PhpTodoTxt;

use ArrayIterator;
use PhpTodoTxt\Models\Task;

/**
 * A task list.
 *
 * Tasks can be accessed via {@see \Iterator} or direct access using {@see TodoTxt::getTasks()}.
 * Individual tasks are accessible via {@see TodoTxt::get()}.
 *
 * Example:
 * <code>
 *     $file = new \SplFileInfo("todo.txt");
 *     $todos = \PhpTodoTxt\TodoTxt::readFromFile($file);
 *     foreach ($todos as $todo) {
 *         echo $todo->getName();
 *     }
 * </code>
 *
 * @see Models\Task
 */
class TodoTxt extends ArrayIterator implements \Countable {

    private array $tasks = [];

    public function __construct() {
        parent::__construct($this->tasks);
    }

    /**
     * Read a task list from a text file.
     *
     * Example:
     * <code>
     *     $file = new \SplFileInfo("todo.txt");
     *     $todos = \PhpTodoTxt\TodoTxt::readFromFile($file);
     * </code>
     *
     * @param \SplFileInfo $file The file to read tasks from
     * @return TodoTxt Object representing the entire task list
     */
    public static function readFromFile(\SplFileInfo $file): TodoTxt {
        if (!$file->isReadable()) {
            throw new \RuntimeException("File {$file->getBasename()} is not readable!");
        }
        $reader = $file->openFile('r');
        $todos = new TodoTxt();
        while ($line = $reader->fgets()) {
            if (strlen(trim($line)) === 0) {
                continue;
            }
            $todos->append(Task::fromString($line));
        }
        return $todos;
    }

    /**
     * Write a task list to a text file.
     *
     * <b>This will overwrite all content in the file!</b>
     *
     * Example:
     * <code>
     *     $todos = new \PhpTodoTxt\TodoTxt();
     *     // add some tasks here or read them from a file
     *     $todos->writeToFile(new \SplFileInfo("todo.txt"));
     * </code>
     *
     * @param \SplFileInfo $file The file to write all tasks to
     * @return int Number of lines written
     */
    public function writeToFile(\SplFileInfo $file): int {
        if (!$file->isWritable()) {
            throw new \RuntimeException("File {$file->getBasename()} is not writeable!");
        }
        $writer = $file->openFile('w');
        $lines = 0;
        foreach ($this as $todo) {
            $writer->fwrite("{$todo}\n");
            $lines += 1;
        }
        $writer->fflush();
        return $lines;
    }

    /**
     * Retrieve all tasks from this task list.
     * @return Task[] All tasks
     */
    public function getTasks(): array {
        return $this->tasks;
    }

    /**
     * Retrieve one specific task from this task list.
     * @param int $index
     * @return Task|null The task at the given position or `null` if `index` is unknown
     */
    public function get(int $index): ?Task {
        return $this->offsetExists($index) ? $this->offsetGet($index) : null;
    }

    /**
     * Add a new task to the list.
     * @param Task $task Task to add
     */
    public function addTask(Task $task): void {
        $this->append($task);
    }

    /**
     * Remove a ask from the task list.
     * @param Task $task The task to remove
     * @return bool `true` if the task was found and removed, `false` otherwise
     */
    public function removeTask(Task $task): bool {
        $index = array_search($task, $this->tasks, true);
        if ($index === false) {
            return false;
        } else {
            $this->offsetUnset($index);
            return true;
        }
    }

    /**
     * Delete (and return) the task at a specific index.
     *
     * @param int $index The index of the Task to be deleted
     * @return Task|null The removed task or `null` if the index was invalid
     */
    public function removeTaskByIndex(int $index): ?Task {
        if (!$this->offsetExists($index)) {
            return null;
        }
        $task = $this->offsetGet($index);
        $this->offsetUnset($index);
        return $task;
    }

    /**
     * @inheritDoc
     * @return Task|null Current task or `null` if the current position is invalid
     * @see TodoTxt::valid()
     */
    public function current(): ?Task {
        $task = parent::current();
        return ($task instanceof Task) ? $task : null;
    }

    /**
     * @inheritDoc
     * @return int Number of contained tasks
     */
    public function count(): int {
        return count($this->tasks);
    }
}
