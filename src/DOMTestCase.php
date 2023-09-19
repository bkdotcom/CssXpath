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

use PHPUnit\Framework\TestCase;

/**
 * TestCase with DOM Assertions.
 */
abstract class DOMTestCase extends TestCase
{
    use DOMAssertionTrait;
}
