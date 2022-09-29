<?php
declare(strict_types=1);

/**
 * User: Fabien Sanchez
 * Date: 01/10/2020
 * Time: 09:58
 */

namespace Test;

/**
 * Class Bool
 */
class BoolClass
{
    /**
     * @var bool pour illustration
     * @phpstan-ignore-next-line : propriété écrite, mais jamais lue.
     */
    private bool $test;

    public function __construct(bool $test)
    {
        $this->test = $test;
    }
}
