<?php

// backward compatibility
$classMap = array(
    'PHPUnit_Framework_Exception' => 'PHPUnit\Framework\Exception',
    'PHPUnit_Framework_TestCase' => 'PHPUnit\Framework\TestCase',
    'PHPUnit_Framework_TestSuite' => 'PHPUnit\Framework\TestSuite',
);
foreach ($classMap as $old => $new) {
    if (!class_exists($new)) {
        class_alias($old, $new);
    }
}

$includes = array(
	__DIR__.'/../src/CssXpath.php',
	__DIR__.'/../src/CssSelect.php',
	__DIR__.'/../src/DOMTestCase.php',
);
foreach ($includes as $file) {
	require $file;
}
