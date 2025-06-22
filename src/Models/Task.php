<?php

namespace PhpTodoTxt\Models;

use PhpTodoTxt\TodoTxt;

/**
 * A single task on a task list.
 *
 * Follows the simple schema defined by the t​odo.txt project.
 *
 * Can be used individually, but usually it makes more sense to parse an entire task list.
 *
 * @see TodoTxt
 */
class Task {

    private bool $done = false;
    private string $text = '';
    private ?string $priority = null;
    private ?string $creationDate = null;
    private ?string $completionDate = null;
    private array $projects = [];
    private array $contexts = [];
    private array $meta = [];

    public function isDone(): bool {
        return $this->done;
    }

    public function setDone(bool $done): Task {
        $this->done = $done;
        return $this;
    }

    public function done(): Task {
        $this->setDone(true);
        $this->setCompletionDate(date('Y-m-d'));
        return $this;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getCleanText(): string {
        $tokens = explode(' ', $this->text);
        while (count($tokens) > 0) {
            $t = $tokens[count($tokens) - 1];
            if ((mb_substr($t, 0, 1) !== '+') &&
                (mb_substr($t, 0, 1) !== '@') &&
                (!preg_match('#^[^ ]+:[^ ]+$#', $t))) {
                return join(' ', $tokens);
            }
            array_pop($tokens);
        }
        return $this->text;
    }

    public function setText(string $text): Task {
        $this->text = $text;
        return $this;
    }

    public function getPriority(): ?string {
        return $this->priority;
    }

    public function setPriority(string $priority): Task {
        $this->priority = $priority;
        return $this;
    }

    public function getCreationDate(): ?string {
        return $this->creationDate;
    }

    public function setCreationDate(?string $creationDate): Task {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getCompletionDate(): ?string {
        return $this->completionDate;
    }

    public function setCompletionDate(?string $date): Task {
        $this->completionDate = $date;
        return $this;
    }

    public function getProjects(): array {
        return $this->projects;
    }

    public function addProject(string $project): Task {
        if (!in_array($project, $this->projects)) {
            $this->projects[] = $this->sanitizeTag($project);
        }
        return $this;
    }

    public function getContexts(): array {
        return $this->contexts;
    }

    public function addContext(string $context): Task {
        if (!in_array($context, $this->contexts)) {
            $this->contexts[] = $this->sanitizeTag($context);
        }
        return $this;
    }

    public function getMeta(): array {
        return $this->meta;
    }

    public function addMeta(string $key, string $value): Task {
        $this->meta[$this->sanitizeTag($key)] = $this->sanitizeTag($value);
        return $this;
    }

    public function __toString(): string {
        return $this->asTodoTxtString();
    }

    public function asTodoTxtString(): string {
        $tokens = [];
        if ($this->isDone()) $tokens[] = 'x';
        if (!$this->isDone() && !is_null($this->getPriority())) $tokens[] = sprintf('(%s)', $this->getPriority());
        if (!is_null($this->getCompletionDate())) $tokens[] = $this->getCompletionDate();
        if (!is_null($this->getCreationDate())) $tokens[] = $this->getCreationDate();
        $tokens[] = $this->getText();
        foreach ($this->projects as $project) {
            if (!mb_strstr($this->getText(), "+{$project}")) {
                $tokens[] = sprintf('+%s', $project);
            }
        }
        foreach ($this->contexts as $context) {
            if (!mb_strstr($this->getText(), "@{$context}")) {
                $tokens[] = sprintf('@%s', $context);
            }
        }
        foreach ($this->meta as $key => $value) {
            if (!mb_strstr($this->getText(), "{$key}:{$value}")) {
                $tokens[] = sprintf('%s:%s', $key, $value);
            }
        }
        if ($this->isDone() && !is_null($this->getPriority())) $tokens[] = sprintf('prio:%s', $this->getPriority());
        return join(' ', $tokens);
    }

    public static function fromString(string $line): Task {
        $tokens = explode(' ', trim($line));
        $todo = new Task();
        if ($tokens[0] === 'x') {
            $todo->setDone(true);
            array_shift($tokens);
        }
        if (preg_match('#^\(([A-Z])\)$#', $tokens[0], $priority)) {
            $todo->setPriority($priority[1]);
            array_shift($tokens);
        }
        if (preg_match('#^(\d{4}-\d\d-\d\d)$#', $tokens[0], $date)) {
            if ($todo->isDone()) {
                $todo->setCompletionDate($date[0]);
            } else {
                $todo->setCreationDate($date[0]);
            }
            array_shift($tokens);
        }
        if ($todo->isDone() && preg_match('#^(\d{4}-\d\d-\d\d)$#', $tokens[0], $date)) {
            $todo->setCreationDate($date[0]);
            array_shift($tokens);
        }
        return self::parseTodoText($tokens, $todo);
    }

    private static function parseTodoText(array $tokens, Task $todo): Task {
        $text = [];
        foreach ($tokens as $token) {
            if (mb_substr($token, 0, 1) === '+') {
                $todo->addProject(mb_substr($token, 1));
            } else if (mb_substr($token, 0, 1) === '@') {
                $todo->addContext(mb_substr($token, 1));
            } else if (preg_match('#^([^ ]+):([^ ]+)$#', $token, $matches)) {
                $todo->addMeta($matches[1], $matches[2]);
            }
            $text[] = $token;
        }
        $todo->setText(join(' ', $text));
        return $todo;
    }

    private function sanitizeTag(string $tag): string {
        $result = '';
        foreach (preg_split('#\s+#', trim($tag), -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (empty($result)) {
                $result .= ($token);
            } else {
                $firstLetter = mb_convert_case($token[0], MB_CASE_UPPER);
                $tail = mb_convert_case(mb_substr($token, 1),MB_CASE_LOWER);
                $result .= $firstLetter . $tail;
            }
        }
        return $result;
    }
}
