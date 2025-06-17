<?php

namespace PhpTodoTxt\Models;

class Todo {

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

    public function setDone(bool $done): Todo {
        $this->done = $done;
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

    public function setText(string $text): Todo {
        $this->text = $text;
        return $this;
    }

    public function getPriority(): ?string {
        return $this->priority;
    }

    public function setPriority(string $priority): Todo {
        $this->priority = $priority;
        return $this;
    }

    public function getCreationDate(): ?string {
        return $this->creationDate;
    }

    public function setCreationDate(?string $creationDate): Todo {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getCompletionDate(): ?string {
        return $this->completionDate;
    }

    public function setCompletionDate(?string $date): Todo {
        $this->completionDate = $date;
        return $this;
    }

    public function getProjects(): array {
        return $this->projects;
    }

    public function addProject(string $project): Todo {
        if (!in_array($project, $this->projects)) {
            $this->projects[] = $project;
        }
        return $this;
    }

    public function getContexts(): array {
        return $this->contexts;
    }

    public function addContext(string $context): Todo {
        if (!in_array($context, $this->contexts)) {
            $this->contexts[] = $context;
        }
        return $this;
    }

    public function getMeta(): array {
        return $this->meta;
    }

    public function addMeta(string $key, string $value): Todo {
        $this->meta[$key] = $value;
        return $this;
    }

    public function __toString(): string {
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

    public static function fromString(string $line): Todo {
        $tokens = explode(' ', trim($line));
        $todo = new Todo();
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

    private static function parseTodoText(array $tokens, Todo $todo): Todo {
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
}
