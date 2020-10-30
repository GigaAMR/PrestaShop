<?php

namespace MolliePrefix;

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class PHPUnit_Framework_Constraint_SameSize extends \MolliePrefix\PHPUnit_Framework_Constraint_Count
{
    /**
     * @var int
     */
    protected $expectedCount;
    /**
     * @param int $expected
     */
    public function __construct($expected)
    {
        parent::__construct($this->getCountOf($expected));
    }
}
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
\class_alias('MolliePrefix\\PHPUnit_Framework_Constraint_SameSize', 'MolliePrefix\\PHPUnit_Framework_Constraint_SameSize', \false);
