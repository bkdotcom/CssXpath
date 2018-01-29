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

use bdk\CssXpath\CssXpath;

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

    protected $DOMXpath;

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
            return call_user_func_array(array($this, 'selectNonStatic'), $arguments);
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
            return call_user_func_array(array('self', 'selectStatic'), $arguments);
        }
    }

    /**
     * Select elements from $html using css $selector.
     *
     * @param string|DOMDocument $html      HTML string or DOMDocument
     * @param string             $selector  CSS selector
     * @param boolean            $asDomList (false)
     *
     * @return mixed
     */
    protected static function selectStatic($html, $selector, $asDomList = false)
    {
        $DOMXpath = self::getDomXpath($html);
        $xpath = CssXpath::cssToXpath($selector);
        $elements = $DOMXpath->evaluate($xpath);
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
     * When $asArray is true:
     * matching elements will be return as an associative array containing
     *      name : element name
     *      attributes : attributes array
     *      innerHTML : innner HTML
     *
     * Otherwise regular DOMElement's will be returned.
     *
     * @param string  $selector  css selector
     * @param boolean $asDomList (false)
     *
     * @return mixed
     */
    protected function selectNonStatic($selector, $asDomList = false)
    {
        $DOMXpath = $this->DOMXpath;
        $xpath = CssXpath::cssToXpath($selector);
        $elements = $DOMXpath->evaluate($xpath);
        if (!$elements) {
            return array();
        }
        return $asDomList
            ? $elements
            : self::elementsToArray($elements);
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
        $this->DOMXpath = $this->getDomXpath($html);
    }

    /**
     * Convert DOMNodeList to an array.
     *
     * @param \DOMNodeList $elements elements
     *
     * @return array
     */
    protected static function elementsToArray(\DOMNodeList $elements)
    {
        $array = array();
        for ($i = 0, $length = $elements->length; $i < $length; ++$i) {
            if ($elements->item($i)->nodeType == XML_ELEMENT_NODE) {
                $array[] = self::elementToArray($elements->item($i));
            }
        }
        return $array;
    }

    /**
     * Convert DOMElement to an array.
     *
     * @param \DOMElement $element element
     *
     * @return array
     */
    protected static function elementToArray(\DOMElement $element)
    {
        $array = array(
            'name' => $element->nodeName,
            'attributes' => array(),
            'innerHTML' => self::domInnerHtml($element),
        );
        foreach ($element->attributes as $key => $attr) {
            $array['attributes'][$key] = $attr->value;
        }
        return $array;
    }

    /**
     * Build inner html for given DOMElement
     *
     * @param \DOMElement $element dom element
     *
     * @return string html
     */
    protected static function domInnerHtml(\DOMElement $element)
    {
        $innerHTML = '';
        foreach ($element->childNodes as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }
        $innerHTML = preg_replace('/{amp}([0-9a-z]+);/i', '&\1;', $innerHTML);
        // $innerHTML = str_replace("\xc2\xa0", ' ', $innerHTML);  // &nbsp; && &#160; get converted to UTF-8 \xc2\xa0
        /*
            saveHTML doesn't close "void" tags  :(
        */
        $voidTags = array('area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr');
        $regEx = '#<('.implode('|', $voidTags).')(\b[^>]*)>#';
        $innerHTML = preg_replace($regEx, '<\\1\\2 />', $innerHTML);
        return trim($innerHTML);
    }

    /**
     * Return \DOMXpath object
     *
     * @param string|\DOMDocument $html HTML string or \DOMDocument object
     *
     * @return DOUMXpath
     */
    protected static function getDomXpath($html)
    {
        if ($html instanceof \DOMDocument) {
            $DOMXpath = new \DOMXpath($html);
        } else {
            libxml_use_internal_errors(true);
            if (empty($html)) {
                $html = '<!-- empty document -->';
            }
            $dom = new \DOMDocument();
            /*
                PHP bug: entities get converted
            */
            $html = preg_replace('/&([0-9a-z]+);/i', '{amp}\1;', $html);
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html); // seriously?
            foreach ($dom->childNodes as $node) {
                if ($node->nodeType == XML_PI_NODE) {
                    $dom->removeChild($node); // remove xml encoding tag
                    break;
                }
            }
            $dom->encoding = 'UTF-8';
            $DOMXpath = new \DOMXpath($dom);
        }
        return $DOMXpath;
    }
}
