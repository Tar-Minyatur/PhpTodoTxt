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
class TodoTxt implements \Countable, \Iterator, \ArrayAccess {

    private array $tasks = [];
    private int $currentIndex = 0;

    public function __construct() {
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
     * @param \SplFileInfo|string $file The file (or path of the file) to read tasks from
     * @return TodoTxt Object representing the entire task list
     */
    public static function readFromFile(\SplFileInfo|string $file): TodoTxt {
        if (is_string($file)) {
            $file = new \SplFileInfo($file);
        }
        if (!$file->isReadable()) {
            $filePath = sprintf('%s%s%s', $file->getPath(), DIRECTORY_SEPARATOR, $file->getBasename());
            throw new \RuntimeException("File {$filePath} is not readable!");
        }
        $reader = $file->openFile('r');
        $todos = new TodoTxt();
        foreach ($reader as $line) {
            if (strlen(trim($line)) === 0) {
                continue;
            }
            $todos->addTask(Task::fromString($line));
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
     * @param \SplFileInfo|string $file The file to write all tasks to
     * @return int Number of lines written
     */
    public function writeToFile(\SplFileInfo|string $file): int {
        if (is_string($file)) {
            $file = new \SplFileInfo($file);
        }
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
     * Retrieve tasks as string array, e.g. to store it in a database.
     *
     * @return string[] Individual lines, each representing a task
     */
    public function toStringArray(): array {
        return array_map(function ($task) {
            return strval($task);
        }, $this->getTasks());
    }

    /**
     * Read tasks into a new task list from a string array, e.g. one retrieved from a database.
     *
     * @param string[] $tasks
     * @return TodoTxt
     */
    public static function fromStringArray(array $tasks): TodoTxt {
        $todos = new TodoTxt();
        foreach ($tasks as $task) {
            $todos->addTask(Task::fromString($task));
        }
        return $todos;
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
        return array_key_exists($index, $this->tasks) ? $this->tasks[$index] : null;
    }

    /**
     * Add a new task to the list.
     * @param Task $task Task to add
     */
    public function addTask(Task $task, bool $setCreationDate = true): void {
        $newIndex = empty($this->tasks) ? 0 : (array_key_last($this->tasks) + 1);
        $this->tasks[$newIndex] = $task;
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
            unset($this->tasks[$index]);
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
        if (array_key_exists($index, $this->tasks)) {
            $task = $this->get($index);
            unset($this->tasks[$index]);
            if ($this->currentIndex === $index) {
                $this->next();
            }
            return $task;
        } else {
            return null;
        }
    }

    public function moveTask(Task $task, int $targetIndex): void {
        $oldIndex = array_search($task, $this->tasks, true);
        $taskInTheWay = $this[$targetIndex];
        $this[$targetIndex] = $task;
        unset($this->tasks[$oldIndex]);
        for ($i = $targetIndex + 1; !is_null($taskInTheWay); $i += 1) {
            if (is_null($this[$i])) {
                $this[$i] = $taskInTheWay;
                $taskInTheWay = null;
            } else {
                $tempTask = $this[$i];
                $this[$i] = $taskInTheWay;
                $taskInTheWay = $tempTask;
            }
        }
    }

    /**
     * @inheritDoc
     * @return int Number of contained tasks
     */
    public function count(): int {
        return count($this->tasks);
    }

    public function next(): void {
        $keys = array_keys($this->tasks);
        $index = array_search($this->currentIndex, $keys, true) + 1;
        $this->currentIndex = array_key_exists($index, $keys) ? $keys[$index] : $this->currentIndex += 1;
    }

    public function key(): ?int {
        return $this->currentIndex;
    }

    public function valid(): bool {
        return array_key_exists($this->currentIndex, $this->tasks);
    }

    public function rewind(): void {
        $this->currentIndex = array_key_first($this->tasks);
    }

    public function current(): ?Task{
        return $this->valid() ? $this->tasks[$this->currentIndex] : null;
    }

    public function offsetExists(mixed $offset): bool {
       return array_key_exists($offset, $this->tasks);
    }

    public function offsetGet(mixed $offset): ?Task {
        return $this->offsetExists($offset) ? $this->tasks[$offset] : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (!($value instanceof Task)) {
            throw new \InvalidArgumentException('$value must be an instance of Task');
        }
        $this->tasks[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        if ($this->offsetExists($offset)) {
            unset($this->tasks[$offset]);
        }
    }
}
