<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Exception\InternalError\UnresolvableParameter as UnresolvableParameterException;
use App\ResolverClass;
use Pimple\Container;

class A
{
    public $prop = 0;
}
class B
{
    public function __construct($x)
    {
    }
}
class C
{
    public function __construct(A $x)
    {
    }
}
class D
{
    public function __construct(B $x)
    {
    }
}
class E
{
    public function __construct($x)
    {
    }
}
class F
{
    public function __construct(E $x)
    {
    }
}
class G
{
    public function __construct(F $x, C $y)
    {
    }
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
$container = new Container();
$container[E::class] = function ($c) {
    return new E("x");
};

titre("doit retrouver une instance de classe connu du container");
$resolver = new ResolverClass($this->container);
$e = $resolver(E::class);
expect($e)->toBeAnInstanceOf(E::class);

titre("doit retrouver l'instance container");
$resolver = new ResolverClass($this->container);
$g = $resolver(Container::class);
expect($g)->toBeAnInstanceOf(Container::class);
expect($g)->toBe($this->container);

titre("doit retrouver l'instance container quand on demande la même interface");
$resolver = new ResolverClass($this->container);
$g = $resolver(\Psr\Container\ContainerInterface::class);
expect($g)->toBeAnInstanceOf(Container::class);
expect($g)->toBe($this->container);

titre("doit instancier une class simple");
$resolver = new ResolverClass($this->container);
$a = $resolver(A::class);
expect($a)->toBeAnInstanceOf(A::class);

titre("ne doit pas instancier une class avec un paramètre non résolvable");
expect(function () {
    $resolver = new ResolverClass($this->container);
    $b = $resolver(B::class);
})->toThrow(new UnresolvableParameterException());

titre("doit instancier une class dépendante d'une classe simple");
$resolver = new ResolverClass($this->container);
$c = $resolver(C::class);
expect($c)->toBeAnInstanceOf(C::class);

titre("ne doit pas instancier une class dépendante d'une class avec un paramètre non résolvable");
expect(function () {
    $resolver = new ResolverClass($this->container);
    $d = $resolver(D::class);
})->toThrow(new UnresolvableParameterException());

titre("doit instancier une class dépendante d'une classe connu du container");
$resolver = new ResolverClass($this->container);
$f = $resolver(F::class);
expect($f)->toBeAnInstanceOf(F::class);

titre("doit instancier une class complexe");
$resolver = new ResolverClass($this->container);
$g = $resolver(G::class);
expect($g)->toBeAnInstanceOf(G::class);
