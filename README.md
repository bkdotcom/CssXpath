CssXpath
===============

* Convert CSS selector to XPath
* Query HTML string (or \DOMDocument) by CSS selector
* Provide PHPUnit Assertions (once provided by PHPUnit)
  * assertSelectCount($selector, $count, $actual, $message = '')
  * assertSelectRegExp($selector, $pattern, $count, $actual, $message = '')
  * assertSelectEquals($selector, $content, $count, $actual, $message = '')

## Installation

```
composer require bdk/css-xpath
```
## Usage

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

var_dump(\bdk\CssXpath\CssSelect::select($html, 'ul > li:last-child [href]'));
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

 pass a 3rd argument of `true`, to return a `\DOMNodeList` object instead of an array
