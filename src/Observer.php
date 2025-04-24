<?php

namespace Eventloop;

use SplObserver;
use SplSubject;
/****************************splObserver************************************************/
/****************************php.net****************************************************/

/**
 * Abstract Observer class for managing observer behavior.
 */
abstract class Observer implements SplObserver
{
    private $observable;

    public function __construct(SplSubject $observable)
    {
        $this->observable = $observable;
        $observable->attach($this);
    }

    public function update(SplSubject $subject): void
    {
        if ($subject === $this->observable) {
            $this->doUpdate($subject);
        }
    }

    abstract protected function doUpdate(SplSubject $observable): void;
}
/****************************!php.net****************************************************/
/****************************!splObserver************************************************/
