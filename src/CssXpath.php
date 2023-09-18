<?php

/**
 * This file is part of CssXpath
 *
 * @package   CssXPath
 * @author    Brad Kent <bkfake-github@yahoo.com>
 * @license   http://opensource.org/licenses/MIT MIT
 * @copyright 2018-2023 Brad Kent
 * @version   1.0
 *
 * @link http://www.github.com/bkdotcom/CssXpath
 */

namespace bdk\CssXpath;

/**
 * Convert CSS selector to xpath selector
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/Reference#Selectors
 * @see https://github.com/rdsubhas/css-xpath-converter/blob/gh-pages/app.js
 * @see https://msdn.microsoft.com/en-us/library/ms256086(v=vs.110).aspx
 * @see https://en.wikibooks.org/wiki/XPath/CSS_Equivalents
 * @see http://ricostacruz.com/cheatsheets/xpath.html
 */
class CssXpath
{
    private static $cache = array();

    /** @var string[] attribute && :contains() substitutions */
    private static $strings = array();
    private static $clearStrings = true;

	/**
	 * css -> xpath
	 *
	 * @param string $selector CSS selector
	 *
	 * @return string
	 */
	public static function cssToXpath($selector)
	{
        if (isset(self::$cache[$selector])) {
            return self::$cache[$selector];
        }

        if (self::$clearStrings) {
            self::$strings = array();
        }

        $xpath = self::processRegexs($selector);
        $xpath = \preg_match('/^\/\//', $xpath)
        	? $xpath
        	: '//' . $xpath;
        $xpath = \preg_replace('#/{4}#', '', $xpath);
        self::$cache[$selector] = $xpath;
        return $xpath;
	}

    /**
     * Handle attributes reges
     *
     * @param array $matches preg_match maches
     *
     * @return string
     */
    protected static function callbackAttribs($matches)
    {
        // Attribute selectors
        $return = '[@' . $matches[2] . ']';
        $regex = '/^(?<name>.*?)(?<comparison>=|~=|\|=|\^=|\$=|\*=|!=)[\'"]?(?<value>.*?)[\'"]?$/';
        $matchesInner = array();
        if (\preg_match($regex, $matches[2], $matchesInner)) {
            $map = array(
                '!=' => '[@%s!="%s"]',
                '$=' => '[ends-with(@%s, "%s")]',
                '*=' => '[contains(@%s, "%s")]',
                '=' => '[@%s="%s"]',
                '^=' => '[starts-with(@%s, "%s")]',
                '|=' => '[starts-with(concat(@%s, "-"), "%s-")]',
                '~=' => '[contains(concat(" ", @%s, " "), " %s ")]',
            );
            $return = \sprintf($map[$matchesInner['comparison']], $matchesInner['name'], $matchesInner['value']);
        }
        self::$strings[] = ($matches[1] ? '*' : '') . $return;
        return ($matches[1] ? ' ' : '') . '[{' . (\count(self::$strings) - 1) . '}]';
    }

    /**
     * Itterate over regular expressions transforming css selector
     *
     * @param string $cssSelector CSS selector
     *
     * @return string
     */
    private static function processRegexs($cssSelector)
    {
        $regexs = self::regexs();
        $xpath = ' ' . $cssSelector;
        foreach ($regexs as $regCallback) {
            $limit = isset($regCallback[2])
                ? $regCallback[2]
                : -1;
            if ($limit < 0) {
                $xpath = \preg_replace_callback($regCallback[0], $regCallback[1], $xpath);
                continue;
            }
            $count = 0;
            do {
                $xpath = \preg_replace_callback($regCallback[0], $regCallback[1], $xpath, $limit, $count);
            } while ($count > 0);
        }
        return $xpath;
    }

