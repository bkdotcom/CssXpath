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

	/**
	 * css -> xpath
	 *
	 * @param string $selector css selector
	 *
	 * @return string
	 */
	public static function cssToXpath($selector)
	{
        if (isset(self::$cache[$selector])) {
            return self::$cache[$selector];
        }

        $xpath = ' ' . $selector;
        $strings = array();  // attribute && :contains() substitutions

        /*
        	The order in which items are replaced is IMPORTANT!
        */
        $regexs = array(
            /*
                First handle attributes and :contains()
                these may contain "," " ", " > ", and other "special" strings
            */
            array('/([\s]?)\[(.*?)\]/', function ($matches) use (&$strings) {
                // Attribute selectors
                $return = '[@' . $matches[2] . ']';
                if (preg_match('/^(.*?)(=|~=|\|=|\^=|\$=|\*=|!=)[\'"]?(.*?)[\'"]?$/', $matches[2], $matchesInner)) {
                    $name       = $matchesInner[1];
                    $comparison = $matchesInner[2];
                    $value      = $matchesInner[3];
                    switch ($comparison) {
                        case '=':
                            $return = '[@'.$name.'="'.$value.'"]';
                            break;
                        case '~=':
                            // whitespace separated
                            $return = '[contains(concat(" ", @'.$name.', " "), " '.$value.' ")]';
                            break;
                        case '|=':
                            // equals or begins with, followed by -
                            $return = '[starts-with(concat(@'.$name.', "-"), "'.$value.'-")]';
                            break;
                        case '^=':
                            // begins with
                            $return = '[starts-with(@'.$name.', "'.$value.'")]';
                            break;
                        case '$=':
                            // ends with
                            // $return = '[substring(@'.$name.',string-length(@'.$name.')-'.(strlen($value)-1).')="'.$value.'"]';
                            $return = '[ends-with(@'.$name.', "'.$value.'")]';
                            break;
                        case '*=':
                            // contains
                            $return = '[contains(@'.$name.', "'.$value.'")]';
                            break;
                        case '!=':
                            // negate  (jquery)
                            // equivalent to :not([attr='value']).
                            $return = '[@'.$name.'!="'.$value.'"]';
                            break;
                    }
                }
                $strings[] = ($matches[1] ? '*' : '').$return;
                return ($matches[1]?' ':'').'[{'.(count($strings)-1).'}]';
            }),
            // :contains(foo)  // a jquery thing
            array('/:contains\((.*?)\)/', function ($matches) use (&$strings) {
                $strings[] = '[contains(text(), "'.$matches[1].'")]';
                return '[{'.(count($strings)-1).'}]';
            }),
            array('/([\s]?):not\((.*?)\)/', function ($matches) use (&$strings) {
                // this currently works for simple :not(.classname)
                // unsure of other selectors
                $xpathNot = \bdk\CssXpath\CssXpath::cssToXpath($matches[2]);
                $xpathNot = preg_replace('#^//\*\[(.+)\]#', '$1', $xpathNot);
                $strings[] = ($matches[1] ? '*' : '').'[not('.$xpathNot.')]';
                return '[{'.(count($strings)-1).'}]';
            }),
            // All blocks of 2 or more spaces
            array('/\s{2,}/', function () {
                return ' ';
            }),
            // additional selectors (comma seperated)
            array('/\s*,\s*/', function () {
                return '|//';
            }),
            // input pseudo selectors
            array(
                '/:(text|password|checkbox|radio|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/',
                function ($matches) {
                    return '[@type="'.$matches[1].'"]';
                }
            ),
            array('/([\s]?):button/', function ($matches) use (&$strings) {
                // button or input[@type="button"]
                $strings[] = ($matches[1] ? '*' : '').'[self::button or @type="button"]';
                return '[{'.(count($strings)-1).'}]';
            }),
            array('/([\s]?):input/', function () {
                $strings[] = ($matches[1] ? '*' : '').'[self::input or self::select or self::textarea or self::button]';
                return '[{'.(count($strings)-1).'}]';
            }),
            array('/([\s]?):submit/', function ($matches) use (&$strings) {
                // input[type="submit"]   button[@type="submit"]  button[not(@type)]
                $strings[] = ($matches[1] ? '*' : '').'[@type="submit" or (self::button and not(@type))]';
                return '[{'.(count($strings)-1).'}]';
            }),
            array('/:header/', function () use (&$strings) {
                $strings[] = '*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]';
                return '[{'.(count($strings)-1).'}]';
            }),
            array('/:(autofocus|checked|disabled|required|selected)/', function ($matches) {
                return '[@'.$matches[1].']';
            }),
            array('/:autocomplete/', function ($matches) {
                return '[@autocomplete="on"]';
            }),
            // :nth-child(n)
            array('/(\S*):nth-child\((\d+)\)/', function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    .'[' . $matches[2] . ']';
            }),
            // :nth-last-child(n)
            array('/(\S*):nth-last-child\((\d+)\)/', function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    .'[position()=(last()-('.$matches[2].'-1))]';
            }),
            // :last-child
            array('/(\S*):last-child/', function ($matches) {
                return ($matches[1] ? $matches[1] : '*')
                    .'[last()]';
            }),
            // :first-child
            array('/(\S*):first-child/', function ($matches) {
                return ($matches[1] ? $matches[1]: '*')
                    .'[1]';
            }),
            // Adjacent "sibling" selectors
            array('/\s*\+\s*([^\s]+)/', function ($matches) {
                return '/following-sibling::' . $matches[1] . '[1]';
            }),
            // General "sibling" selectors
            array('/\s*~\s*([^\s]+)/', function ($matches) {
                return '/following-sibling::' . $matches[1];
            }),
            // "child" selectors
            array('/\s*>\s*/', function () {
                return '/';
            }),
            // Remaining Spaces
            array('/\s/', function () {
                return '//';
            }),
            // #id
            array('/([a-z0-9\]]?)#([a-z][-a-z0-9_]+)/i', function ($matches) {
                return $matches[1]
                    .($matches[1] ? '' : '*')
                	.'[@id="' . $matches[2] . '"]';
            }),
            // .className
            // tricky.  without limiting the replacement, the first group will be empty for the 2nd class
            // test case:
            //    foo.classa.classb
            array('/([a-z0-9\]]?)\.(-?[_a-z]+[_a-z0-9-]*)/i', function ($matches) {
                return $matches[1]
                	.($matches[1] ? '' : '*')
                	.'[contains(concat(" ", normalize-space(@class), " "), " ' . $matches[2] . ' ")]';
            }, 1),
            array('/:scope/', function () {
                return '//';
            }),
            // E! : https://www.w3.org/TR/selectors4/
            array('/^.+!.+$/', function ($matches) {
                $subSelectors = explode(',', $matches[0]);
                foreach ($subSelectors as $i => $subSelector) {
                    $parts = explode('!', $subSelector);
                    $subSelector = array_shift($parts);
                    if (preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)) {
                        $results = $matches[0];
                        $results[] = str_repeat('/..', count($results) - 2);
                        $subSelector .= implode('', $results);
                    }
                    $subSelectors[$i] = $subSelector;
                }
                return implode(',', $subSelectors);
            }),
            // Restore strings
            array('/\[\{(\d+)\}\]/', function ($matches) use (&$strings) {
                return $strings[$matches[1]];
            }),
        );
        foreach ($regexs as $regCallback) {
            $limit = isset($regCallback[2])
                ? $regCallback[2]
                : -1;
            if ($limit >= 0) {
                $count = 0;
                do {
                    $xpath = preg_replace_callback($regCallback[0], $regCallback[1], $xpath, $limit, $count);
                } while ($count > 0);
            } else {
                $xpath = preg_replace_callback($regCallback[0], $regCallback[1], $xpath);
            }
        }
        $xpath = preg_match('/^\/\//', $xpath)
        	? $xpath
        	: '//'.$xpath;
        $xpath = preg_replace('#/{4}#', '', $xpath);
        self::$cache[$selector] = $xpath;
        return $xpath;
	}
}
