<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\EventManager;
use mnhcc\ml\classes\EventParms;

/**
 * Class-level recorder for testRaise_listenerExceptionDoesNotStopTheChain —
 * used in lieu of closure-captured locals because PHPUnit's exception
 * handling and the Error singleton's set_exception_handler can interact
 * unpredictably with closures that share state across listener calls.
 */
class _RaiseHits {
    public static $values = [];
    public static function pushFirst() { self::$values[] = 'first'; }
    public static function pushThird() { self::$values[] = 'third'; }
}

class EventManagerTest extends TestCase
{
    /** @var int */
    private $_obEntryLevel;

    protected function setUp()
    {
        EventManager::resetTestState();
        $this->_obEntryLevel = ob_get_level();
    }

    protected function tearDown()
    {
        // _reportListenerFailure may instantiate Error, whose __construct
        // opens an ob_start() for blank-screen protection that's never
        // paired with a close in our unit context.  Drain orphans so
        // PHPUnit does not flag the test as Risky.
        while (ob_get_level() > $this->_obEntryLevel) {
            ob_end_clean();
        }
        EventManager::resetTestState();
    }

    // --- cleanEventName ---

    /**
     * @dataProvider provideCleanEventNameCases
     */
    public function testCleanEventName_stripsOnPrefixAndAppliesCase($input, $asKey, $expected)
    {
        $this->assertSame($expected, EventManager::cleanEventName($input, $asKey));
    }

    public function provideCleanEventNameCases()
    {
        return [
            'on prefix ucfirst'            => ['onClick',    false, 'Click'],
            'on prefix as key (lowercase)' => ['onClick',    true,  'click'],
            'onchange as key'              => ['onchange',   true,  'change'],
            'onSubmit ucfirst'             => ['onSubmit',   false, 'Submit'],
            'no on prefix ucfirst'         => ['change',     false, 'Change'],
            'no on prefix as key'          => ['change',     true,  'change'],
            'uppercase ON stripped'        => ['ONload',     false, 'Load'],
            'all lowercase result'         => ['onkeydown',  false, 'Keydown'],
            'as key all lowercase'         => ['onkeydown',  true,  'keydown'],
        ];
    }

    // --- filter() / registerFilter() ---

    public function testFilter_noListeners_returnsValueUnchanged()
    {
        $this->assertSame('hello', EventManager::filter('demo', 'hello'));
        $this->assertSame(42, EventManager::filter('demo', 42));
    }

    public function testFilter_singleListenerThreadsValue()
    {
        EventManager::registerFilter('demo', function ($value) {
            return $value . '-modified';
        });

        $this->assertSame('hello-modified', EventManager::filter('demo', 'hello'));
    }

    public function testFilter_chainsListenersInRegistrationOrderAtSamePriority()
    {
        EventManager::registerFilter('demo', function ($v) { return $v . 'A'; });
        EventManager::registerFilter('demo', function ($v) { return $v . 'B'; });
        EventManager::registerFilter('demo', function ($v) { return $v . 'C'; });

        $this->assertSame('startABC', EventManager::filter('demo', 'start'));
    }

    public function testFilter_lowerPriorityRunsEarlier()
    {
        EventManager::registerFilter('demo', function ($v) { return $v . 'late';   }, 100);
        EventManager::registerFilter('demo', function ($v) { return $v . 'early '; }, 1);
        EventManager::registerFilter('demo', function ($v) { return $v . 'mid ';   }, 10);

        $this->assertSame('xearly mid late', EventManager::filter('demo', 'x'));
    }

    public function testFilter_listenerReturningNullPreservesPreviousValue()
    {
        // WordPress convention: returning null means "I did nothing".
        EventManager::registerFilter('demo', function ($v) { return $v . 'A'; });
        EventManager::registerFilter('demo', function ($v) { return null; }); // no-op
        EventManager::registerFilter('demo', function ($v) { return $v . 'C'; });

        $this->assertSame('startAC', EventManager::filter('demo', 'start'));
    }

    public function testFilter_passesContextAsSecondArg()
    {
        $captured = null;
        EventManager::registerFilter('demo', function ($v, $ctx) use (&$captured) {
            $captured = $ctx;
            return $v;
        });

        $context = new EventParms(['key' => 'value']);
        EventManager::filter('demo', 'x', $context);

        $this->assertSame($context, $captured,
            'filter() must hand the EventParms context through unchanged');
    }

    public function testRegisterFilter_throwsOnNonCallable()
    {
        $this->expectException(\InvalidArgumentException::class);
        EventManager::registerFilter('demo', 'not_a_real_function_zzz');
    }

