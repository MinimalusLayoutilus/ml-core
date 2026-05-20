# Event System — Status & Roadmap

This document captures what the framework's event bus does **today**, what it
**doesn't** do, and the planned extension path so listeners can intervene in
or manipulate framework behaviour at well-known points (the original design
intent before the bus settled into its current "fire-and-forget" shape).

The implementation lives in `ml-core` (`mnhcc\ml\classes\EventManager`,
`Event`, `EventParms` + the matching trait and interface).  Specialised
payload classes live with the package that produces them
(`Template\EventParms` in ml-mvc, `ExceptionEventParms` in ml-bugcatcher).

---

## 1. Status quo

### Public surface

```php
EventManager::register(Event $event);                // attach a listener
EventManager::raise(string $name, EventParms $parms); // dispatch by name
EventManager::cleanEventName(string $name): string;   // strip "on", ucfirst
```

`Event` wraps a callable and a name; `EventParms` is a typed parameter bag
with `get/set/is/getParms`.  Names are normalised by stripping a leading
`on` and `ucfirst`-ing the rest.

### Where the framework actually raises events

| Event             | Raised by                                        | Payload                    |
|-------------------|--------------------------------------------------|----------------------------|
| `templateCreated` | `Template::__construct` (ml-mvc)                 | `Template\EventParms`      |
| `beforeRender`    | `TemplateHtml::render` (ml-mvc, before fetch)    | `EventParms($this, [])`    |
| `renderHead`      | `TemplateHtml::_renderHead` (per `<head>` slot)  | `EventParms(['type','attr'])` |
| `exception`       | `Error::handleException` (ml-bugcatcher)         | `ExceptionEventParms`      |
| `shutdown`        | `Error::shutdown` (ml-bugcatcher)                | `ExceptionEventParms`      |

### The single production listener

`Error::__construct` (ml-bugcatcher) registers exactly one listener — its own
`onTemplateCreated` static method — which adds `error` and `debug` styles to
the active Template instance when it boots.

### What the bus is, today

A **fire-and-forget notification bus** (Observer pattern).  Listeners
**observe**; callers do not consume listener results.  Concretely:

- `Event::raise()` calls `call_user_func($cb, $eparm)` and *ignores* the
  return value.
- The caller never re-reads the `EventParms` after `raise()` to see what
  listeners may have changed.
- There is no `stopPropagation()`, no `preventDefault()`, no priority/weight,
  no result aggregation, no listener-exception isolation.
- Listener registry is process-wide static state with no `resetTestState()`
  hook — listeners leak between tests.

---

## 2. What the bus cannot do that you *want* it to

The original design intent — judging from the events that get raised but
have no consumer (`beforeRender`, `renderHead`, `exception`, `shutdown`) —
was for listeners to **intervene**: change a value before render, redirect a
404 to a custom flow, append HTML to the head, abort dispatch on a condition.
That capability isn't there yet:

| Use case | Today | Why blocked |
|---|---|---|
| Append a `<style>` tag to the page chrome | ✓ via `onTemplateCreated` (the listener mutates the Template directly via `addStyle()`) | Works because the Template instance is reachable inside the parm. |
| Filter the rendered component body before it lands in the layout | ✗ | No raise point exists; even with one, caller doesn't read parms back. |
| Add fields to PageConfig before they ship to Template | ✗ | No raise point; same reason. |
| Cancel an action and emit a Redirect | ✗ | No `stopPropagation()`; no convention for "veto" return value. |
| Decorate the output of one filter with another | ✗ | `EventParms::get()` returns a copy-by-value for scalars; no pipeline contract. |
| Register two listeners and have them not stomp each other | Partial — execution order = registration order; no priority. |

### Missing raise points (in addition to the API gaps)

The places where intervention is *typically* needed have no event today:

- **Router**: after URL → control/action mapping, before dispatch.
- **Programm::runn**: before a Control class is resolved; before/after
  `actionXxx` execution.
- **Control**: before/after `getComponent`, after `getModul`.
- **View**: before/after `renderComponent`.
- **Template**: after `_fetchTemplate` (raw template loaded but not parsed),
  after `_parseTemplate` (tags found but not yet replaced), before final
  output emit.
- **ContentFilterReplacer**: per-filter pre/post hooks.

---

## 3. Roadmap

The plan is staged so each etap is independently mergeable and reverts
cleanly.  Each stage delivers one capability without breaking the existing
fire-and-forget contract.

### Stage A — make the bus testable  ✓ delivered 2026-04-26

*Touches: `EventManager`, tests.*

Done:
- `EventManager::resetTestState()` — empties `$_events`, `$_filters` and
  the strict flag; PHPUnit can now isolate listener state between methods.
- `EventManager::getListeners(string $name = null): Event[]` (read-only).
- `EventManager::getFilters(string $name = null): array` (read-only).
- `Event::raise()` exceptions in the dispatcher are caught; failures route
  to `Error::report()` (and from there to the framework log + DEBUG-only
  BugCatcher overlay).  `EventManager::setStrict(true)` opts back into
  fail-loud for debugging.
- Bonus that fell out of the testing work: an inconsistency between
  `register()` (storage key was lowercase via `Event::setEventName`) and
  `raise()` (lookup key was ucfirst) meant listeners registered through
  the documented API never fired.  Both paths now use the same
  lowercase-key normalisation, so the long-dormant
  `Error::onTemplateCreated` listener actually runs.

### Stage B — read-back: listeners can mutate `EventParms`, callers honour it  ✓ delivered 2026-04-27

*Touches: `EventParms` (docblock), `ml-mvc/Template/TemplateHtml::_renderHead`.*

