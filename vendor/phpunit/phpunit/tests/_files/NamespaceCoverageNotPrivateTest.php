<?php

namespace MolliePrefix;

class NamespaceCoverageNotPrivateTest extends \MolliePrefix\PHPUnit_Framework_TestCase
{
    /**
     * @covers Foo\CoveredClass::<!private>
     */
    public function testSomething()
    {
        $o = new \MolliePrefix\Foo\CoveredClass();
        $o->publicMethod();
    }
}
\class_alias('MolliePrefix\\NamespaceCoverageNotPrivateTest', 'MolliePrefix\\NamespaceCoverageNotPrivateTest', \false);
