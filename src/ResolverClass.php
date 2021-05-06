<?php
declare(strict_types=1);
/**
 * User: Fabien Sanchez
 * Date: 13/12/2018
 * Time: 13:50
 */

namespace InstanceResolver;

use Psr\Container\ContainerInterface;

class ResolverClass
{
    /** @var \Psr\Container\ContainerInterface ContainerInterface */
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
        if (interface_exists($searchClass)) {
            $solutions = $this->listPossibleInterface($searchClass);
            $strSolution = '';
            if (!empty($solutions)) {
                $strSolution = " Autre solutions possible : " . implode(', ', $solutions) . '.';
            }
            throw new Exception\UnresolvedClass(
                "$searchClass n'est pas une classe mais une interface!." . $strSolution
            );
        }
        if (!class_exists($searchClass)) {
            $solutions = $this->listPossibleClass($searchClass);
            $strSolution = '';
            if (!empty($solutions)) {
                $strSolution = " Autre solutions possible : " . implode(', ', $solutions) . '.';
            }
            throw new Exception\UnresolvedClass(
                "La classe $searchClass n'existe pas!" . $strSolution
            );
        }
        $refectClass = new \ReflectionClass($searchClass);
        $constructor = $refectClass->getConstructor();
        if (!is_null($constructor)) {
            $paramConstructorList = $constructor->getParameters();
            $paramResolved = $this->resolveParams($paramConstructorList);
            return new $searchClass(...$paramResolved);
        }
        return new $searchClass();
    }

    /**
     * recherche des items similaire dans une liste
     * @param string $needle
     * @param string[] $haystack
     * @return string[]
     */
    private static function similarItems(string $needle, array $haystack): array
    {
        return array_filter($haystack, static function ($item) use ($needle) {
            $itemSmallName = explode('\\', $item);
            $itemSmallName = array_pop($itemSmallName);
            $itemSmallName = is_string($itemSmallName) ? $itemSmallName : ''; // @phpstan-ignore-line
            $needleSmallName = explode('\\', $needle);
            $needleSmallName = array_pop($needleSmallName);
            $needleSmallName = is_string($needleSmallName) ? $needleSmallName : ''; // @phpstan-ignore-line
            $needleCute = substr($needle, -strlen($item));
            $needleCute = is_string($needleCute) ? $needleCute : ''; // @phpstan-ignore-line
            return levenshtein($item, $needle) <= 3
                || levenshtein($item, $needleCute) <= 2
                || levenshtein($itemSmallName, $needleSmallName) <= 1;
        });
    }

    /**
     * recherche un nom parmi les classes déjà déclarées
     * @param string $searchClass
     * @return string[]
     */
    private function listPossibleClass(string $searchClass): array
    {
        return self::similarItems($searchClass, get_declared_classes());
    }

    /**
     * recherche un nom parmi les interface déclarées dans le container
     * @param string $searchInterface
     * @return string[]
     */
    private function listPossibleInterface(string $searchInterface): array
    {
        $similar = self::similarItems($searchInterface, get_declared_interfaces());
        return array_filter($similar, function ($interface) {
            return $this->container->has($interface);
        });
    }

    /**
     * Resolver de list de paramètres
     * @param \ReflectionParameter[] $parametres
     * @return mixed[];
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
