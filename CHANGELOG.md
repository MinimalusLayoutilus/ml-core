# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.9.5] — 2026-04-28

### Fixed — PHP 8.x compatibility (verified live on PHP 8.5)
- **`BootstrapHandler::__require()` no longer fatals on PHP 8.x.**
  The lifecycle-hook dispatcher called
  `(new \ReflectionMethod($name, '___onLoaded'))->invoke($name)` —
  passing the class-name string into the parent's first slot.
  PHP 8 enforces `?object` on that slot and fataled with
  "Argument #1 (\$object) must be of type ?object, string given"
  on the very first hook firing under PHP 8.5 in `mn-hegenbarth.de`.
  `___onLoaded()` and `___require()` are static methods, the
  ReflectionMethod is already bound to the class via the
  `($name, $methodName)` ctor — `invoke(null)` is the right call on
  every supported runtime.
- **`Filter::input` no longer triggers `E_DEPRECATED` on PHP 8.1+.**
  `filter_input()`'s fourth parameter (`$options`) tightened to
  `array|int` in PHP 8.1; passing `null` (the historical default)
  prints "Passing null to parameter #4 (\$options) of type array|int
  is deprecated".  Default changed from `NULL` to `0` (no flags),
  matching PHP's own internal default.

### Added — PHP 8.2+ dynamic-property opt-in
- `MNHcC` (abstract base), `EventParms` and `Config` carry the
  `#[\AllowDynamicProperties]` attribute.  PHP 5.6 / 7.x parse the
  line as a `#`-comment (single-line comment syntax — no parse
  error); PHP 8.0+ honour it as a real attribute and stop emitting
  `E_DEPRECATED` on dynamic property creation against any descendant
  (the framework's `__call`-based magic and the `EventParms` bag
  pattern would otherwise drown PHP 8.2+ logs in deprecation
  warnings).  `Config` carries the attribute directly because it
  extends `\ArrayObject` rather than the framework's `MNHcC` base —
  an inherited `#[\AllowDynamicProperties]` would not apply.

### Fixed — `traits\ArrayAccess` LSP under PHP 8.1+
- `offsetGet` / `offsetSet` / `offsetExists` / `offsetUnset` stamped
  with `#[\ReturnTypeWillChange]`.  `ArrayAccess`'s interface
  methods grew explicit return types in PHP 8 (`offsetGet(): mixed`,
  `offsetExists(): bool`, etc.).  Without the attribute every class
  using the trait fired a deprecation on each load.  No semantic
  change; the trait already returned the right values.

### Compatibility
- Framework's PHP floor stays at 5.6.  All fixes use 5.6-syntactic
  constructs; the `#[…]`-attributes are forward- AND backward-
  compatible because PHP <8.0 parses `#` as a comment.
- Skeleton (`MinimalusLayoutilus`) on PHP 5.6 in DDEV: byte-identical
  to baseline.  `mn-hegenbarth.de` on PHP 8.5: page renders, content
  delivered (HTTP 200, structurally matches the 5.6 baseline aside
  from a counter-service idiosyncrasy unrelated to this commit).

## [0.9.4] — 2026-04-28

### Added
- **Auto-discovery of installed ml-* packages.**
  `BootstrapHandler::initial()` now calls a new
  `_loadGeneratedPackageRegistry()` step after the SPL autoloader
  registrations.  When the optional `mnhcc/ml-composer-plugin` is
  installed, it has written
  `<vendor>/composer/mnhcc-ml-packages.php` — a flat `[name => path]`
  array — on the most recent `composer install` / `update` /
  `dump-autoload`.  The bootstrap reads that file and calls
  `registerPackagePath()` for every entry, so the framework's
  autoloader sees every Minimalus Layoutilus package Composer just
  installed without a manual call from the consumer's `initial.php`.
- This closes the gap left by Phase 2 (v0.9.3): the wrapper-loader
  could fire lifecycle hooks for any class it could find through
  `getIncludePaths()`, but the registry only saw ml-core by default.
  v0.9.4 makes the registry self-populate from Composer, so the same
  hook story applies to ml-bugcatcher, ml-mvc, ml-filepages and any
  future ml-* sibling.

### Compatibility
- The new step is fail-soft.  When the plugin is not installed
  (composer-less deploy, plugin not yet pulled in, plugin disabled
  via `config.allow-plugins`) the manifest file is absent and
  `_loadGeneratedPackageRegistry()` returns silently — the framework
  falls back to whatever paths the consumer registered manually.
- Manual `BootstrapHandler::registerPackagePath()` calls continue to
  work alongside plugin-discovered entries; both write into the same
  underlying registry.

## [0.9.3] — 2026-04-28

