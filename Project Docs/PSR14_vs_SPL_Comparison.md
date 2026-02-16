# PSR-14 vs PHP SPL Observer Pattern Comparison

## Overview

Both PSR-14 and SPL provide event/observer mechanisms, but they differ significantly in design philosophy and implementation.

---

## Core Interfaces

### SPL Observer Pattern (PHP Built-in)

```php
interface SplSubject
{
    public function attach(SplObserver $observer): void;
    public function detach(SplObserver $observer): void;
    public function notify(): void;
}

interface SplObserver
{
    public function update(SplSubject $subject): void;
}
```

**Key Characteristics:**
- **Tight Coupling**: Observer receives the entire Subject
- **Pull Model**: Observer must pull data from Subject
- **No Event Context**: Single `update()` method for all events
- **Direct References**: Subject holds direct references to observers

### PSR-14 Event Dispatcher

```php
interface EventDispatcherInterface
{
    public function dispatch(object $event): object;
}

interface ListenerProviderInterface
{
    public function getListenersForEvent(object $event): iterable;
}

interface StoppableEventInterface
{
    public function isPropagationStopped(): bool;
}
```

**Key Characteristics:**
- **Loose Coupling**: Listeners receive event objects, not the dispatcher
- **Push Model**: Event carries all necessary data
- **Event-Specific**: Different event classes for different scenarios
- **Indirect References**: Provider manages listener registration

---

## Design Philosophy

| Aspect | SPL Observer | PSR-14 Event Dispatcher |
|--------|--------------|------------------------|
| **Coupling** | Tight (Observer knows Subject) | Loose (Listener knows Event only) |
| **Data Flow** | Pull (Observer queries Subject) | Push (Event contains data) |
| **Event Types** | Single notification method | Multiple event classes |
| **Registration** | Direct on Subject | Via separate Provider |
| **Propagation** | No built-in control | `StoppableEventInterface` |
| **Priority** | Not standardized | Provider-specific (common) |
| **Wildcards** | Not supported | Provider-specific (possible) |

---

## Usage Examples

### SPL Observer Pattern

```php
class UserService implements SplSubject
{
    private $observers = [];
    private $lastAction;
    
    public function attach(SplObserver $observer): void
    {
        $this->observers[] = $observer;
    }
    
    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
    
    public function createUser($data)
    {
        // Create user...
        $this->lastAction = 'user_created';
        $this->notify();
    }
    
    public function getLastAction() { return $this->lastAction; }
}

class EmailNotifier implements SplObserver
{
    public function update(SplSubject $subject): void
    {
        // Must know about UserService to get data
        if ($subject instanceof UserService) {
            $action = $subject->getLastAction();
            if ($action === 'user_created') {
                // Send email
            }
        }
    }
}
```

**Issues:**
- Observer must know about Subject type
- No event-specific data structure
- Observer must check action type manually

### PSR-14 Event Dispatcher

```php
class UserCreatedEvent
{
    private $user;
    private $propagationStopped = false;
    
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    
    public function getUser(): User { return $this->user; }
    
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
    
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

class UserService
{
    private $dispatcher;
    
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    public function createUser($data)
    {
        $user = new User($data);
        // Create user...
        
        $event = new UserCreatedEvent($user);
        $this->dispatcher->dispatch($event);
    }
}

// Listener registration (via Provider)
$provider->addListener(UserCreatedEvent::class, function($event) {
    $user = $event->getUser();
    // Send email - no need to know about UserService
});
```

**Benefits:**
- Listener only knows about event type
- Event carries all necessary data
- Type-safe event handling
- Can stop propagation

---

## Your Implementation Comparison

### Your `eventloop` (SPL-based)

```php
class eventloop extends origin implements \SplSubject
{
    public function attach($observer, $event = '*')
    {
        $this->observers[$event][] = $observer;
    }
    
    public function ObserverNotify($trigger_class, $event, $msg)
    {
        // Legacy SPL-style notification
        foreach ($this->getEventObservers($event) as $observer) {
            if (method_exists($observer, 'notified')) {
                $observer->notified($trigger_class, $event, $msg);
            }
        }
    }
}
```

**Enhancements over standard SPL:**
- ✅ Event-specific registration (`$event` parameter)
- ✅ Wildcard support (`*`, `**`)
- ✅ Custom notification method (`notified` vs `update`)

### Your PSR-14 Integration

```php
class LegacyEvent implements EventInterface
{
    private $trigger;
    private $name;
    private $message;
    
    public function __construct($trigger, string $name, $message)
    {
        $this->trigger = $trigger;
        $this->name = $name;
        $this->message = $message;
    }
    
    public function getTrigger() { return $this->trigger; }
    public function getMessage() { return $this->message; }
}

// In eventloop
public function ObserverNotify($trigger_class, $event, $msg)
{
    // Dispatch via PSR-14
    $legacyEvent = new LegacyEvent($trigger_class, $event, $msg);
    $this->eventManager->dispatch($legacyEvent);
}
```

**Hybrid Approach:**
- ✅ Maintains backward compatibility
- ✅ Leverages PSR-14 features (priority, wildcards)
- ✅ Allows gradual migration

---

## Trade-offs

### When to Use SPL Observer

**Pros:**
- Built into PHP (no dependencies)
- Simple for basic use cases
- Direct object references (can be faster)

**Cons:**
- Tight coupling
- No standardized priority/wildcards
- Limited event context
- Harder to test (mocking subjects)

### When to Use PSR-14

**Pros:**
- Loose coupling (better architecture)
- Rich event context
- Standardized interface (interoperability)
- Easy to test (mock events)
- Propagation control
- Provider flexibility (priority, wildcards, filtering)

**Cons:**
- Requires library/implementation
- Slightly more overhead
- More classes to manage

---

## Recommendations

### For New Code
Use **PSR-14** for:
- Complex applications
- Multiple event types
- Need for priority/filtering
- Better testability
- Framework integration

### For Legacy Code
Your hybrid approach is excellent:
1. Keep SPL interface for backward compatibility
2. Delegate to PSR-14 internally
3. Gradually migrate observers to PSR-14 listeners

### Migration Path

```php
// Phase 1: Dual support (current)
$eventloop->attach($observer, 'user.created');  // SPL style
$eventManager->addListener('user.created', $listener);  // PSR-14 style

// Phase 2: Deprecate SPL methods
// @deprecated Use EventManager::addListener()
public function attach($observer, $event = '*') { ... }

// Phase 3: Remove SPL interface
class eventloop extends origin  // No longer implements SplSubject
```

---

## Key Insight

**SPL Observer** is a **pattern implementation** (how to structure code).

**PSR-14** is a **standard interface** (how to interoperate).

Your `eventloop` successfully bridges both worlds, giving you:
- Backward compatibility with existing SPL-based code
- Modern PSR-14 features (priority, wildcards, propagation control)
- Gradual migration path
