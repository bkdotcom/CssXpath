<?php

/**
 * This file is part of CssXpath
 *
 * @package   CssXPath
 * @author    Brad Kent <bkfake-github@yahoo.com>
 * @license   http://opensource.org/licenses/MIT MIT
 * @copyright 2018 Brad Kent
 * @version   1.0
 *
 * @link http://www.github.com/bkdotcom/CssXpath
 */

namespace bdk\CssXpath;

/**
 * PHPUnit DOM Assertions.
 *
 * These assertions were provide with PHPUnit 3.3 - < 5
 */
trait DOMAssertionTrait
{
    /**
     * Assert the presence, absence, or count of elements in a document matching
     * the CSS $selector, regardless of the contents of those elements.
     *
     * The first argument, $selector, is the CSS selector used to match
     * the elements in the $actual document.
     *
     * The second argument, $count, can be either boolean or numeric.
     * When boolean, it asserts for presence of elements matching the selector
     * (true) or absence of elements (false).
     * When numeric, it assertsk the count of elements.
     *
     * examples:
     *   assertSelectCount("#binder", true, $xml);  // any?
     *   assertSelectCount(".binder", 3, $xml);     // exactly 3?
     *
     * @param array          $selector CSS selector
     * @param int|bool|array $count    bool, count, or array('>'=5, <=10)
     * @param mixed          $actual   HTML
     * @param string         $message  exception message
     * @param bool           $isHtml   not used
     *
     * @return void
     *
     * @link(https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.assertions.assertSelectCount
     */
    public static function assertSelectCount($selector, $count, $actual, $message = '', $isHtml = true)
    {
        self::assertSelectEquals($selector, true, $count, $actual, $message, $isHtml);
    }

    /**
     * Reports an error identified by $message
     *  if the CSS selector $selector does not match $count elements
     *  in the DOMNode $actual with the value $content.
     *
     * $count can be one of the following types:
     *   bool: Asserts for presence of elements matching the selector (TRUE) or absence of elements (FALSE).
     *   int: Asserts the count of elements.
     *   array: Asserts that the count is in a range specified by using <, >, <=, and >= as keys.
     *
     * Examples
     *   assertSelectEquals("#binder .name", "Chuck", true,  $xml);  // any?
     *   assertSelectEquals("#binder .name", "Chuck", false, $xml);  // none?
     *
     * @param string         $selector css selector
     * @param string         $content  content to match against.  may specify regex as regexp:/regexp/
     * @param int|bool|array $count    bool, integer, or array('>' => 5, '<=' => 10)
     * @param mixed          $actual   html or domdocument
     * @param string         $message  exception message
     * @param bool           $isHtml   not used
     *
     * @return void
     * @throws \PHPUnit\Framework\Exception Invalid count format.
     *
     * @link https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.assertions.assertSelectEquals
     */
    public static function assertSelectEquals($selector, $content, $count, $actual, $message = '', $isHtml = true)
    {
        $found = CssSelect::select($actual, $selector);
        $found = \array_filter($found, static function ($node) use ($content) {
            if (\is_string($content) === false) {
                return true;
            }
            if ($content === '') {
                return $node['innerHTML'] === '';
            }
            if (\preg_match('/^regexp\s*:\s*(.*)/i', $content, $matches)) {
                return \preg_match($matches[1], $node['innerHTML']) === 1;
            }
            return \strstr($node['innerHTML'], $content) !== false;
        });
        self::assertDomCount(\count($found), $count, $message);
    }

    /**
     * examples:
     *   assertSelectRegExp("#binder .name", "/Mike|Derek/", true, $xml); // any?
     *   assertSelectRegExp("#binder .name", "/Mike|Derek/", 3, $xml);    // 3?
     *
     * @param array          $selector CSS selector
     * @param string         $pattern  regex
     * @param int|bool|array $count    bool, count, or array('>'=5, <=10)
     * @param mixed          $actual   HTML or domdocument
     * @param string         $message  exception message
     * @param bool           $isHtml   not used
     *
     * @return void
     *
     * @link( https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.assertions.assertSelectRegExp, link)
     */
    public static function assertSelectRegExp($selector, $pattern, $count, $actual, $message = '', $isHtml = true)
    {
        self::assertSelectEquals($selector, 'regexp:' . $pattern, $count, $actual, $message, $isHtml);
    }

    /**
     * Assert number found elements match expected
     *
     * @param int            $countActual Num of matches actually found
     * @param int|bool|array $count       bool, count, or array('>'=5, <=10)
     * @param string         $message     Exception message
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\Exception Invalid count format.
     */
    private static function assertDomCount($countActual, $count, $message)
    {
        self::assertValidDomCount($count);
        if ($count === true) {
            $count = array('>' => 0);
        } elseif ($count === false) {
            $count = 0;
        }
        if (\is_numeric($count)) {
            self::assertEquals($count, $countActual, $message);
            return;
        }
        $countVals = \array_merge(array(
            '<' => 0,
            '<=' => 0,
            '>' => 0,
            '>=' => 0,
        ), $count);
        $count = \array_intersect_key(array(
            '<' => $countActual < $countVals['<'],
            '<=' => $countActual <= $countVals['<='],
            '>' => $countActual > $countVals['>'],
            '>=' => $countActual >= $countVals['>='],
        ), $count);
        \array_walk($count, static function ($val) use ($message) {
            self::assertTrue($val, $message);
        });
    }

    /**
     * Assert bool, int, or array supplied for count
     *
     * @param mixed $count Count value
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\Exception
     */
    private static function assertValidDomCount($count)
    {
        if (\is_bool($count) === false && \is_numeric($count) === false && \is_array($count) === false) {
            throw new \PHPUnit\Framework\Exception('Invalid count format.  Expected bool, int, or array()');
        }
        if (\is_array($count) && \array_intersect_key($count, \array_flip(array('>', '<', '>=', '<='))) === array()) {
            throw new \PHPUnit\Framework\Exception('Invalid count.  Array should contain >, >=, <, and/or <=');
        }
    }
}
