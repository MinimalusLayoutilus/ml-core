<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\Helper;

class HelperTest extends TestCase
{
    // --- callOrGet ---

    public function testCallOrGet_returnsScalarAsIs()
    {
        $this->assertSame('hello', Helper::callOrGet('hello'));
        $this->assertSame(42, Helper::callOrGet(42));
        $this->assertNull(Helper::callOrGet(null));
    }

    public function testCallOrGet_invokesCallable()
    {
        $result = Helper::callOrGet(function () { return 'invoked'; });
        $this->assertSame('invoked', $result);
    }

    public function testCallOrGet_invokesClosureWithNoArgs()
    {
        $counter = 0;
        Helper::callOrGet(function () use (&$counter) { $counter++; });
        $this->assertSame(1, $counter);
    }

    // --- toggle ---

    public function testToggle_wrapsContentInDiv()
    {
        $result = Helper::toggle('content here');
        $this->assertSame('<div class="preview_toggle">content here</div>', $result);
    }

    public function testToggle_usesCustomContainer()
    {
        $result = Helper::toggle('inner', 'span');
        $this->assertSame('<span class="preview_toggle">inner</span>', $result);
    }

    // --- cssNameClean ---

    public function testCssNameClean_returnsValidNameUnchanged()
    {
        $this->assertSame('validName', Helper::cssNameClean('validName'));
    }

    public function testCssNameClean_replacesSpacesWithUnderscore()
    {
        $result = Helper::cssNameClean('my class name');
        $this->assertSame('my_class_name', $result);
    }

    public function testCssNameClean_collapseConsecutiveUnderscores()
    {
        $result = Helper::cssNameClean('my  class');
        $this->assertSame('my_class', $result);
    }

    public function testCssNameClean_replacesSpecialCharsWithUnderscore()
    {
        $result = Helper::cssNameClean('my-class');
        $this->assertSame('my_class', $result);
    }

    public function testCssNameClean_prependsCustomStringWhenStartsWithDigit()
    {
        $result = Helper::cssNameClean('1invalid', 'prefix_');
        $this->assertSame('prefix_1invalid', $result);
    }

    public function testCssNameClean_prependsCustomStringWhenStartsWithUnderscore()
    {
        $result = Helper::cssNameClean('_bad', 'pre');
        $this->assertSame('pre_bad', $result);
    }

    // --- isJson1 ---

    public function testIsJson1_trueForValidJsonObject()
    {
        $this->assertTrue(Helper::isJson1('{"key":"value"}'));
    }

    public function testIsJson1_trueForValidJsonArray()
    {
        $this->assertTrue(Helper::isJson1('[1,2,3]'));
    }

    public function testIsJson1_trueForJsonString()
    {
        $this->assertTrue(Helper::isJson1('"hello"'));
    }

    public function testIsJson1_trueForJsonNull()
    {
        $this->assertTrue(Helper::isJson1('null'));
    }

    public function testIsJson1_falseForInvalidJson()
    {
        $this->assertFalse(Helper::isJson1('{invalid}'));
        $this->assertFalse(Helper::isJson1('plain string'));
    }

    public function testIsJson1_falseForEmptyString()
    {
        $this->assertFalse(Helper::isJson1(''));
    }

    // --- generateLegitimer ---

    public function testGenerateLegitimer_defaultLengthIsThree()
    {
        $result = Helper::generateLegitimer();
        $this->assertSame(3, strlen($result));
    }

    public function testGenerateLegitimer_returnsAlphabeticString()
    {
        $result = Helper::generateLegitimer(5);
        $this->assertRegExp('/^[a-z]+$/', $result);
    }

    public function testGenerateLegitimer_respectsCustomLength()
    {
        $result = Helper::generateLegitimer(7);
        $this->assertSame(7, strlen($result));
    }

    public function testGenerateLegitimer_invalidLengthFallsBackToThree()
    {
        $this->assertSame(3, strlen(Helper::generateLegitimer(0)));
        $this->assertSame(3, strlen(Helper::generateLegitimer(-1)));
    }
}
