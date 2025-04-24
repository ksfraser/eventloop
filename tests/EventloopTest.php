<?php

use PHPUnit\Framework\TestCase;
use Eventloop\eventloop;

/**
 * PHPUnit test cases for the Eventloop class.
 */
class EventloopTest extends TestCase
{
    /**
     * Test setting the module directory.
     */
    public function testSetModuledir(): void
    {
        $eventloop = new eventloop();
        $eventloop->set_moduledir('/path/to/modules');
        $this->assertEquals('/path/to/modules', $eventloop->moduledir);
    }

    /**
     * Test registering an observer.
     */
    public function testObserverRegister(): void
    {
        $eventloop = new eventloop();
        $observer = $this->createMock(SplObserver::class);
        $eventloop->ObserverRegister($observer, 'test_event');
        $observers = $eventloop->getEventObservers('test_event');
        $this->assertContains($observer, $observers);
    }

    /**
     * Test notifying observers.
     */
    public function testObserverNotify(): void
    {
        $eventloop = new eventloop();
        $observer = $this->createMock(SplObserver::class);
        $observer->expects($this->once())
                 ->method('update')
                 ->with($this->equalTo($eventloop));

        $eventloop->ObserverRegister($observer, 'test_event');
        $eventloop->ObserverNotify($eventloop, 'test_event', 'Test message');
    }

    /**
     * Test input sanitization.
     */
    public function testSanitizeInput(): void
    {
        $eventloop = new eventloop();
        $input = '<script>alert("XSS")</script>';
        $sanitized = $eventloop->sanitizeInput($input);
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $sanitized);
    }

    /**
     * Test adding and executing hooks.
     */
    public function testAddHookAndExecuteHooks(): void
    {
        $eventloop = new eventloop();
        $eventloop->add_action('test_event', function ($data) {
            return $data . ' modified';
        });

        $result = $eventloop->do_action('test_event', 'original data');
        $this->assertNull($result); // do_action does not return a value
    }

    /**
     * Test registering and processing triggers.
     */
    public function testRegisterTriggerAndProcessWorkflow(): void
    {
        $eventloop = new eventloop();
        $eventloop->register_trigger('test_event', function ($data) {
            echo "Trigger executed with data: $data";
        });

        $this->expectOutputString("Trigger executed with data: workflow data");
        $eventloop->process_workflow('test_event', 'workflow data');
    }
}