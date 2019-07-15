<?php
declare(strict_types=1);
/**
 * User: Fabien Sanchez
 * Date: 13/12/2018
 * Time: 13:50
 */

namespace Resolver;

use Psr\Container\ContainerInterface;

class ResolverClass
{

    /**
     * Container
     * @param ContainerInterface $container
     */
    private $container;

    /**
     * Undocumented function
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Retourne une instance de la classe demandée
     * @param string $searchClass nom de classe
     * @return mixed instance demandé
     * @throws \ReflectionException
     */
    public function __invoke(string $searchClass)
    {
        if ($this->container->has($searchClass)) {
            return $this->container->get($searchClass);
        }
        if ((class_exists($searchClass) || interface_exists($searchClass))
            && is_a($this->container, $searchClass)) {
            return $this->container;
        }
        if (!class_exists($searchClass) && !interface_exists($searchClass)) {
            throw new Exception\UnresolvedClass(
                "La classe $searchClass n'existe pas!"
            );
        }
        $refectClass = new \ReflectionClass($searchClass);
        if ($refectClass->isInterface()) {
            throw new Exception\UnresolvedClass(
                "$searchClass n'est pas une classe mais une interface!"
            );
        }
        $constructor = $refectClass->getConstructor();
        if (!is_null($constructor)) {
            $paramConstructorList = $constructor->getParameters();
            $paramResolved = $this->resolveParams($paramConstructorList);
            return new $searchClass(...$paramResolved);
        }
        return new $searchClass();
    }

    /**
     * Resolver de list de paramètres
     * @param \ReflectionParameter[] $parametres
     * @return array;
     */
    protected function resolveParams(array $parametres)
    {
        $paramsTrouvable = array_filter(
            $parametres,
            function (\ReflectionParameter $param) {
                return !$param->isOptional();
            }
        );
        $resolver = new ResolverParameter($this);
        return array_map($resolver, $paramsTrouvable);
    }
}