### Added
- **Wrapper-Loader: framework `___onLoaded` / `___require` hooks fire
  again under Composer.**  `BootstrapHandler::namespaceLoader` is now
  registered with `prepend=true`, so it runs ahead of Composer's
  classmap loader for every class load.  A prefix filter
  (`mnhcc\ml\*`) at the top of `namespaceLoader` keeps the cost out of
  every non-mnhcc autoload — non-framework FQCNs return false in O(1)
  and Composer takes over unchanged.  Net effect: the
  long-dormant `MNHcC::___onLoaded` lifecycle hook (documented as
  Issue 11 in `MinimalusLayoutilus/ARCHITECTURE_NOTES.md`) is alive
  again for ml-core classes — `ArrayHelper::___onLoaded()`'s
  `ArrayObject` / `MNHcCArray` converters actually register on first
  load, `Bootstrap::___onLoaded()`'s `APPLICATIONNAMESPACE` default
  is set, etc.
- **`ArrayHelper::getConverters()`** — read-only access to the
  converter registry.  Lets test suites and consumer code introspect
  what was registered via `setConverter()`.
- Phase-3 (auto-discovery via Composer plugin) follows in v0.9.4 to
  extend the same hook coverage to ml-bugcatcher / ml-mvc / ml-* —
  today they still go through Composer's classmap because
  `getIncludePaths()` only sees ml-core by default.

### Fixed
- **`ArrayHelper::setConverter` was a silent no-op** — wrote to a
  function-local `$_converters` because the variable was missing the
  `self::` prefix, and `$_converters` itself was declared as a non-
  static instance property on an abstract utility class with only
  static methods.  Both bugs are fixed: the property is `protected
  static` and the method writes to `self::$_converters`.  This was
  paper-over-able while the `___onLoaded` hook stayed dormant; now
  that the hook fires again, the no-op would have been load-bearing.
- **`BootstrapHandler::addDependencies()` no longer recurses into
  `ArrayHelper::isArray()`** — when the hook fires for `MNHcC` (the
  parent class autoloaded by `extends MNHcC` during ArrayHelper's
  own require_once), `MNHcC::___require()` returns `[]` and the
  dispatcher hands it to `addDependencies()`.  Calling
  `ArrayHelper::isArray()` from there would re-trigger autoload of
  ArrayHelper — already mid-load — and PHP fatals "Class
  ArrayHelper not found".  Switched to the PHP-native `is_array()`
  check; addDependencies needs the strict subset anyway (no
  `ArrayAccess` payloads ever flow through `___require`).

## [0.9.2] — 2026-04-27

### Added
- **`BootstrapHandler::registerPackagePath($path, $key = null, $using_default = false)`**
  — primary public name for the additive package-root registry.  Each
  call appends one more namespace root the framework autoloader will
  search for `Foo.class.php`, `Foo.interface.php`, `Foo.trait.php` files.
  Designed for third-party packages that ship `mnhcc\ml\interfaces\…` /
  `mnhcc\ml\traits\…` and need the framework's SPL loader to find them
  without going through Composer's classmap (interfaces and traits are
  not in the classmap of every consumer).
- `tests/Unit/BootstrapHandlerTest.php` — new suite covering the
  registry: keyless append, keyed entries, trailing-slash trim, alias
  identity, and the root-namespace prepend in `getIncludePaths()`.

### Deprecated
- **`BootstrapHandler::setIncludePath()`** — renamed to
  `registerPackagePath()` because the implementation has always been
  additive (`self::$_includePaths[] = …`), never a setter.  The old name
  remains as a thin alias delegating to the new one and is scheduled for
  removal in v1.0.  Existing callers (mn-hegenbarth.de's `initial.php`
  was the last in-tree consumer until 0.9.2) continue to work
  unchanged.

## [0.9.1] — 2026-04-27

### Added
- **Read-back convention on `raise()` (Stage B).**  `EventParms`'s class
  docblock now formalises the contract: listeners may mutate the bag in
  place via `set()`, and callers are free to read keys back after
  `EventManager::raise()` returns.  This makes `raise()` a light-weight
  intervention point — listener edits are visible to the caller without
  changing any existing call-site that doesn't opt in (read-back falls
  back to the caller-supplied default).  Two new tests cover the
  round-trip and the no-listener fallback path.  See
  `docs/EVENT_SYSTEM.md` §3 Stage B (now delivered).
- **WordPress-style filter chain on the event bus.**
  `EventManager::filter(string $name, mixed $value, ?EventParms $context = null)`
  threads `$value` through every registered listener in priority order
  (lower priority = earlier).  Listeners receive `($value, $context)` and
  return the next value; returning `null` is coerced to the previous
  value (WP "I did nothing" semantics).  Companion API:
  `EventManager::registerFilter($name, $callback, $priority = 10)`.  First
  production user is `ContentFilterReplacer` in ml-mvc — see that
  package's CHANGELOG for the migration.
