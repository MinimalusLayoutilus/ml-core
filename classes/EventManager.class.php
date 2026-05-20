<?php

/*
 * Copyright (C) 2013 Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace mnhcc\ml\classes {

	/**
	 * Central event dispatcher — dispatches named events to registered listeners.
	 *
	 * Two complementary APIs:
	 *   - {@see raise()}    — fan-out notification ("observer pattern").  Listeners
	 *                         observe; the caller does not consume listener results.
	 *   - {@see filter()}   — value chain ("WordPress filter" pattern).  Each
	 *                         listener takes a value, returns a (possibly
	 *                         modified) value; the dispatcher threads them.
	 *
	 * Listener exceptions are isolated: a single bad listener writes to
	 * {@see Error::report()} (PHP error_log + DEBUG-overlay) but does NOT
	 * tear down the rest of the chain — set {@see setStrict(true)} to opt
	 * into the legacy fail-loud behaviour.
	 *
	 * See ml-core/docs/EVENT_SYSTEM.md for the full design + roadmap.
	 *
	 * @author carschrotter
	 */
	abstract class EventManager
	{

		/**
		 * Registered raise() listeners keyed by normalised event name.
		 * @var array
		 */
		static protected $_events = [];

		/**
		 * Registered filter() listeners keyed by normalised event name.
		 * Each entry is a list of [callable, priority, registration-index]
		 * tuples; the dispatcher sorts by priority lazily on first use.
		 * Kept separate from $_events because the contract differs (filter
		 * listeners must return a value).
		 *
		 * @var array
		 */
		static protected $_filters = [];

		/**
		 * Tracks whether the per-name filter chain has been priority-sorted
		 * since the last register.  Lazy sort lets registration stay O(1).
		 *
		 * @var array
		 */
		static protected $_filtersSorted = [];

		/**
		 * Strict mode — when true, listener exceptions bubble out of the
		 * dispatch loop (legacy behaviour).  When false (default), they are
		 * routed to Error::report() and the next listener still runs.
		 *
		 * @var bool
		 */
		static protected $_strict = false;

		/**
		 * Raises a named event, notifying all registered listeners.
		 * @param string     $name  Event name (leading "on" is stripped automatically).
		 * @param EventParms $parms Parameter bag passed to each listener.
		 */
		static public function raise($name, EventParms $parms)
		{
			$cName = self::cleanEventName($name, true);
			$parms->setEvent($cName);
			if (isset(self::$_events[$cName])) {
				foreach (self::$_events[$cName] as $index => $event) {
					try {
						$event->raise($parms, $index);
					} catch (\Exception $exc) {
						if (self::$_strict) {
							throw $exc;
						}
						self::_reportListenerFailure($cName, $exc);
					}
				}
			}
		}

		/**
		 * Apply a filter chain.  Threads $value through every registered
		 * listener for $name in priority order; each listener receives
		 * `($value, $context)` and must return the (possibly modified) value.
		 *
		 * Listener priority defaults to 10 (WordPress convention — lower
		 * numbers run earlier).  Same priority preserves registration order.
		 *
		 * Listeners returning null are coerced back to the previous $value —
		 * the WP convention that "I did nothing" is identity, not "I want
		 * to wipe the value".
		 *
		 * @param  string         $name     Filter name (leading "on" stripped).
		 * @param  mixed          $value    Initial value.
		 * @param  EventParms|null $context Optional read-only side data passed
		 *                                  to every listener as the second arg.
		 * @return mixed                    Final value after the chain.
		 */
		static public function filter($name, $value, EventParms $context = null)
		{
			$cName = self::cleanEventName($name, true);
			if (empty(self::$_filters[$cName])) {
				return $value;
			}
			self::_ensureSorted($cName);

			foreach (self::$_filters[$cName] as $entry) {
				try {
					$next = \call_user_func($entry[0], $value, $context);
					if ($next !== null) {
						$value = $next;
					}
				} catch (\Exception $exc) {
					if (self::$_strict) {
						throw $exc;
					}
					self::_reportListenerFailure($cName, $exc);
				}
			}
			return $value;
		}

		/**
		 * Register a filter listener for $name.
		 *
		 * @param  string   $name      Filter name (leading "on" stripped).
		 * @param  callable $callback  Receives ($value, ?EventParms $context),
		 *                             returns the (possibly modified) value.
		 * @param  int      $priority  Lower numbers run earlier (default 10).
		 * @return void
		 */
		static public function registerFilter($name, $callback, $priority = 10)
		{
			if (!\is_callable($callback)) {
				throw new \InvalidArgumentException(
					'Filter callback for "' . $name . '" is not callable.'
				);
			}
			$cName = self::cleanEventName($name, true);
			self::$_filters[$cName][]      = [$callback, (int) $priority];
			self::$_filtersSorted[$cName]  = false;
		}

		/**
		 * Sort the filter chain for $cName by priority (ascending).  PHP's
		 * usort is unstable on 5.6 / 7.x, so we tag a registration index onto
		 * each entry as a tiebreaker — equal priorities keep insertion order.
		 *
		 * @param  string $cName  Already-normalised event name.
		 * @return void
		 */
		static protected function _ensureSorted($cName)
		{
			if (!empty(self::$_filtersSorted[$cName])) {
				return;
			}
			$i = 0;
			foreach (self::$_filters[$cName] as &$entry) {
				$entry[2] = $i++;
			}
			unset($entry);
			\usort(self::$_filters[$cName], function ($a, $b) {
				if ($a[1] !== $b[1]) {
					return $a[1] - $b[1];
				}
				return $a[2] - $b[2];
			});
			self::$_filtersSorted[$cName] = true;
		}

		/**
		 * Route a listener exception to BugCatcher's Error::report() so it
		 * lands in the framework log and (DEBUG=on) the BugCatcher overlay.
		 * Falls back to plain error_log when ml-bugcatcher is not on the
		 * autoloader (kept resilient because the bus itself has no hard
		 * dependency on the error handler).
		 *
		 * @param  string     $eventName
		 * @param  \Exception $exc
		 * @return void
		 */
		static protected function _reportListenerFailure($eventName, \Exception $exc)
		{
			if (Helper::classExists('Error', true, false)) {
				try {
					Error::getInstance()->report(new \RuntimeException(
						'Listener for "' . $eventName . '" threw: ' . $exc->getMessage(),
						0,
						$exc
					));
					return;
				} catch (\Exception $report) {
					// fall through to error_log
				}
			}
			\error_log(
				'EventManager listener for "' . $eventName . '" threw: '
					. $exc->getMessage() . ' in ' . $exc->getFile() . ':' . $exc->getLine()
			);
		}

		/**
		 * Toggle strict mode.  In strict mode, listener exceptions bubble
		 * out of {@see raise()} / {@see filter()}.  Default off.
		 *
		 * @param  bool $value
		 * @return void
		 */
		static public function setStrict($value)
		{
			self::$_strict = (bool) $value;
		}

		/**
		 * @return bool
		 */
		static public function isStrict()
		{
			return self::$_strict;
		}

		/**
		 * Test-only helper — drops the listener registries and the strict
		 * flag.  Production code never needs this.
		 *
		 * @return void
		 */
		static public function resetTestState()
		{
			self::$_events        = [];
			self::$_filters       = [];
			self::$_filtersSorted = [];
			self::$_strict        = false;
		}

		/**
		 * Read-only access to the raise()-style listener registry.
		 *
		 * @param  string|null $name  Optional event-name filter.
		 * @return array
		 */
		static public function getListeners($name = null)
		{
			if ($name === null) {
				return self::$_events;
			}
			$cName = self::cleanEventName($name, true);
			return isset(self::$_events[$cName]) ? self::$_events[$cName] : [];
		}

		/**
		 * Read-only access to the filter listener registry, in priority order.
		 *
		 * @param  string|null $name
		 * @return array
		 */
		static public function getFilters($name = null)
		{
			if ($name === null) {
				return self::$_filters;
			}
			$cName = self::cleanEventName($name, true);
			if (empty(self::$_filters[$cName])) {
				return [];
			}
			self::_ensureSorted($cName);
			return self::$_filters[$cName];
		}

		/**
		 * Normalises an event name: strips leading "on" prefix and applies ucfirst.
		 * @param string $name   Raw event name.
		 * @param bool   $asKey  Return all-lowercase for use as an array key.
		 * @return string
		 */
		static public function cleanEventName($name, $asKey = false)
		{
			$cleanEventName = \preg_replace("~^on~i", '', $name);
			if ($asKey) {
				return \strtolower($cleanEventName);
			}
			return \ucfirst($cleanEventName);
		}

		/**
		 *
		 * @param \mnhcc\ml\classes\Event $event
		 */
		static public function register(Event $event)
		{
			return self::$_events[$event->getEventName()][] = $event;
		}
	}
}
