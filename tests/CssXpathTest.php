<?php

use bdk\CssXpath\CssXpath;

/**
 * PHPUnit tests for CssXpath
 */
class CssXpathTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test
     *
     * @return array of serialized logs
     */
    public function cssToXpathProvider()
    {
        return array(
            array('foo',                '//foo'),
            array('foo, bar',           '//foo|//bar'),
            array('foo bar',            '//foo//bar'),
            array('foo    bar',         '//foo//bar'),
            array('foo > bar',          '//foo/bar'),
            array('foo >bar',           '//foo/bar'),
            array('foo>bar',            '//foo/bar'),
            array('foo> bar',           '//foo/bar'),
            array('div#foo',            '//div[@id="foo"]'),
            array('#foo',               '//*[@id="foo"]'),
            array('#foo.myclass',       '//*[@id="foo"][contains(concat(" ", normalize-space(@class), " "), " myclass ")]'),
            array('div.foo',            '//div[contains(concat(" ", normalize-space(@class), " "), " foo ")]'),
            array('.foo',               '//*[contains(concat(" ", normalize-space(@class), " "), " foo ")]'),
            // attribute tests
            array('[foo~=bar]',         '//*[contains(concat(" ", @foo, " "), " bar ")]'),
            array('[foo|=bar]',         '//*[starts-with(concat(@foo, "-"), "bar-")]'),
            array('[foo^=bar]',         '//*[starts-with(@foo, "bar")]'),
            array('[foo$=bar]',         '//*[ends-with(@foo, "bar")]'),
            array('[foo*=bar]',         '//*[contains(@foo, "bar")]'),

            array('[id]',               '//*[@id]'),
            array('[id=bar]',           '//*[@id="bar"]'),
            array('foo[id=bar]',        '//foo[@id="bar"]'),
            array('[style=color: red; border: 1px solid black;]',       '//*[@style="color: red; border: 1px solid black;"]'),
            array('foo[style=color: red; border: 1px solid black;]',    '//foo[@style="color: red; border: 1px solid black;"]'),
            // array(':button',            '//input[@type="button"]'),
            // array(':submit',            '//input[@type="submit"]'),
            array('textarea',           '//textarea'),
            array(':first-child',       '//*[1]'),
            array('div:first-child',    '//div[1]'),
            array(':last-child',        '//*[last()]'),
            array('div:last-child',     '//div[last()]'),
            array(':nth-child(2)',      '//*[2]'),
            array('div:nth-child(2)',   '//div[2]'),
            array(':nth-last-child(2)', '//*[position()=(last()-(2-1))]'),
            array('foo + bar',          '//foo/following-sibling::bar[1]'),
            array('li:contains(Foo)',   '//li[contains(text(), "Foo")]'),
            array('foo bar baz',        '//foo//bar//baz'),
            array('foo + bar + baz',    '//foo/following-sibling::bar[1]/following-sibling::baz[1]'),
            array('foo > bar > baz',    '//foo/bar/baz'),
            array('p ~ p ~ p',          '//p/following-sibling::p/following-sibling::p'),
            array('div#article p em',   '//div[@id="article"]//p//em'),
            array('div.foo:first-child','//div[contains(concat(" ", normalize-space(@class), " "), " foo ")][1]'),
            array('form#login > input[type=hidden]._method', '//form[@id="login"]/input[@type="hidden"][contains(concat(" ", normalize-space(@class), " "), " _method ")]'),
            # https://github.com/tj/php-selector/issues/14
            array('.classa > .classb',  '//*[contains(concat(" ", normalize-space(@class), " "), " classa ")]/*[contains(concat(" ", normalize-space(@class), " "), " classb ")]'),
            array('ul > li:first-child', '//ul/li[1]'),
        );
    }

    /**
     * Test
     *
     * @param string $selector css selector
     * @param string $xpath    expected xpath
     *
     * @return       void
     * @dataProvider cssToXpathProvider
     */
    public function testCssToXpath($selector, $xpath)
    {
        $xpathGenerated = CssXpath::cssToXpath($selector);
        $this->assertSame($xpath, $xpathGenerated);
    }
}
