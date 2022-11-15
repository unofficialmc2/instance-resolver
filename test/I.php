<?php
declare(strict_types=1);

namespace Test;

/**
 * Class pour tester les parametre de constructeur de type scalaire
 */
class I
{
    /**
     * @param F|C $multi
     */
    public function __construct(F|C $multi)
    {
    }
}
