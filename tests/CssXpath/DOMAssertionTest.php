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
        self::assertSelectEquals('.name', 'fred', 0, '<div class="name"></div><div class="name">Jimmy</div>');

        self::assertSelectEquals('.array-inner > li > .t_array > .t_keyword', 'array', true, '<li class="m_log"><span class="t_array"><span class="t_keyword">array</span><span class="t_punct">(</span>
            <ul class="array-inner list-unstyled">
                <li><span class="t_key">foo</span><span class="t_operator">=&gt;</span><span class="t_string">bar</span></li>
                <li><span class="t_key">val</span><span class="t_operator">=&gt;</span><span class="t_array"><span class="t_keyword">array</span> <span class="t_recursion">*RECURSION*</span></span></li>
            </ul><span class="t_punct">)</span></span></li>');
    }

    public function testAssertSelectRegExp()
    {
        self::assertSelectRegExp('.name', '/Sam/', 1, '<div class="name">Sam</div>');
    }

    public function testInvalidCountArg()
    {
        $caughtException = false;
        $message = null;
        try {
            self::assertSelectEquals('.name', '', 'zero', '');
        } catch (\PHPUnit\Framework\Exception $e) {
            $caughtException = true;
            $message = $e->getMessage();
        }
        self::assertTrue($caughtException);
        self::assertSame('Invalid count format.  Expected bool, int, or array()', $message);


        $caughtException = false;
        $message = null;
        try {
            self::assertSelectEquals('.name', '', array(), '');
        } catch (\PHPUnit\Framework\Exception $e) {
            $caughtException = true;
            $message = $e->getMessage();
        }
        self::assertTrue($caughtException);
        self::assertSame('Invalid count.  Array should contain >, >=, <, and/or <=', $message);
    }
}
