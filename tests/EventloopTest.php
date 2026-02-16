<?php

declare(strict_types=1);

namespace Eventloop\Tests;

use Eventloop\EventManager;
use Eventloop\ObserverManager;
use Eventloop\Sanitizer;
use PHPUnit\Framework\TestCase;
use SplObserver;
use SplSubject;

final class EventloopTest extends TestCase
{
    public function testSanitizerEscapesHtml(): void
    {
        $input = '<script>alert("XSS")</script>';
        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', Sanitizer::sanitize($input));
    }

    public function testSanitizerSanitizeArrayProcessesEachElement(): void
    {
        $inputs = ['<b>a</b>', 'x"y'];
        $this->assertSame(['&lt;b&gt;a&lt;/b&gt;', 'x&quot;y'], Sanitizer::sanitizeArray($inputs));
    }

    public function testEventManagerExecutesCallbacksByPriorityDescending(): void
    {
        $mgr = new EventManager();
        $calls = [];

        $mgr->addEvent('evt', function () use (&$calls): void { $calls[] = 'low'; }, 0);
        $mgr->addEvent('evt', function () use (&$calls): void { $calls[] = 'high'; }, 10);

        $mgr->executeEvent('evt');
        $this->assertSame(['high', 'low'], $calls);
    }

    public function testObserverManagerNotifiesAttachedObservers(): void
    {
        $manager = new ObserverManager();

        $subject = new class implements SplSubject {
            public function attach(SplObserver $observer): void {}
            public function detach(SplObserver $observer): void {}
            public function notify(): void {}
        };

        $called = 0;
        $observer = new class ($called) implements SplObserver {
            private int $called;
            public function __construct(int &$called) { $this->called = &$called; }
            public function update(SplSubject $subject): void { $this->called++; }
        };

        $manager->attach($observer);
        $manager->notify($subject);

        $this->assertSame(1, $called);
    }
}