CssXpath
===============

* Convert CSS selector to XPath
* Query HTML string (or \DOMDocument) by CSS selector
* Provide PHPUnit Assertions (once provided by PHPUnit)

![No Dependencies](https://img.shields.io/badge/dependencies-none-333333.svg)
[![Build Status](https://img.shields.io/travis/bkdotcom/CssXpath.svg)](https://travis-ci.org/bkdotcom/CssXpath)

## Installation

```
composer require bdk/css-xpath
```
## Usage

### CSS to XPath

```PHP
\bdk\CssXpath\CssXpath::cssToXpath('ul > li:first-child');	// returns '//ul/li[1]'
```
### Query DOM/HTML

Example:

```PHP
$html = <<<HTML
<div id="article" class="block large">
  <h2>Article Name</h2>
  <p>Contents of article</p>
  <ul>
    <li>One</li>
    <li>Two</li>
    <li>Three</li>
    <li>Four</li>
    <li><a href="#">Five</a></li>
  </ul>
</div>
HTML;

// use static method
var_dump(\bdk\CssXpath\CssSelect::select($html, 'ul > li:last-child [href]'));

// or create and use an instance
$cssSelect = new \bdk\CssXpath\CssSelect($html);
$found = $cssSelect->select('ul > li:last-child [href]');
```

Output:
```text
array (size=1)
  0 =>
    array (size=3)
      'name' => string 'a' (length=1)
      'attributes' =>
        array (size=1)
          'href' => string '#' (length=1)
      'innerHTML' => string 'Five' (length=4)
```

Pass a last argument of `true`, to return a `\DOMNodeList` object instead of an array

### PHPUnit

`bdk\CssXpath\DOMTestCase` extends `\PHPUnit\Framework\TestCase` and provids 3 assertions:

  * `assertSelectCount($selector, $count, $actual, $message = '')`
  * `assertSelectRegExp($selector, $pattern, $count, $actual, $message = '')`
  * `assertSelectEquals($selector, $content, $count, $actual, $message = '')`

[These assertions](https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.assertions.assertSelectCount) were originally provided with PHPUnit 3.3, but removed with PHPUnit 5.0
