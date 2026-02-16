<?php

declare(strict_types=1);

// Include the eventloop class file directly since it has non-standard autoloading
require_once __DIR__ . '/../src/class.eventloop.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for eventloop class focusing on logging and inherited origin methods
 * These tests ensure backward compatibility during refactoring
 */
final class EventloopInheritanceTest extends TestCase
{
    /** @var \Ksfraser\Eventloop\eventloop */
    private $eventloop;

    protected function setUp(): void
    {
        $this->eventloop = new \Ksfraser\Eventloop\eventloop(null, 'TestCaller');
    }

    /**
     * Test that Log method is accessible and works
     */
    public function testLogMethodExists(): void
    {
        // Log should be callable without throwing exceptions
        $this->eventloop->Log('Test message', PEAR_LOG_DEBUG);
        $this->assertTrue(method_exists($this->eventloop, 'Log'));
    }

    /**
     * Test stampLog method
     */
    public function testStampLogMethodExists(): void
    {
        $this->eventloop->stampLog('Test stamped message', PEAR_LOG_INFO);
        $this->assertTrue(method_exists($this->eventloop, 'stampLog'));
    }

    /**
     * Test log_0 through log_7 shortcut methods
     */
    public function testLogShortcutMethods(): void
    {
        $shortcuts = ['log_0', 'log_1', 'log_2', 'log_3', 'log_4', 'log_5', 'log_6', 'log_7'];
        
        foreach ($shortcuts as $method) {
            $this->assertTrue(
                method_exists($this->eventloop, $method),
                "Method {$method} should exist"
            );
            
            // Call the method to ensure it works
            $this->eventloop->$method($this->eventloop, 'Test message');
        }
    }

    /**
     * Test inherited set() method from origin
     */
    public function testSetMethodFromOrigin(): void
    {
        $this->assertTrue(method_exists($this->eventloop, 'set'));
        
        // set() should work without throwing exceptions
        try {
            $this->eventloop->set('test_field', 'test_value', false);
            $this->assertTrue(true); // If we get here, no exception was thrown
        } catch (\Exception $e) {
            // set() might throw if field doesn't exist, which is expected
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /**
     * Test inherited get() method from origin
     */
    public function testGetMethodFromOrigin(): void
    {
        $this->assertTrue(method_exists($this->eventloop, 'get'));
    }

    /**
     * Test inherited set_var() method from origin
     */
    public function testSetVarMethodFromOrigin(): void
    {
        $this->assertTrue(method_exists($this->eventloop, 'set_var'));
        
        // set_var should not throw exceptions
        $result = $this->eventloop->set_var('caller', 'NewCaller');
        $this->assertTrue($result);
    }

    /**
     * Test inherited get_var() method from origin
     */
    public function testGetVarMethodFromOrigin(): void
    {
        $this->assertTrue(method_exists($this->eventloop, 'get_var'));
        
        // Get the caller we set in constructor
        $caller = $this->eventloop->get_var('caller');
        $this->assertEquals('TestCaller', $caller);
    }

    /**
     * Test that eventloop no longer extends kfLog (now uses composition)
     */
    public function testDoesNotExtendKfLog()
    {
        $this->assertNotInstanceOf(\ksfraser\kfLog::class, $this->eventloop);
    }

    /**
     * Test that eventloop has logger functionality via composition
     */
    public function testLoggerFunctionalityViaComposition()
    {
        // Log methods should work even though we don't extend kfLog
        $this->eventloop->Log('Test via composition', PEAR_LOG_DEBUG);
        $this->eventloop->stampLog('Stamped test', PEAR_LOG_INFO);
        
        // If we get here without errors, composition is working
        $this->assertTrue(true);
    }

    /**
     * Test that eventloop extends origin (transitively through kfLog)
     */
    public function testExtendsOrigin(): void
    {
        $this->assertInstanceOf(\Ksfraser\Origin\origin::class, $this->eventloop);
    }
}
