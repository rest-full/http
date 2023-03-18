<?php

namespace Restfull\Http\Middleware;

use Restfull\Container\Instances;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Http\Runner;

/**
 *
 */
class Middleware
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var InstanceClass
     */
    protected $instance;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        if (!($this->instance instanceof Instances)) {
            $this->instance = new Instances();
        }
        return $this;
    }

    /**
     * @param array $middlewares
     *
     * @return $this
     */
    public function queue(array $middlewares): Middleware
    {
        if (count($middlewares) > 0) {
            $this->middlewares = $middlewares;
        }
        return $this;
    }

    /**
     * @param Runner $run
     *
     * @return Runner
     */
    public function run(Runner $run): Runner
    {
        $class = [
            "%s" . DS_REVERSE . 'Error' . DS_REVERSE . 'Middleware' . DS_REVERSE
            . 'ErrorMiddleware',
            "%s" . DS_REVERSE . 'Security' . DS_REVERSE . 'Middleware'
            . DS_REVERSE . 'SecurityMiddleware',
            "%s" . DS_REVERSE . 'Route' . DS_REVERSE . 'Middleware' . DS_REVERSE
            . 'RouteMiddleware'
        ];
        $class = array_merge($class, $this->middlewares);
        $class = array_merge(
            $class, [
                "%s" . DS_REVERSE . 'Executing' . DS_REVERSE . 'Middleware'
                . DS_REVERSE . 'WebServiceMiddleware',
                "%s" . DS_REVERSE . 'Core' . DS_REVERSE . 'Middleware'
                . DS_REVERSE
                . 'AplicationMiddleware'
            ]
        );
        $count = count($class);
        for ($a = 0; $a < $count; $a++) {
            if ($this->instance->resolveClass(
                $this->instance->assemblyClassOrPath(
                    '%s' . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
                    [ROOT_NAMESPACE]
                ), [
                    'file' => $this->instance->assemblyClassOrPath(
                        $class[$a] . ".php", [PATH_NAMESPACE]
                    )
                ]
            )->exists()
            ) {
                $run->add(
                    $this->instance->assemblyClassOrPath(
                        $class[$a], [ROOT_NAMESPACE]
                    )
                );
            }
        }
        return $run;
    }

    /**
     * @return array
     */
    public function http(): array
    {
        return [
            'request' => $this->request,
            'response' => $this->response,
            'instance' => $this->instance
        ];
    }

}
