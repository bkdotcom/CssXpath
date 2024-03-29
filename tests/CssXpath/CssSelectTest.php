<?php

namespace bdk\Test\CssXpath;

use bdk\CssXpath\CssSelect;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for CssSelect
 *
 * @covers \bdk\CssXpath\CssSelect
 */
class CssSelectTest extends TestCase
{
    /**
     * Test
     *
     * @return array of serialized logs
     */
    public function selectProvider()
    {
        return array(
            array('*', 14),
            array('div', 1),
            array('div, p', 2),
            array('div , p', 2),
            array('div ,p', 2),
            array('div, p, ul li a', 3),
            array('div#article', 1),
            array('div#article.block', 1),
            array('div#article.large.block', 1),
            array('h2', 1),
            array('div h2', 1),
            array('div > h2', 1),
            array('ul li a', 1),
            array('ul > li > a', 1),
            array('a[href=#]', 1),
            array('a[href="#"]', 1),
            array('div[id="article"]', 1),
            array('h2:contains(Article)', 1),
            array('h2:contains(Article) + p', 1),
            array('h2:contains(Article) + p:contains(Contents)', 1, 'Contents &amp; Stuff'),
            array('div p + ul', 1),
            array('ul li', 5),
            array('li ~ li', 4),
            array('li ~ li ~ li', 3),
            array('li + li', 4),
            array('li + li + li', 3),
            array('li:first-child', 1),
            array('li:last-child', 1),
            array('li:contains(One):first-child', 1),
            array('li:nth-child(2)', 1),
            array('li:nth-child(3)', 1),
            array('li:nth-child(4)', 1),
            array('li:nth-child(6)', 0),
            array('li:nth-last-child(2)', 1),
            array('li:nth-last-child(3)', 1),
            array('li:nth-last-child(4)', 1),
            array('li:nth-last-child(6)', 0),
            // array('ul li! > a', 1),
            array(':scope ul li > a', 1),
            array('.a', 2),
            array('#article', 1),
            array('[id="article"]', 1),
            array('.classa > .classb', 1),
            array('ul > li:last-child [href]', 1),
            array('[class~=large] li[class~=a]', 2),
            array('li[class]:not(.bar)', 1),
            array('div > :header', 1),
            array('.bar.a', 1),
            array('bo $ us', 0),
        );
    }

    /**
     * test selector
     *
     * @param string $selector css selector
     * @param int    $count    expected number of matches
     *
     * @return void
     *
     * @dataProvider selectProvider
     */
    public function testSelectStatic($selector, $count, $inner = null)
    {
        $html = <<<HTML
  <div id="article" class="block large">
    <h2>Article Name</h2>
    <p>Contents &amp; Stuff</p>
    <ul>
      <li class="a">One</li>
      <li class="bar">Two</li>
      <li class="bar a">Three</li>
      <li>Four</li>
      <li><a href="#">Five</a></li>
    </ul>
  </div>
  <span class="classa"><span class="classb">hi</span></span>
HTML;
        $found = CssSelect::select($html, $selector);
        self::assertSame($count, \count($found), $selector);

        if ($inner !== null) {
            self::assertSame($inner, $found[0]['innerHTML']);
        }

        $found = CssSelect::select($html, $selector, true);
        self::assertSame($count, \is_array($found) ? \count($found) : $found->length);
        if ($selector === 'bo $ us') {
            self::assertTrue(\is_array($found));
        } else {
            self::assertInstanceOf('DOMNodeList', $found);
        }

        if ($inner !== null) {
            $innerHTML = '';
            $item = $found->item(0);
            foreach ($item->childNodes as $child) {
                $innerHTML .= $item->ownerDocument->saveHTML($child);
            }
            self::assertSame($inner, $innerHTML);
        }
    }

    /**
     * test selector
     *
     * @param string $selector css selector
     * @param int    $count    expected number of matches
     *
     * @return void
     *
     * @dataProvider selectProvider
     */
    public function testSelectInstance($selector, $count, $inner = null)
    {
        $html = <<<HTML
  <div id="article" class="block large">
    <h2>Article Name</h2>
    <p>Contents &amp; Stuff</p>
    <ul>
      <li class="a">One</li>
      <li class="bar">Two</li>
      <li class="bar a">Three</li>
      <li>Four</li>
      <li><a href="#">Five</a></li>
    </ul>
  </div>
  <span class="classa"><span class="classb">hi</span></span>
HTML;

        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $cssSelect = new CssSelect($dom);

        $found = $cssSelect->select($selector);
        self::assertSame($count, \count($found));

        $found = $cssSelect->select($selector, true);
        self::assertSame($count, \is_array($found) ? \count($found) : $found->length);
        if ($selector === 'bo $ us') {
            self::assertTrue(\is_array($found));
        } else {
            self::assertInstanceOf('DOMNodeList', $found);
        }
    }

    public function testSelectFromEmpty()
    {
        $found = CssSelect::select('', '#hello');
        self::assertSame(0, \count($found));
    }
}
