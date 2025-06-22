<?php
namespace PhpTodoTxt\Models;

use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase {

    public function testJustTextToString() {
        $todo = $this->givenTodo("Test");
        $this->thenStringIs("Test", $todo);
    }

    public function testDoneToString() {
        $todo = $this->givenTodo("Test", true);
        $this->thenStringIs("x Test", $todo);
    }

    public function testWithPriority() {
        $todo = $this->givenTodo("Test")->setPriority('Y');
        $this->thenStringIs("(Y) Test", $todo);
    }

    public function testDoneWithPriority() {
        $todo = $this->givenTodo("Test", true, "2025-01-01")->setPriority('Y');
        $this->thenStringIs("x 2025-01-01 Test prio:Y", $todo);
    }

    public function testWithCreationDateToString() {
        $todo = $this->givenTodo("Test", false, "2025-01-01");
        $this->thenStringIs("2025-01-01 Test", $todo);
    }

    public function testDoneWithCompletionDateToString() {
        $todo = $this->givenTodo("Test", true, null, "2025-01-01");
        $this->thenStringIs("x 2025-01-01 Test", $todo);
    }

    public function testDoneWithBothDatesToString() {
        $todo = $this->givenTodo("Test", true, "2025-02-01", "2025-01-01");
        $this->thenStringIs("x 2025-01-01 2025-02-01 Test", $todo);
    }

    public function testWithProjectsToString() {
        $todo = $this->givenTodo("Test")->addProject("TestProject");
        $this->thenStringIs("Test +TestProject", $todo);
        $todo->addProject("AnotherProject");
        $this->thenStringIs("Test +TestProject +AnotherProject", $todo);
    }

    public function testWithContextsToString() {
        $todo = $this->givenTodo("Test")->addContext("Home");
        $this->thenStringIs("Test @Home", $todo);
        $todo->addContext("work");
        $this->thenStringIs("Test @Home @work", $todo);
    }

    public function testWithMetasToString() {
        $todo = $this->givenTodo("Test")->addMeta("color", "red");
        $this->thenStringIs("Test color:red", $todo);
        $todo->addMeta("test", "yes");
        $this->thenStringIs("Test color:red test:yes", $todo);
    }

    public function testComplexToString() {
        $todo = $this->givenTodo("Some longer text", true, "2025-01-01", "2025-02-01")
            ->setPriority('Y')
            ->addProject("SomeProject")
            ->addContext("SomeContext")
            ->addMeta("meta", "no");
        $this->thenStringIs("x 2025-02-01 2025-01-01 Some longer text +SomeProject @SomeContext meta:no prio:Y", $todo);
    }

    public function testParseSimple() {
        $todo = Task::fromString("Test");
        $this->thenTodoHas($todo, "Test", false, null, null, null, 0, 0, 0);
    }

    public function testLongText() {
        $todo = Task::fromString("Lorem ipsum dolor sit amet");
        $this->thenTodoHas($todo, "Lorem ipsum dolor sit amet", false, null, null, null, 0, 0, 0);
    }

    public function testParseDone() {
        $todo = Task::fromString("x Test");
        $this->thenTodoHas($todo, "Test", true, null, null, null, 0, 0, 0);
    }

    public function testParsePriority() {
        $todo = Task::fromString("(Y) Test");
        $this->thenTodoHas($todo, "Test", false, 'Y',null, null, 0, 0, 0);
    }

    public function testParseCreationDate() {
        $todo = Task::fromString("2025-01-01 Test");
        $this->thenTodoHas($todo, "Test", false, null,'2025-01-01', null, 0, 0, 0);
    }

    public function testParseBothDates() {
        $todo = Task::fromString("x 2025-02-01 2025-01-01 Test");
        $this->thenTodoHas($todo, "Test", true, null,'2025-01-01', '2025-02-01', 0, 0, 0);
    }

    public function testParseProjects() {
        $todo = Task::fromString("Test +TestProject");
        $this->thenTodoHas($todo, "Test +TestProject", false, null, null, null, ['TestProject'], 0, 0);
        $this->assertEquals("Test", $todo->getCleanText());

        $todo = Task::fromString("Test +TestProject and +anotherProject");
        $this->thenTodoHas($todo, "Test +TestProject and +anotherProject", false, null, null, null, ['TestProject', 'anotherProject'], 0, 0);
        $this->assertEquals("Test +TestProject and", $todo->getCleanText());
    }

    public function testParseContexts() {
        $todo = Task::fromString("Test @Home");
        $this->thenTodoHas($todo, "Test @Home", false, null, null, null, 0, ['Home'], 0);
        $this->assertEquals("Test", $todo->getCleanText());

        $todo = Task::fromString("Test @Home and @work");
        $this->thenTodoHas($todo, "Test @Home and @work", false, null, null, null, 0, ['Home', 'work'], 0);
        $this->assertEquals("Test @Home and", $todo->getCleanText());
    }

    public function testParseMeta() {
        $todo = Task::fromString("Test color:red");
        $this->thenTodoHas($todo, "Test color:red", false, null, null, null, 0, 0, ['color' => 'red']);
        $this->assertEquals("Test", $todo->getCleanText());

        $todo = Task::fromString("Test color:red and test:yes");
        $this->thenTodoHas($todo, "Test color:red and test:yes", false, null, null, null, 0, 0, ['color' => 'red', 'test' => 'yes']);
        $this->assertEquals("Test color:red and", $todo->getCleanText());
    }

    public function testParseComplex() {
        $todo = Task::fromString("x 2025-01-01 2024-12-01 Just an @example with lots of +details +and more:yes");
        $this->thenTodoHas($todo, "Just an @example with lots of +details +and more:yes",
                           true, null, '2024-12-01', '2025-01-01',
                           ['details', 'and'], ['example'], ['more' => 'yes']);
        $this->assertEquals("Just an @example with lots of", $todo->getCleanText());

        $todo = Task::fromString("(Y) 2024-12-01 Another @example @with @lots of +details");
        $this->thenTodoHas($todo, "Another @example @with @lots of +details",
                           false, 'Y', '2024-12-01', null,
                           ['details'], ['example', 'with', 'lots'], 0);
        $this->assertEquals("Another @example @with @lots of", $todo->getCleanText());
    }

    public function testParseBrokenFormat() {
        $todo = Task::fromString("X Not really a done task");
        $this->thenTodoHas($todo, "X Not really a done task",
                           false, null, null, null, 0, 0,0);

        $todo = Task::fromString("(Y Incorrect priority format");
        $this->thenTodoHas($todo, "(Y Incorrect priority format",
                           false, null, null, null, 0, 0,0);

        $todo = Task::fromString("2024-02-01 (Y) Wrong order");
        $this->thenTodoHas($todo, "(Y) Wrong order",
                           false, null, '2024-02-01', null, 0, 0,0);

        $todo = Task::fromString("x Also wrong order 2024-02-01");
        $this->thenTodoHas($todo, "Also wrong order 2024-02-01",
                           true, null, null, null, 0, 0,0);
    }

    public function testTagSanitation() {
        $todo = $this->givenTodo('Test', false, null, null);
        $todo->addProject('weird project  with spaces');
        $todo->addContext('strange CONTEXT with   SPACES');
        $todo->addMeta('silly key', 'even more silly value');
        $this->thenTodoHas($todo, 'Test', false, null, null, null,
                           ['weirdProjectWithSpaces'], ['strangeContextWithSpaces'], ['sillyKey' => 'evenMoreSillyValue']);
    }

    public function testMarkingAsDone() {
        $todo = $this->givenTodo('Test', false, null, null);
        $todo->done();
        $today = date('Y-m-d');
        $this->thenTodoHas($todo, 'Test', true, null, null, $today, 0, 0, 0);
    }

    private function givenTodo(
        string $text = "Test",
        bool $done = false,
        ?string $creationDate = null,
        ?string $completionDate = null
    ): Task {
        return (new Task())
            ->setText($text)
            ->setDone($done)
            ->setCreationDate($creationDate)
            ->setCompletionDate($completionDate);
    }

    private function thenStringIs(string $expected, Task $todo): void {
        $this->assertEquals($expected, "{$todo}", "String does not match");
    }

    private function thenTodoHas(Task    $todo, string $text, bool $done, ?string $priority, ?string $creationDate,
                                 ?string $completionDate, int|array $projects, int|array $contexts, int|array $metas): void {
        $this->assertEquals($text, $todo->getText(), "Text doesn't match");
        $this->assertEquals($done, $todo->isDone(), "Done state doesn't match");
        $this->assertEquals($priority, $todo->getPriority(), "Priority doesn't match");
        $this->assertEquals($creationDate, $todo->getCreationDate(), "Creation date doesn't match");
        $this->assertEquals($completionDate, $todo->getCompletionDate(), "Completion date doesn't match");
        if (is_array($projects)) {
            $this->assertEquals($projects, $todo->getProjects(), "Projects don't match");
        } else {
            $this->assertCount($projects, $todo->getProjects(), "Wrong number of projects");
        }
        if (is_array($contexts)) {
            $this->assertEquals($contexts, $todo->getContexts(), "Contexts don't match");
        } else {
            $this->assertCount($contexts, $todo->getContexts(), "Wrong number of contexts");
        }
        if (is_array($metas)) {
            foreach ($metas as $k => $v) {
                $this->assertThat($todo->getMeta(), self::arrayHasKey($k), "Missing meta entry");
                $this->assertEquals($v, $todo->getMeta()[$k] ?? null, "Meta entry has wrong value");
            }
        } else {
            $this->assertCount($metas, $todo->getMeta(), "Wrong number of meta values");
        }
    }

}
