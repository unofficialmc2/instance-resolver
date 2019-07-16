<?php

require __DIR__ . '/../vendor/autoload.php';

use InstanceResolver\Exception\UnresolvedException;
use InstanceResolver\ResolverClass;
use Psr\Container\ContainerInterface;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container;

class A
{
    public $prop = 0;
}
class B
{
    public function __construct($x)
    { }
}
class C
{
    public function __construct(A $x)
    { }
}
class D
{
    public function __construct(B $x)
    { }
}
class E
{
    public function __construct($x)
    { }
}
class F
{
    public function __construct(E $x)
    { }
}
class G
{
    public function __construct(F $x, C $y)
    { }
}

function message(string $msg)
{
    echo $msg . PHP_EOL;
}

function titre(string $msg)
{
    $echap = function ($code) {
        return chr(27) . '[' . $code . 'm';
    };
    message($echap('93') . $msg . $echap('0'));
}

titre("Initialisation du container");
$container = new Pimple();
$container[E::class] = function ($c) {
    return new E("x");
};
$container = new Container($container);


titre("doit retrouver une instance de classe connu du container");
$resolver = new ResolverClass($container);
$e = $resolver(E::class);
Assert\boolTest(is_a($e, E::class), "ResolverClass doit fournir un objet d'instance " . E::class);

titre("doit retrouver l'instance container");
$resolver = new ResolverClass($container);
$g = $resolver(Container::class);
Assert\boolTest(is_a($g, Container::class), "ResolverClass doit fournir un objet d'instance " . Container::class);
Assert\boolTest($g === $container, "ResolverClass doit redonner le container");

titre("doit retrouver l'instance container quand on demande la même interface");
$resolver = new ResolverClass($container);
$g = $resolver(ContainerInterface::class);
Assert\boolTest(is_a($g, Container::class), "ResolverClass doit fournir un objet d'instance " . Container::class);
Assert\boolTest($g === $container, "ResolverClass doit redonner le container");

titre("doit instancier une class simple");
$resolver = new ResolverClass($container);
$a = $resolver(A::class);
Assert\boolTest(is_a($a, A::class), "ResolverClass doit fournir un objet d'instance " . A::class);

titre("ne doit pas instancier une class avec un paramètre non résolvable");
Assert\throwTest(function () use ($container) {
    $resolver = new ResolverClass($container);
    $b = $resolver(B::class);
}, new UnresolvedException());

titre("doit instancier une class dépendante d'une classe simple");
$resolver = new ResolverClass($container);
$c = $resolver(C::class);
Assert\boolTest(is_a($c, C::class), "ResolverClass doit fournir un objet d'instance " . C::class);

titre("ne doit pas instancier une class dépendante d'une class avec un paramètre non résolvable");
Assert\throwTest(function () use ($container) {
    $resolver = new ResolverClass($container);
    $d = $resolver(D::class);
}, new UnresolvedException());

titre("doit instancier une class dépendante d'une classe connu du container");
$resolver = new ResolverClass($container);
$f = $resolver(F::class);
Assert\boolTest(is_a($f, F::class), "ResolverClass doit fournir un objet d'instance " . F::class);

titre("doit instancier une class complexe");
$resolver = new ResolverClass($container);
$g = $resolver(G::class);
Assert\boolTest(is_a($g, G::class), "ResolverClass doit fournir un objet d'instance " . G::class);