Done:
- `EventParms`'s class docblock formalises the contract: listeners may
  mutate the bag in place via `set()`; callers may read keys back after
  `raise()` returns.  Two new unit tests cover the round-trip
  (`testRaise_listenerMutationVisibleToCallerAfterRaise`) and the
  no-listener fallback (`testRaise_callerReadBackFallsBackWhenNoListenerMutates`).
- First production read-back call site:
  `TemplateHtml::_renderHead($name, $attribs)` now reads `'type'` and
  `'attr'` back from the `renderHead` parm bag before dispatching to
  the per-renderer helpers.  Backwards-compatible: a listener that
  doesn't mutate is a no-op, and the rendered HTML stays byte-identical
  to the pre-Stage-B output.

Concrete pattern:

```php
// Template.php — called for every <head> include.
$parms = new EventParms(['type' => $name, 'attr' => $attribs]);
EventManager::raise('renderHead', $parms);
$attribs = $parms->get('attr', $attribs); // honour any listener edits.
```

Roadmap call-outs that are still future work:
- More read-back call sites at the raise points planned in stage D
  (`beforeAction`, `afterAction`, etc.) so a listener can short-circuit
  or amend the dispatch.

### Stage C — `filter()` — explicit return-chained hooks  ✓ delivered 2026-04-26

*Touches: `EventManager`, ContentFilterReplacer.*

Done:
- `EventManager::filter(string $name, mixed $value, ?EventParms $context = null): mixed`
  — threads `$value` through every registered listener in priority order;
  each callback receives `($value, ?EventParms $context)` and returns the
  next value.  Listeners returning `null` are coerced back to the previous
  value (WP convention: "I did nothing" = identity).
- `EventManager::registerFilter(string $name, callable $callback, int $priority = 10)`
  — register; lower priority = earlier in the chain.  Equal priorities
  preserve registration order (stable sort even on PHP 5.6 / 7.x).
- Listener-exception isolation: same path as `raise()` (route to
  `Error::report()`, chain continues; `setStrict(true)` opts out).

First production use case: **ContentFilterReplacer migrated to the bus**
(see ml-mvc/CHANGELOG):
- Each declared filter registers a listener on `content.filter.{key}`.
- `apply($content, $keys)` dispatches each key through `filter()`, then
  fires `content.filter.body` once for whole-body manipulation.
- Per-match callable / eval failures wrap into `ContentFilterException`
  and route through `Error::report()` (always logged, BugCatcher overlay
  when DEBUG=on).
- Third parties can `EventManager::registerFilter('content.filter.foo', …)`
  without going through `filters.php`.

Roadmap call-outs that are still future work:
- `filter('component.body', $renderedBody, ParmsControl)` in
  `Programm::runn` between View and Template.
- `filter('pageConfig', $pageConfig)` in `ControlFilePage::actionIndex` after
  build, before push.
- `filter('template.body', $finalHtml)` in `TemplateHtml::render` before
  emit.

### Stage D — `stopPropagation()` and the rest of the standard event surface

*Touches: `EventParms` (or a new `StoppableEventParms`), `EventManager::raise`.*

Add the missing observer-pattern affordances:

- `EventParms::stopPropagation()` / `isPropagationStopped()` — `raise()` honours it
  by short-circuiting the listener loop.  Useful for "the first listener
  consumed the event, no one else needs to see it".
- `EventParms::preventDefault()` / `isDefaultPrevented()` — convention for the
  *caller* to read back: "a listener vetoed the default behaviour, take the
  alternate path."  Concrete first use: `Programm::runn` checks this on
  `beforeAction` to skip running the action and emit a redirect from
  `EventParms::get('redirect')` instead.
- Listener priority on `register()`, like `registerFilter()` in stage C.

**Risk**: medium.  Subtle semantics — needs documenting.  Pure additions, so
existing fire-and-forget code keeps working.

### Stage E — fill in the missing raise points

*Touches: `Programm`, `Control`, `View`, `Template`, `ContentFilterReplacer`.*

Once stages A–D are landed, sprinkle raise/filter calls at the natural
intervention points listed in §2 ("Missing raise points").  Each one is its
own small PR with a doc note ("`afterAction` is raised after `actionXxx`
returns; payload exposes the returned component"), documented test, and a
single sample listener in the test suite.

**Risk**: low per-site.  Each raise point is independently reverable.

---

## 4. Out of scope (for now)

- **Async listeners** — PHP's request lifecycle is synchronous; an async
  bus would need a queue and worker, which changes the deployment story
  considerably.  Not warranted by current use cases.
- **Symfony `EventDispatcher` compatibility** — the FQCN aliases would be
  trivial, but the typed-event contract (one PHP class per event with
  named accessors) is a much bigger change than just exposing
  `getException()` / `getTemplate()` on a parm bag.  Worth revisiting if
  the framework ever wants to consume third-party Symfony listeners.
- **Reflection-based handler resolution** ("auto-register every method
  starting with `on…` on a registered class") — convenient but pushes the
  boundary between framework code and application code further into
  unfamiliar territory.

---

## 5. Recommendation

If the immediate need is "let the page chrome inject extra `<head>`
content" or "let a listener tweak `PageConfig` before push", the cheapest
*useful* delivery is **stage A + selective use of stage B** at one or two
read-back-friendly raise points.  That gets you most of the intervention
intent with one merge, no API breakage, and no new public surface.

Stage C (`filter()`) is the next clean breakpoint and unlocks the
"transform a value through the chain" use case that an observer-only bus
fundamentally cannot serve.  Worth doing once stage B's read-back pattern
has settled in production.

Stages D and E are progressively bigger and more opinionated; defer until
you have a concrete need.
