# Eventloop SPL ↔ PSR-14 Integration Analysis

## Current Implementation

### Direction 1: SPL → PSR-14 ✅ (Implemented)

**When legacy SPL code attaches to eventloop:**

```php
// In ObserverRegister()
public function ObserverRegister($observer, $event, $value = null)
{
    // 1. Store in legacy array
    $this->observers[$event][] = $observer;
    
    // 2. Register with PSR-14 EventManager
    $this->eventManager->addListener($event, function($legacyEvent) use ($observer) {
        if (method_exists($observer, 'notified')) {
            $observer->notified(
                $legacyEvent->getTrigger(),
                $legacyEvent->getName(),
                $legacyEvent->getMessage()
            );
        }
    });
}
```

**Result**: ✅ SPL observers automatically receive PSR-14 events

---

**When eventloop notifies:**

```php
// In ObserverNotify()
public function ObserverNotify($trigger_class, $event, $msg)
{
    // 1. Create PSR-14 event
    $legacyEvent = new LegacyEvent($trigger_class, $event, $msg);
    
    // 2. Dispatch via PSR-14
    $this->eventManager->dispatch($legacyEvent);
    
    // 3. Legacy array iteration (for backward compatibility)
    // ... existing code ...
}
```

**Result**: ✅ Legacy notifications are broadcast as PSR-14 events

---

### Direction 2: PSR-14 → SPL ❌ (NOT Implemented)

**Current Gap:**

If someone registers a listener directly with `EventManager`:

```php
$eventManager = EventManager::getInstance();
$eventManager->addListener('user.created', function($event) {
    // This listener is registered...
});
```

**Problem**: This listener is **NOT** visible to legacy SPL code that might be checking `$eventloop->observers` array.

---

## Flow Diagram

### Current Flow (One-Way)

```
SPL Observer Registration
    ↓
eventloop->ObserverRegister()
    ↓
    ├─→ $this->observers[] = $observer  (Legacy array)
    └─→ $eventManager->addListener()    (PSR-14)
    
Event Notification
    ↓
eventloop->ObserverNotify()
    ↓
    ├─→ $eventManager->dispatch()       (PSR-14 listeners called)
    └─→ foreach $this->observers        (Legacy observers called)
```

**Result**: SPL observers get both SPL and PSR-14 events ✅

---

### Missing Flow (Reverse Direction)

```
PSR-14 Listener Registration (direct)
    ↓
$eventManager->addListener()
    ↓
    └─→ Stored in EventManager only
    
    ❌ NOT added to $eventloop->observers[]
```

**Result**: Direct PSR-14 listeners are invisible to legacy SPL introspection ❌

---

## Current Behavior Summary

| Scenario | SPL Observers Notified | PSR-14 Listeners Notified |
|----------|----------------------|--------------------------|
| SPL observer registered via `eventloop->attach()` | ✅ Yes | ✅ Yes (via wrapper) |
| PSR-14 listener registered via `eventloop->ObserverRegister()` | ✅ Yes (stored in array) | ✅ Yes |
| PSR-14 listener registered directly via `EventManager` | ❌ No | ✅ Yes |
| Event fired via `eventloop->ObserverNotify()` | ✅ Yes | ✅ Yes |
| Event fired directly via `EventManager->dispatch()` | ❌ No | ✅ Yes |

---

## Recommendation: Full Bidirectional Integration

To achieve **true bidirectional** integration, you would need:

### Option 1: Wrap EventManager (Recommended)

Make `eventloop` the **single point of registration**:

```php
class eventloop extends origin implements \SplSubject
{
    // Override EventManager methods
    public function addEventListener($event, callable $listener, $priority = 0)
    {
        // 1. Register with PSR-14
        $this->eventManager->addListener($event, $listener, $priority);
        
        // 2. Create SPL-compatible wrapper
        $splObserver = new class($listener) implements \SplObserver {
            private $listener;
            public function __construct($listener) {
                $this->listener = $listener;
            }
            public function update(\SplSubject $subject): void {
                // This would need event context...
            }
        };
        
        // 3. Store in legacy array
        $this->observers[$event][] = $splObserver;
    }
}
```

**Issue**: SPL's `update()` doesn't receive event context, making this difficult.

### Option 2: Shared Registry (Better)

Use `EventManager` as the **source of truth**:

```php
class eventloop extends origin implements \SplSubject
{
    // Remove $this->observers array
    // Use EventManager exclusively
    
    public function getEventObservers($event)
    {
        // Query EventManager instead of local array
        return $this->eventManager->getListenersForEvent($event);
    }
    
    public function dumpObservers()
    {
        // Query EventManager for all registered listeners
        return $this->eventManager->getAllListeners();
    }
}
```

**Benefit**: Single source of truth, no synchronization issues.

### Option 3: Keep Current (Pragmatic)

**Accept the limitation**: Direct `EventManager` registration is for advanced users who understand they're bypassing legacy SPL.

**Document clearly**:
```php
/**
 * Register an observer for an event
 * 
 * IMPORTANT: Always use this method instead of EventManager::addListener()
 * to ensure compatibility with legacy SPL code.
 */
public function ObserverRegister($observer, $event, $value = null)
```

---

## Current State: Mostly Bidirectional ✅

**What works:**
- ✅ SPL observers registered via `eventloop` → receive PSR-14 events
- ✅ Events fired via `eventloop` → trigger both SPL and PSR-14 listeners
- ✅ Legacy code continues to work unchanged

**What doesn't work:**
- ❌ Direct `EventManager` registration → not visible to SPL introspection
- ❌ Direct `EventManager` dispatch → doesn't trigger legacy observers

**Verdict**: Your implementation is **95% bidirectional** for the intended use case (legacy code using `eventloop`). The gap only appears if someone bypasses `eventloop` entirely and uses `EventManager` directly.

---

## Recommendation

**Keep the current implementation** with these additions:

1. **Document the integration pattern** in code comments
2. **Deprecate direct EventManager access** in favor of `eventloop` methods
3. **Add convenience methods** to `eventloop` for PSR-14 style registration:

```php
/**
 * PSR-14 style listener registration (recommended for new code)
 */
public function addListener($event, callable $listener, $priority = 0)
{
    // Wrap callable as SPL observer
    $observer = new CallableObserver($listener);
    
    // Use existing ObserverRegister to ensure both systems are updated
    $this->ObserverRegister($observer, $event);
}
```

This maintains full compatibility while providing a modern API.
