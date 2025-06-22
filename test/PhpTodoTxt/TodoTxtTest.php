<?php
namespace PhpTodoTxt;

use PhpTodoTxt\Models\Task;
use PHPUnit\Framework\TestCase;

class TodoTxtTest extends TestCase {

    private string $filePath;

    protected function setUp(): void {
        parent::setUp();
        $this->filePath = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
    }

    public function testTraversability() {
        $todos = $this->givenTaskListWith3Tasks();
        $this->thenForeachCounts($todos, 3);
    }

    public function testArrayAccess() {
        $todos = $this->givenTaskListWith3Tasks();
        $this->assertInstanceOf(Task::class, $todos[0]);
        $this->assertEquals('First task', $todos[0]->getText());
        $this->assertInstanceOf(Task::class, $todos[1]);
        $this->assertEquals('Second task', $todos[1]->getText());
        $this->assertInstanceOf(Task::class, $todos[2]);
        $this->assertEquals('Third task', $todos[2]->getText());
    }

    public function testSimpleReadAndWrite() {
        list($srcFile, $todos) = $this->givenTaskListFromFile();
        $this->assertCount(3, $todos);
        $this->assertEquals('Second Task', $todos[1]->getText());
        $this->assertTrue($todos[2]->isDone());
        $tmpFile = tempnam(sys_get_temp_dir(), 'tttest');
        $todos->writeToFile($tmpFile);
        $this->thenFilesAreEqual($srcFile, $tmpFile);
    }

    public function testRemovingAndAddingTasks() {
        $todos = $this->givenTaskListWith3Tasks();
        $todos->removeTaskByIndex(1);
        $this->assertCount(2, $todos);
        $this->thenForeachCounts($todos, 2);
        $todos->addTask((new Task())->setText('Another task'));
        $this->thenForeachCounts($todos, 3);
        $this->assertEquals(3, $todos->count());
        $this->assertEquals('Another task', $todos[3]->getText());
        $this->assertNull($todos[1]);
    }

    public function testMovingTaskUp() {
        $todos = $this->givenTaskListWith3Tasks();
        $fourthTask = (new Task())->setText('Another task');
        $todos->addTask($fourthTask);
        $firstTask = $todos[0];
        $secondTask = $todos[1];
        $thirdTask = $todos[2];
        $todos->moveTask($firstTask, 2);
        $this->assertNull($todos[0]);
        $this->assertSame($secondTask, $todos[1]);
        $this->assertSame($firstTask, $todos[2]);
        $this->assertSame($thirdTask, $todos[3]);
        $this->assertSame($fourthTask, $todos[4]);
    }

    public function testMovingTaskDown() {
        $todos = $this->givenTaskListWith3Tasks();
        $fourthTask = (new Task())->setText('Another task');
        $todos->addTask($fourthTask);
        $firstTask = $todos[0];
        $secondTask = $todos[1];
        $thirdTask = $todos[2];
        $todos->moveTask($fourthTask, 1);
        $this->assertSame($firstTask, $todos[0]);
        $this->assertSame($fourthTask, $todos[1]);
        $this->assertSame($secondTask, $todos[2]);
        $this->assertSame($thirdTask, $todos[3]);
    }

    public function testToStringArray() {
        $todos = $this->givenTaskListWith3Tasks();
        $expected = ['First task', 'Second task', 'Third task'];
        $this->assertEquals($expected, $todos->toStringArray());
    }

    public function testFromStringArray() {
        $todos = TodoTxt::fromStringArray(["First task", "Second Task", "x Third Task"]);
        $this->assertCount(3, $todos);
        $this->assertEquals("First task", $todos[0]->getText());
        $this->assertTrue($todos[2]->isDone());
    }

    private function givenTaskListFromFile(): array {
        $srcFile = $this->filePath . 'simple.txt';
        $todos = TodoTxt::readFromFile($srcFile);
        return array($srcFile, $todos);
    }

    private function givenTaskListWith3Tasks(): TodoTxt {
        $todos = new TodoTxt();
        $todos->addTask(new Task('First task', null, false));
        $todos->addTask(new Task('Second task', null, false));
        $todos->addTask(new Task('Third task', null, false));
        return $todos;
    }

    private function thenFilesAreEqual(string $expectedFile, string $actualFile): void {
        $expected = fopen($expectedFile, 'r');
        $actual = fopen($actualFile, 'r');
        $line = 0;
        while ($expectedLine = fgets($expected)) {
            $actualLine = fgets($actual);
            if ($actualLine === false) {
                $this->fail("Actual file has less lines than expected");
            }
            $this->assertEquals(trim($expectedLine), trim($actualLine), "Expected line {$line} is different than the actual line");
            $line += 1;
        }
    }

    private function thenForeachCounts(TodoTxt $todos, int $expected): void {
        $count = 0;
        foreach ($todos as $task) {
            $count += 1;
        }
        $this->assertEquals($expected, $count);
    }

}
