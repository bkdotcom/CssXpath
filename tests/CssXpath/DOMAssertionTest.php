<?php

namespace bdk\Test\CssXpath;

use bdk\CssXpath\DOMAssertionTrait;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for CssXpath
 *
 * @covers \bdk\CssXpath\DOMAssertionTrait
 */
class DOMAssertionTest extends TestCase
{
    use DOMAssertionTrait;

    public function testAssertSelectCount()
    {
        self::assertSelectCount('p', true, '<div><p>howdy</p></div>');
        self::assertSelectCount('li', false, '<div><p>howdy</p></div>');
    }

    public function testAssertSelectEquals()
    {
        self::assertSelectEquals('.name', '', array(
            '<' => 2,
            '<=' => 2,
            '>' => 0,
            '>=' => 0,
        ), '<div class="name"></div><div class="name">Jimmy</div>');
    }

    public function testAssertSelectRegExp()
    {
        self::assertSelectRegExp('.name', "/Sam/", 1, '<div class="name">Sam</div>');
    }

    public function testInvalidCountArg()
    {
        $caughtException = false;
        $message = null;
        try {
            self::assertSelectEquals('.name', '', array(), '');
        } catch (\PHPUnit\Framework\Exception $e) {
            $caughtException = true;
            $message = $e->getMessage();
        }
        self::assertTrue($caughtException);
        self::assertSame('Invalid count format', $message);
    }
}
