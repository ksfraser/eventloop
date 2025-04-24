<?php

use PHPUnit\Framework\TestCase;
use Eventloop\eventloop;

class EventloopTest extends TestCase
{
    public function testSetModuledir()
    {
        $eventloop = new eventloop();
        $eventloop->set_moduledir('/path/to/modules');
        $this->assertEquals('/path/to/modules', $eventloop->moduledir);
    }

    public function testObserverRegister()
    {
        $eventloop = new eventloop();
        $observer = $this->createMock(SplObserver::class);
        $eventloop->ObserverRegister($observer, 'test_event');
        $observers = $eventloop->getEventObservers('test_event');
        $this->assertContains($observer, $observers);
    }

    public function testObserverNotify()
    {
        $eventloop = new eventloop();
        $observer = $this->createMock(SplObserver::class);
        $observer->expects($this->once())
                 ->method('update')
                 ->with($this->equalTo($eventloop));

        $eventloop->ObserverRegister($observer, 'test_event');
        $eventloop->ObserverNotify($eventloop, 'test_event', 'Test message');
    }

    public function testSanitizeInput()
    {
        $eventloop = new eventloop();
        $input = '<script>alert("XSS")</script>';
        $sanitized = $eventloop->sanitizeInput($input);
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $sanitized);
    }

    public function testAddHookAndExecuteHooks()
    {
        $eventloop = new eventloop();
        $eventloop->addHook('test_event', function ($data) {
            return $data . ' modified';
        });

        $result = $eventloop->executeHooks('test_event', 'original data');
        $this->assertEquals('original data modified', $result);
    }
}