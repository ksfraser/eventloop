<?php

namespace Ksfraser\Eventloop;

use Ksfraser\Event\Contracts\EventInterface;

/**
 * Legacy Event Adapter
 * 
 * Bridges eventloop's 3-argument notification system to the modern Event system.
 */
class LegacyEvent implements EventInterface
{
    private object $trigger;
    private string $name;
    private mixed $message;
    private bool $propagationStopped = false;

    public function __construct(object $trigger, string $name, mixed $message)
    {
        $this->trigger = $trigger;
        $this->name = $name;
        $this->message = $message;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTrigger(): object
    {
        return $this->trigger;
    }

    public function getMessage(): mixed
    {
        return $this->message;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
