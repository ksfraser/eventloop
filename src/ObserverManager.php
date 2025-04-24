<?php

namespace Eventloop;

use SplObserver;
use SplObjectStorage;

/**
 * Manages observer registration, deregistration, and notification.
 */
class ObserverManager
{
    private SplObjectStorage $observers;

    public function __construct()
    {
        $this->observers = new SplObjectStorage();
    }

    /**
     * Attach an observer.
     *
     * @param SplObserver $observer
     */
    public function attach(SplObserver $observer): void
    {
        $this->observers->attach($observer);
    }

    /**
     * Detach an observer.
     *
     * @param SplObserver $observer
     */
    public function detach(SplObserver $observer): void
    {
        $this->observers->detach($observer);
    }

    /**
     * Notify all observers.
     *
     * @param object $subject
     */
    public function notify(object $subject): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($subject);
        }
    }
}