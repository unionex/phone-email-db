<?php
namespace App\Responses;

use Phalcon\Di;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;

abstract class Response implements InjectionAwareInterface
{
    public function __construct()
    {
        $this->setDI(Di::getDefault());
    }

    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * Sets the dependency injector
     *
     * @param DiInterface $dependencyInjector
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }
}