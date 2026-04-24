<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\ArrayHelper;

class ArrayHelperTest extends TestCase
{
    // --- keyExists ---

    public function testKeyExists_returnsTrueForPresentKey()
    {
        $arr = ['foo' => 'bar'];
        $this->assertTrue(ArrayHelper::keyExists('foo', $arr));
    }

    public function testKeyExists_returnsFalseForMissingKey()
    {
        $arr = ['foo' => 'bar'];
        $this->assertFalse(ArrayHelper::keyExists('baz', $arr));
    }

    public function testKeyExists_worksWithIntegerKeys()
    {
        $arr = [0 => 'zero', 1 => 'one'];
        $this->assertTrue(ArrayHelper::keyExists(0, $arr));
        $this->assertFalse(ArrayHelper::keyExists(5, $arr));
    }

    // --- get ---

    public function testGet_returnsValueForExistingKey()
    {
        $arr = ['name' => 'Alice'];
        $this->assertSame('Alice', ArrayHelper::get('name', $arr));
    }

    public function testGet_returnsDefaultForMissingKey()
    {
        $arr = [];
        $this->assertSame('default', ArrayHelper::get('missing', $arr, 'default'));
    }

    public function testGet_returnsNullWhenNoDefaultProvided()
    {
        $arr = [];
        $this->assertNull(ArrayHelper::get('missing', $arr));
    }

    public function testGet_invokesCallableDefault()
    {
        $arr = [];
        $result = ArrayHelper::get('missing', $arr, function () { return 'computed'; }, true);
        $this->assertSame('computed', $result);
    }

    public function testGet_doesNotInvokeCallableWhenCallDefaultIsFalse()
    {
        $arr = [];
        $callable = function () { return 'computed'; };
        $result = ArrayHelper::get('missing', $arr, $callable, false);
        $this->assertSame($callable, $result);
    }

    // --- implode ---

    public function testImplode_joinsWithGlue()
    {
        $this->assertSame('a,b,c', ArrayHelper::implode(['a', 'b', 'c'], ','));
    }

    public function testImplode_defaultGlueIsEmpty()
    {
        $this->assertSame('abc', ArrayHelper::implode(['a', 'b', 'c']));
    }

    public function testImplode_emptyArrayGivesEmptyString()
    {
        $this->assertSame('', ArrayHelper::implode([]));
    }

    // --- explode ---

    public function testExplode_splitsByDelimiter()
    {
        $this->assertSame(['a', 'b', 'c'], ArrayHelper::explode(',', 'a,b,c'));
    }

    public function testExplode_withLimit()
    {
        $this->assertSame(['a', 'b,c'], ArrayHelper::explode(',', 'a,b,c', 2));
    }

    // --- shift ---

    public function testShift_removesAndReturnsFirstElement()
    {
        $arr = ['first', 'second', 'third'];
        $value = ArrayHelper::shift($arr);
        $this->assertSame('first', $value);
        $this->assertSame(['second', 'third'], array_values($arr));
    }

    public function testShift_withRepetition_returnsMultipleElements()
    {
        $arr = ['a', 'b', 'c', 'd'];
        $values = ArrayHelper::shift($arr, 2);
        $this->assertSame(['a', 'b'], $values);
        $this->assertSame(['c', 'd'], array_values($arr));
    }

    public function testShift_onEmptyArray_returnsNull()
    {
        $arr = [];
        $this->assertNull(ArrayHelper::shift($arr));
    }

    // --- pop ---

    public function testPop_removesAndReturnsLastElement()
    {
        $arr = ['x', 'y', 'z'];
        $value = ArrayHelper::pop($arr);
        $this->assertSame('z', $value);
        $this->assertCount(2, $arr);
    }

    public function testPop_onEmptyArray_returnsNull()
    {
        $arr = [];
        $this->assertNull(ArrayHelper::pop($arr));
    }

    // --- count ---

    public function testCount_returnsArrayLength()
    {
        $arr = [1, 2, 3];
        $this->assertSame(3, ArrayHelper::count($arr));
    }

    public function testCount_emptyArray_returnsZero()
    {
        $arr = [];
        $this->assertSame(0, ArrayHelper::count($arr));
    }

    // --- addBefore ---

    public function testAddBefore_prependsValue()
    {
        $arr = ['b', 'c'];
        ArrayHelper::addBefore($arr, 'a');
        $this->assertSame(['a', 'b', 'c'], array_values($arr));
    }

    // --- in (with valid arrays only to avoid ml-bugcatcher exception dependency) ---

    public function testIn_returnsTrueWhenNeedlePresent()
    {
        $this->assertTrue(ArrayHelper::in('b', ['a', 'b', 'c']));
    }

    public function testIn_returnsFalseWhenNeedleAbsent()
    {
        $this->assertFalse(ArrayHelper::in('z', ['a', 'b', 'c']));
    }

    public function testIn_strictMode_doesNotCoerce()
    {
        $this->assertFalse(ArrayHelper::in('1', [1, 2, 3], true));
        $this->assertTrue(ArrayHelper::in(1, [1, 2, 3], true));
    }

    public function testIn_recursive_findsNestedValue()
    {
        $this->assertTrue(ArrayHelper::in('deep', ['a', ['b', 'deep']], false, true));
        $this->assertFalse(ArrayHelper::in('missing', ['a', ['b', 'c']], false, true));
    }

    // --- isArray ---

    public function testIsArray_trueForPlainArray()
    {
        $this->assertTrue(ArrayHelper::isArray([]));
        $this->assertTrue(ArrayHelper::isArray([1, 2, 3]));
    }

    public function testIsArray_falseForScalar()
    {
        $this->assertFalse(ArrayHelper::isArray('string'));
        $this->assertFalse(ArrayHelper::isArray(42));
        $this->assertFalse(ArrayHelper::isArray(null));
    }

    public function testIsArray_falseForNonArrayObject()
    {
        $this->assertFalse(ArrayHelper::isArray(new \stdClass()));
    }

    public function testIsArray_oneForArrayAccessObject()
    {
        $obj = new \ArrayObject(['a', 'b']);
        $this->assertSame(1, ArrayHelper::isArray($obj));
    }

    // --- toArray ---

    public function testToArray_returnsArrayUnchanged()
    {
        $arr = ['a', 'b', 'c'];
        $this->assertSame($arr, ArrayHelper::toArray($arr));
    }

    public function testToArray_wrapsScalarInArray()
    {
        $this->assertSame(['hello'], ArrayHelper::toArray('hello'));
        $this->assertSame([42], ArrayHelper::toArray(42));
    }

    public function testToArray_convertsArrayObject()
    {
        $obj = new \ArrayObject(['x', 'y']);
        $result = ArrayHelper::toArray($obj);
        $this->assertSame(['x', 'y'], $result);
    }

    // --- each ---

    public function testEach_mapsValues()
    {
        $arr = [1, 2, 3];
        $result = ArrayHelper::each($arr, function ($key, $val) {
            return $val * 2;
        });
        $this->assertSame([2, 4, 6], $result);
    }

    public function testEach_allowsKeyValueRemap()
    {
        $arr = ['a', 'b'];
        $result = ArrayHelper::each($arr, function ($key, $val) {
            return ['key' => $val, 'value' => strtoupper($val)];
        });
        $this->assertSame(['a' => 'A', 'b' => 'B'], $result);
    }
}