    /**
     * Return regular expressions to process css selector
     *
     * @return array
     */
    private static function regexs()
    {
        /*
            The order in which items are replaced is IMPORTANT!
        */
        return array(
            /*
                First handle attributes and :contains()
                these may contain "," " ", " > ", and other "special" strings
            */
            array('/([\s]?)\[(.*?)\]/', array(\get_called_class(), 'callbackAttribs')),
            // :contains(foo)  // a jquery thing
            array('/:contains\((.*?)\)/', static function ($matches) {
                self::$strings[] = '[contains(text(), "' . $matches[1] . '")]';
                return '[{' . (\count(self::$strings) - 1) . '}]';
            }),
            array('/([\s]?):not\((.*?)\)/', static function ($matches) {
                // this currently works for simple :not(.classname)
                // unsure of other selectors
                self::$clearStrings = false;
                $xpathNot = self::cssToXpath($matches[2]);
                self::$clearStrings = true;
                $xpathNot = \preg_replace('#^//\*\[(.+)\]#', '$1', $xpathNot);
                self::$strings[] = ($matches[1] ? '*' : '') . '[not(' . $xpathNot . ')]';
                return '[{' . (\count(self::$strings) - 1) . '}]';
            }),
            // All blocks of 2 or more spaces
            array('/\s{2,}/', static function () {
                return ' ';
            }),
            // additional selectors (comma seperated)
            array('/\s*,\s*/', static function () {
                return '|//';
            }),
            // input pseudo selectors
            array(
                '/:(text|password|checkbox|radio|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/',
                static function ($matches) {
                    return '[@type="' . $matches[1] . '"]';
                },
            ),
            array('/([\s]?):button/', static function ($matches) {
                // button or input[@type="button"]
                self::$strings[] = ($matches[1] ? '*' : '') . '[self::button or @type="button"]';
                return '[{' . (\count(self::$strings) - 1) . '}]';
            }),
            array('/([\s]?):input/', static function ($matches) {
                self::$strings[] = ($matches[1] ? '*' : '') . '[self::input or self::select or self::textarea or self::button]';
                return '[{' . (\count(self::$strings) - 1) . '}]';
            }),
            array('/([\s]?):submit/', static function ($matches) {
                // input[type="submit"]   button[@type="submit"]  button[not(@type)]
                self::$strings[] = ($matches[1] ? '*' : '') . '[@type="submit" or (self::button and not(@type))]';
                return '[{' . (\count(self::$strings) - 1) . '}]';
            }),
            array('/:header/', static function () {
                self::$strings[] = '*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]';
                return '[{' . (\count(self::$strings) - 1) . '}]';
            }),
            array('/:(autofocus|checked|disabled|required|selected)/', static function ($matches) {
                return '[@' . $matches[1] . ']';
            }),
            array('/:autocomplete/', static function () {
                return '[@autocomplete="on"]';
            }),
            // :nth-child(n)
            array('/(\S*):nth-child\((\d+)\)/', static function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    . '[' . $matches[2] . ']';
            }),
            // :nth-last-child(n)
            array('/(\S*):nth-last-child\((\d+)\)/', static function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    . '[position()=(last()-(' . $matches[2] . '-1))]';
            }),
            // :last-child
            array('/(\S*):last-child/', static function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    . '[last()]';
            }),
            // :first-child
            array('/(\S*):first-child/', static function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    . '[1]';
            }),
            // Adjacent "sibling" selectors
            array('/\s*\+\s*([^\s]+)/', static function ($matches) {
                return '/following-sibling::' . $matches[1] . '[1]';
            }),
            // General "sibling" selectors
            array('/\s*~\s*([^\s]+)/', static function ($matches) {
                return '/following-sibling::' . $matches[1];
            }),
            // "child" selectors
            array('/\s*>\s*/', static function () {
                return '/';
            }),
            // Remaining Spaces
            array('/\s/', static function () {
                return '//';
            }),
            // #id
            array('/([a-z0-9\]]?)#([a-z][-a-z0-9_]+)/i', static function ($matches) {
                return $matches[1]
                    . ($matches[1] ? '' : '*')
                    . '[@id="' . $matches[2] . '"]';
            }),
            // .className
            // tricky.  without limiting the replacement, the first group will be empty for the 2nd class
            // test case:
            //    foo.classa.classb
            array('/([a-z0-9\]]?)\.(-?[_a-z]+[_a-z0-9-]*)/i', static function ($matches) {
                return $matches[1]
                    . ($matches[1] ? '' : '*')
                    . '[contains(concat(" ", normalize-space(@class), " "), " ' . $matches[2] . ' ")]';
            }, 1),
            array('/:scope/', static function () {
                return '//';
            }),
            // The Relational Pseudo-class: :has()
            // https://www.w3.org/TR/selectors-4/#has-pseudo
            // E! : https://www.w3.org/TR/selectors4/
            array('/^.+!.+$/', static function ($matches) {
                $subSelectors = \explode(',', $matches[0]);
                foreach ($subSelectors as $i => $subSelector) {
                    $parts = \explode('!', $subSelector);
                    $subSelector = \array_shift($parts);
                    if (\preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)) {
                        $results = $matches[0];
                        $results[] = \str_repeat('/..', \count($results) - 2);
                        $subSelector .= \implode('', $results);
                    }
                    $subSelectors[$i] = $subSelector;
                }
                return \implode(',', $subSelectors);
            }),
            // Restore strings
            array('/\[\{(\d+)\}\]/', static function ($matches) {
                return self::$strings[$matches[1]];
            }),
        );
    }
}
