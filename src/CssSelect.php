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

use bdk\CssXpath\CssXpath;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXpath;

/**
 * CSS selector class
 *
 * Originally based on https://github.com/tj/php-selector
 *
 * Use as an instance
 *    $cssSelect = new CssSelect($html)
 *    $nodes = $cssSelect->select('a.nifty');
 *
 * Use statically
 *    $nodes = CssSelect::select($html, 'a.nifty');
 *
 * @method select(string $selector, bool $asDomList)
 * @method static select($html, string $selector, bool $asDomList)
 */
class CssSelect
{
    protected $domXpath;

    /**
     * Constructor
     *
     * @param string|DOMDocument $html HTML string or DOMDocument object
     */
    public function __construct($html = '')
    {
        $this->setHtml($html);
    }

    /**
     * Magic method
     *
     * Used to access select() non-statically
     *
     * @param string $name      method name
     * @param array  $arguments method arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($name === 'select') {
            return \call_user_func_array(array($this, 'selectNonStatic'), $arguments);
        }
    }

    /**
     * Magic method
     *
     * Used to access select() statically
     *
     * @param string $name      method name
     * @param array  $arguments method arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name === 'select') {
            return \call_user_func_array(array(__CLASS__, 'selectStatic'), $arguments);
        }
    }

    /**
     * Set HTML
     *
     * @param string $html HTML
     *
     * @return void
     */
    public function setHtml($html = '')
    {
        $this->domXpath = $this->getDomXpath($html);
    }

    /**
     * Select elements from $html using css $selector.
     *
     * @param string|DOMDocument $html      HTML string or DOMDocument
     * @param string             $selector  CSS selector
     * @param bool               $asDomList (false)
     *
     * @return mixed
     */
    protected static function selectStatic($html, $selector, $asDomList = false)
    {
        $domXpath = self::getDomXpath($html);
        $xpath = CssXpath::cssToXpath($selector);
        $elements = $domXpath->evaluate($xpath);
        if (!$elements) {
            return array();
        }
        return $asDomList
            ? $elements
            : self::elementsToArray($elements);
    }

    /**
     * Select elements using css $selector.
     *
     * When $asDomList is false (default):
     * matching elements will be return as an associative array containing
     *      name : element name
     *      attributes : attributes array
     *      innerHTML : innner HTML
     *
     * Otherwise regular DOMElement's will be returned.
     *
     * @param string $selector  css selector
     * @param bool   $asDomList (false)
     *
     * @return mixed
     */
    protected function selectNonStatic($selector, $asDomList = false)
    {
        $domXpath = $this->domXpath;
        $xpath = CssXpath::cssToXpath($selector);
        $elements = $domXpath->evaluate($xpath);
        if (!$elements) {
            return array();
        }
        return $asDomList
            ? $elements
            : self::elementsToArray($elements);
    }

    /**
     * Convert DOMNodeList to an array.
     *
     * @param DOMNodeList $elements elements
     *
     * @return array
     */
    protected static function elementsToArray(DOMNodeList $elements)
    {
        $array = array();
        for ($i = 0, $length = $elements->length; $i < $length; ++$i) {
            if ($elements->item($i)->nodeType === XML_ELEMENT_NODE) {
                $array[] = self::elementToArray($elements->item($i));
            }
        }
        return $array;
    }

    /**
     * Convert DOMElement to an array.
     *
     * @param DOMElement $element element
     *
     * @return array
     */
    protected static function elementToArray(DOMElement $element)
    {
        $array = array(
            'attributes' => array(),
            'innerHTML' => self::domInnerHtml($element),
            'name' => $element->nodeName,
        );
        foreach ($element->attributes as $key => $attr) {
            $array['attributes'][$key] = $attr->value;
        }
        return $array;
    }

    /**
     * Build inner html for given DOMElement
     *
     * @param DOMElement $element dom element
     *
     * @return string html
     */
    protected static function domInnerHtml(DOMElement $element)
    {
        $innerHTML = '';
        foreach ($element->childNodes as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }
        /*
            saveHTML doesn't close "void" tags  :(
        */
        $voidTags = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');
        $regEx = '#<(' . \implode('|', $voidTags) . ')(\b[^>]*)>#';
        $innerHTML = \preg_replace($regEx, '<\\1\\2 />', $innerHTML);
        return \trim($innerHTML);
    }

    /**
     * Return DOMXpath object
     *
     * @param string|DOMDocument $html HTML string or DOMDocument object
     *
     * @return DOUMXpath
     */
    protected static function getDomXpath($html)
    {
        if ($html instanceof DOMDocument) {
            return new DOMXpath($html);
        }
        \libxml_use_internal_errors(true);
        if (empty($html)) {
            $html = '<!-- empty document -->';
        }
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html); // seriously?
        foreach ($dom->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $dom->removeChild($node); // remove xml encoding tag
                break;
            }
        }
        $dom->encoding = 'UTF-8';
        return new DOMXpath($dom);
    }
}