    public function testFilter_listenerExceptionIsReportedAndChainContinues()
    {
        EventManager::registerFilter('demo', function ($v) { return $v . 'before'; });
        EventManager::registerFilter('demo', function ($v) {
            throw new \RuntimeException('boom');
        });
        EventManager::registerFilter('demo', function ($v) { return $v . '-after'; });

        // Default (non-strict) mode: exception in listener 2 must NOT prevent
        // listener 3 from running.  The throwing listener returns nothing →
        // value passes through unchanged.
        $this->assertSame('xbefore-after', EventManager::filter('demo', 'x'));
    }

    public function testFilter_strictModeBubblesListenerExceptions()
    {
        EventManager::setStrict(true);
        EventManager::registerFilter('demo', function ($v) {
            throw new \RuntimeException('boom');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');
        EventManager::filter('demo', 'x');
    }

    public function testGetFilters_returnsListPriorityOrdered()
    {
        $cb1 = function ($v) { return $v; };
        $cb2 = function ($v) { return $v; };
        $cb3 = function ($v) { return $v; };

        EventManager::registerFilter('demo', $cb1, 100);
        EventManager::registerFilter('demo', $cb2, 1);
        EventManager::registerFilter('demo', $cb3, 10);

        $entries = EventManager::getFilters('demo');
        $this->assertCount(3, $entries);
        $this->assertSame($cb2, $entries[0][0], 'priority 1 first');
        $this->assertSame($cb3, $entries[1][0], 'priority 10 second');
        $this->assertSame($cb1, $entries[2][0], 'priority 100 last');
    }

    public function testResetTestState_clearsEverything()
    {
        EventManager::registerFilter('a', function ($v) { return $v; });
        EventManager::register(new \mnhcc\ml\classes\Event(function () {}, 'b'));
        EventManager::setStrict(true);

        EventManager::resetTestState();

        $this->assertSame([], EventManager::getFilters());
        $this->assertSame([], EventManager::getListeners());
        $this->assertFalse(EventManager::isStrict());
    }

    // --- raise() listener-exception isolation ---

    public function testRaise_listenerExceptionDoesNotStopTheChain()
    {
        // Sanity: a throwing listener in the middle must not prevent the
        // subsequent listener from running.  We track via a class-level
        // static instead of closure-captured locals so the assertion is
        // robust against PHPUnit's exception-handler interactions when
        // _reportListenerFailure boots Error::getInstance() (which
        // installs its own set_exception_handler).
        \mnhcc\ml\tests\Unit\_RaiseHits::$values = [];
        EventManager::register(new \mnhcc\ml\classes\Event(
            ['mnhcc\\ml\\tests\\Unit\\_RaiseHits', 'pushFirst'], 'demo'
        ));
        EventManager::register(new \mnhcc\ml\classes\Event(function () {
            throw new \RuntimeException('boom');
        }, 'demo'));
        EventManager::register(new \mnhcc\ml\classes\Event(
            ['mnhcc\\ml\\tests\\Unit\\_RaiseHits', 'pushThird'], 'demo'
        ));

        EventManager::raise('demo', new EventParms([]));
        $this->assertContains('third', \mnhcc\ml\tests\Unit\_RaiseHits::$values,
            'third listener must still run after middle listener throws');
    }

    // --- raise() read-back convention (Stage B) ---

    public function testRaise_listenerMutationVisibleToCallerAfterRaise()
    {
        // Stage B convention: a listener may call $parms->set(...) to amend
        // the bag in place; the caller is free to read the (possibly mutated)
        // value back after raise() returns.  This is the contract documented
        // in EventParms's class docblock.
        EventManager::register(new \mnhcc\ml\classes\Event(function (EventParms $parms) {
            $parms->set('attr', ['rel' => 'stylesheet', 'data-mark' => 'patched']);
        }, 'renderHead'));

        $parms = new EventParms(['type' => 'style', 'attr' => ['rel' => 'stylesheet']]);
        EventManager::raise('renderHead', $parms);

        $this->assertSame(
            ['rel' => 'stylesheet', 'data-mark' => 'patched'],
            $parms->get('attr'),
            'caller must see the listener mutation when reading attr back'
        );
        $this->assertSame('style', $parms->get('type'),
            'unmutated keys must keep their caller-supplied value');
    }

    public function testRaise_callerReadBackFallsBackWhenNoListenerMutates()
    {
        // No listener registered for 'renderHead'.  The read-back idiom
        // `$x = $parms->get('key', $default)` must hand the caller's own
        // value straight back through, so callsites that adopt read-back do
        // not regress when no listener intervenes.
        $original = ['rel' => 'stylesheet', 'href' => '/a.css'];
        $parms    = new EventParms(['attr' => $original]);

        EventManager::raise('renderHead', $parms);

        $this->assertSame($original, $parms->get('attr', $original));
    }
}
