<?php
declare(strict_types=1);
/**
 * User: Fabien Sanchez
 * Date: 13/12/2018
 * Time: 13:52
 */

namespace InstanceResolver;

use InstanceResolver\Exception\UnresolvedException;

/**
 * Class ResolverParameter
 * @package Resolver
 */
class ResolverParameter
{
    /**
     * Resolver de class
     * @var callable
     */
    private $resolver;

    /**
     * ResolverParameter constructor.
     * @param callable $resolver
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Resolver de parameter
     * @param \ReflectionParameter $parametre
     * @return mixed
     */
    public function __invoke(\ReflectionParameter $parametre)
    {
        $nom = $parametre->getName();
        if (!$parametre->hasType()) {
            throw new Exception\UnresolvedParameter(
                "Le paramètre $nom ne peut pas être déterminé"
            );
        }
        /** @var \ReflectionNamedType $type */
        $type = $parametre->getType();
        $nameType = $type->getName();
        if ($this->isNotObject($nameType)) {
            throw new Exception\UnresolvedParameter(
                "Le paramètre $nom ne peut pas être déterminé"
            );
        }
        $resolverClass = $this->resolver;
        try {
            return $resolverClass($parametre->getClass()->getName());
        } catch (UnresolvedException $ex) {
            $exceptionType = '\\' . \get_class($ex);
            throw new $exceptionType(
                "Dans $nom : " . $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }
    }

    /**
     * Determine si un type n'est pas un objet
     * @param string $type
     * @return boolean
     */
    private function isNotObject(string $type)
    {
        $noObjectType = [
            "boolean",
            "integer",
            "int",
            "double",
            "string",
            "array",
            "resource",
            "resource (closed)",
            "NULL",
            "unknown type",
            "callable"
        ];
        return array_search($type, $noObjectType);
    }
}