- **Listener-exception isolation.**  Both `raise()` and `filter()` now
  catch listener-thrown exceptions and route them through
  `Error::report()` (in ml-bugcatcher) so a single bad listener no longer
  tears down the whole dispatch.  Opt back into the fail-loud legacy
  behaviour with `EventManager::setStrict(true)` (default off).
- **Test plumbing.**  `EventManager::resetTestState()` clears the listener
  registries and the strict flag; `EventManager::getListeners($name = null)`
  and `EventManager::getFilters($name = null)` give read-only access for
  test assertions.
- `docs/EVENT_SYSTEM.md` extended — Stage A (test plumbing + listener
  isolation) and Stage C (filter chain) marked delivered.

### Fixed
- **`raise()` lookup key now matches `register()` storage key** —
  `register()` stored listeners under the lowercase form returned by
  `Event::setEventName` (which calls `cleanEventName($name, true)`), but
  `raise()` looked up under the ucfirst form.  Result: listeners
  registered through the documented API never fired.  `raise()` now uses
  `cleanEventName($name, true)` to match the storage path, surfacing the
  long-dormant `Error::onTemplateCreated` listener (registered by
  ml-bugcatcher's `Error::__construct`).
- **`traits\Event` namespace resolution.**  References to bare
  `Helper::isArray` / `Helper::arrayMerge` resolved to the non-existing
  `mnhcc\ml\traits\Helper` because the trait imports `\mnhcc\ml\classes`
  but not `\mnhcc\ml\classes\Helper`.  Fixed to use the `classes\` prefix
  consistently.  Latent issue surfaced by the `raise()` keying fix above:
  with listeners actually firing for the first time, `Event::raise()` ran
  the trait's `toEventParms()` method which then fataled.
- **`ArrayHelper::inRecursive` always returned `false`.**  The
  `array_walk_recursive` callback captured the `$answer` accumulator by
  value, so every "found" signal was thrown away when the closure
  returned.  Recursive `in()` searches reported "not found" for needles
  that were genuinely present in nested levels.  `$answer` is now
  captured by reference; `testIn_recursive_findsNestedValue` covers
  both the present-needle and missing-needle paths.
- **`Helper::isJson1('')` reported empty string as valid JSON on PHP 5.6.**
  `json_decode('')` returns `null` with `JSON_ERROR_NONE` on PHP 5.6,
  while PHP 7+ correctly reports `JSON_ERROR_SYNTAX` — the helper now
  short-circuits empty / non-string input so the answer is consistent
  across supported runtimes.  `testIsJson1_falseForEmptyString` covers
  the regression.
- **`traits\Event` no longer calls deprecated `Helper::isArray()`.**
  `setParms()`, `addParms()` and `toEventParms()` now type-check via
  `ArrayHelper::isArray()` directly.  The deprecated wrapper in `Helper`
  triggers `E_USER_DEPRECATED` on every call, which PHPUnit promotes to
  an exception — so every listener invocation through `Event::raise()`
  was failing in test runs (the listener-isolation path then masked the
  real listener body and the testRaise_listenerExceptionDoesNotStopTheChain
  case never reached its third listener).  Production paths were silently
  emitting deprecation log entries on every dispatched event.

### Added
- **Event bus moved here from `ml-bugcatcher`** — `EventManager`,
  `Event` (+ trait + interface) and the base `EventParms` are now part of
  ml-core.  The bus is plain dispatcher infrastructure with no error-handling
  dependencies, so it belongs in core where any package (including the error
  handler) can build on it.  Same FQCNs (`mnhcc\ml\classes\EventManager`,
  `mnhcc\ml\classes\Event`, `mnhcc\ml\classes\EventParms`,
  `mnhcc\ml\interfaces\Event`, `mnhcc\ml\traits\Event`) — no client code
  changes required.  Specialised payloads stay with the package that produced
  them (`ExceptionEventParms` in ml-bugcatcher, `Template\EventParms` in ml-mvc).
- `docs/EVENT_SYSTEM.md` — current behaviour, gaps for the
  intervention/manipulation use case, and the planned filter-hook extension.
- `Instances` trait now exposes a `resetTestState()` hook that drops the singleton
  cache for the calling class. Consumer packages override it to clear their own
  per-request statics, giving PHPUnit a single entry point to isolate state
  between test methods.

## [0.9.0] - 2026-04-25

Initial Packagist release. Extracted from the MinimalusLayoutilus skeleton;
contains the custom SPL autoloader (`BootstrapHandler`), base classes
(`MNHcC`, `Bootstrap`, `ApplicationConfig`), helpers (`ArrayHelper`, `Helper`,
`MNHcCString`), traits (`Instances`, `NoInstances`, `MNHcC`, `Prototype`,
`Event`, `Actions`) and interfaces (`Instances`, `MNHcC`, `Prototype`,
`Parameters`, `Viewable`).
