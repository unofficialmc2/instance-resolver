<?php
declare(strict_types=1);
/**
 * User: Fabien Sanchez
 * Date: 01/10/2020
 * Time: 09:45
 */

namespace Test;

use InstanceResolver\Exception\UnresolvedException;
use InstanceResolver\Exception\UnresolvedParameter;
use InstanceResolver\ResolverClass;
use PHPUnit\Framework\TestCase;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;

class ResolverClassTest extends TestCase
{
    /**
     * @var ContainerInterface|null
     */
    private $container = null;

    /**
     * doit retrouver une instance de classe connu du container
     */
    public function testRetrouverUneInstanceDeClasseConnuDuContainer(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $e = $resolver(E::class);
        self::assertInstanceOf(E::class, $e, "ResolverClass doit fournir un objet d'instance " . E::class);
    }

    /**
     * Initialisation du container
     * @return \Psr\Container\ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $container = new Pimple();
            $container[E::class] = static function ($c) {
                return new E("x");
            };
            $container[\PHPUnit\TextUI\ResultPrinter::class] = static function () {
                return 1;
            };
            $this->container = new Container($container);
        }
        return $this->container;
    }

    /**
     * doit retrouver l'instance container
     */
    public function testRetrouverInstanceContainer(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $container = $resolver(Container::class);
        self::assertInstanceOf(Container::class, $container, "ResolverClass doit fournir un objet d'instance " . Container::class);
        self::assertEquals($this->getContainer(), $container, "ResolverClass doit redonner le container");
    }

    /**
     * doit retrouver l'instance container quand on demande la même interface
     */
    public function testRetrouverInstanceContainerQuandOnDemandeLaMemeInterface(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $container = $resolver(ContainerInterface::class);
        self::assertInstanceOf(Container::class, $container, "ResolverClass doit fournir un objet d'instance " . Container::class);
        self::assertEquals($this->getContainer(), $container, "ResolverClass doit redonner le container");
    }

    /**
     * doit instancier une class simple
     */
    public function testInstancierClassSimple(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $a = $resolver(A::class);
        self::assertInstanceOf(A::class, $a, "ResolverClass doit fournir un objet d'instance " . A::class);
    }

    /**
     * ne doit pas instancier une class avec un paramètre non résolvable
     */
    public function testPasInstancierClassParametreNonResolvable(): void
    {
        $this->expectException(UnresolvedException::class);
        $resolver = new ResolverClass($this->getContainer());
        $resolver(B::class);
    }

    /**
     * doit instancier une class dépendante d'une classe simple
     */
    public function testInstancierClassDependanteClasseSimple(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $c = $resolver(C::class);
        self::assertInstanceOf(C::class, $c, "ResolverClass doit fournir un objet d'instance " . C::class);
    }

    /**
     * ne doit pas instancier une class dépendante d'une class avec un paramètre non résolvable
     */
    public function testPasInstancierClassDependanteClassParametreNonResolvable(): void
    {
        $this->expectException(UnresolvedException::class);
        $resolver = new ResolverClass($this->getContainer());
        $resolver(D::class);
    }

    /**
     * doit instancier une class dépendante d'une classe connu du container
     */
    public function testInstancierClassDependanteClasseConnuDuContainer(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $c = $resolver(F::class);
        self::assertInstanceOf(F::class, $c, "ResolverClass doit fournir un objet d'instance " . F::class);
    }


    /**
     * doit instancier une class complexe
     */
    public function testInstancierClassComplex(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        $c = $resolver(G::class);
        self::assertInstanceOf(G::class, $c, "ResolverClass doit fournir un objet d'instance " . G::class);
    }

    /**
     * test de possible solution en cas d'erreur
     * @throws \ReflectionException
     * @noinspection PhpUndefinedClassInspection
     */
    public function testPossibleSolutionInError(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        try {
            /** @phpstan-ignore-next-line */
            $resolver(\ZicArchive::class);
        } catch (UnresolvedException $e) {
            self::assertStringContainsString('ZicArchive', $e->getMessage());
            self::assertStringContainsString('ZipArchive', $e->getMessage());
        }
    }


    /**
     * test de possible solution en cas d'erreur
     * @throws \ReflectionException
     */
    public function testPossibleSolutionInInterfaceNotFound(): void
    {
        $resolver = new ResolverClass($this->getContainer());
        try {
            /** @phpstan-ignore-next-line */
            $resolver(\PHPUnit\TextUI\resultPrinter::class);
        } catch (UnresolvedException $e) {
            self::assertStringContainsString('resultPrinter', $e->getMessage());
            self::assertStringContainsString('ResultPrinter', $e->getMessage());
        }
    }

    /**
     * Test les paramètre de type scalaire
     * @throws \ReflectionException
     */
    public function testParamConstruct(): void
    {
        $this->expectException(UnresolvedParameter::class);
        $resolver = new ResolverClass($this->getContainer());
        $resolver(H::class);
    }

    /**
     * Test l'initialisation d'un boolean impossible
     * @return void
     * @throws \ReflectionException
     */
    public function testResolveBoolParameter(): void
    {
        $this->expectException(UnresolvedParameter::class);
        $resolver = new ResolverClass($this->getContainer());
        $resolver(BoolClass::class);
    }
}
