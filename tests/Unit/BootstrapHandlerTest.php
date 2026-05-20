<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\BootstrapHandler;

/**
 * Tests for BootstrapHandler's public registry API.
 *
 * Scope is intentionally narrow to the surface that ml-core consumers
 * (skeleton, mn-hegenbarth.de, ml-* packages) actually call.  The boot
 * sequence itself is covered by integration tests in the skeleton.
 */
class BootstrapHandlerTest extends TestCase
{
    /**
     * Reflection handle on BootstrapHandler::$_includePaths so each test
     * starts from a known-empty registry.  PHP 5.6 has no first-class
     * reset hook on the class — the property is protected static.
     *
     * @var \ReflectionProperty
     */
    private $_includePathsRef;

    protected function setUp(): void
    {
        $this->_includePathsRef = new \ReflectionProperty(
            'mnhcc\\ml\\classes\\BootstrapHandler',
            '_includePaths'
        );
        $this->_includePathsRef->setAccessible(true);
        $this->_includePathsRef->setValue(null, []);
    }

    protected function tearDown(): void
    {
        // Leave the registry empty so other test classes that touch
        // BootstrapHandler do not inherit residue.
        $this->_includePathsRef->setValue(null, []);
    }

    public function testRegisterPackagePath_appendsKeyless()
    {
        BootstrapHandler::registerPackagePath('/srv/pkg-one');
        BootstrapHandler::registerPackagePath('/srv/pkg-two');

        $registry = $this->_includePathsRef->getValue();
        $this->assertCount(2, $registry, 'two keyless entries must coexist');
        $this->assertSame('/srv/pkg-one', $registry[0]['path']);
        $this->assertSame('/srv/pkg-two', $registry[1]['path']);
        $this->assertTrue($registry[0]['using_default']);
        $this->assertTrue($registry[1]['using_default']);
    }

    public function testRegisterPackagePath_keyedEntryRespectsUsingDefault()
    {
        BootstrapHandler::registerPackagePath('/srv/pkg-keyed', 'namespace.foo', false);
        BootstrapHandler::registerPackagePath('/srv/pkg-default', 'namespace.bar', true);

        $registry = $this->_includePathsRef->getValue();
        $this->assertSame('/srv/pkg-keyed', $registry['namespace.foo']['path']);
        $this->assertFalse($registry['namespace.foo']['using_default']);
        $this->assertSame('/srv/pkg-default', $registry['namespace.bar']['path']);
        $this->assertTrue($registry['namespace.bar']['using_default']);
    }

    public function testRegisterPackagePath_trimsTrailingSlashes()
    {
        BootstrapHandler::registerPackagePath('/srv/pkg/');
        BootstrapHandler::registerPackagePath('/srv/pkg-back\\');

        $registry = $this->_includePathsRef->getValue();
        $this->assertSame('/srv/pkg',      $registry[0]['path']);
        $this->assertSame('/srv/pkg-back', $registry[1]['path']);
    }

    /**
     * The deprecated alias must produce byte-identical state to the new
     * method — otherwise existing callers (mn-hegenbarth.de's initial.php
     * was the last in-tree consumer until 0.9.2) silently start
     * misbehaving when the alias keeps being used.
     */
    public function testSetIncludePath_aliasMatchesRegisterPackagePath()
    {
        BootstrapHandler::setIncludePath('/srv/pkg-via-legacy');
        BootstrapHandler::setIncludePath('/srv/pkg-via-legacy-keyed', 'legacy.key', true);

        $viaLegacy = $this->_includePathsRef->getValue();

        // Reset, then replay through the new method.
        $this->_includePathsRef->setValue(null, []);
        BootstrapHandler::registerPackagePath('/srv/pkg-via-legacy');
        BootstrapHandler::registerPackagePath('/srv/pkg-via-legacy-keyed', 'legacy.key', true);

        $viaNew = $this->_includePathsRef->getValue();

        $this->assertEquals(
            $viaLegacy,
            $viaNew,
            'setIncludePath() must remain a faithful alias of registerPackagePath()'
        );
    }

    public function testGetIncludePaths_prependsRootNamespaceEntry()
    {
        BootstrapHandler::registerPackagePath('/srv/extra');

        $paths = BootstrapHandler::getIncludePaths();
        $this->assertCount(2, $paths, 'root namespace plus the one extra');

        // The extra entry must still be present under its numeric key.
        $extra = null;
        foreach ($paths as $key => $entry) {
            if (isset($entry['path']) && $entry['path'] === '/srv/extra') {
                $extra = $entry;
                break;
            }
        }
        $this->assertNotNull($extra, 'extra package path must survive getIncludePaths() merge');
        $this->assertTrue($extra['using_default']);
    }
}
