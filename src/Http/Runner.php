<?php

namespace Restfull\Http;

use Restfull\Container\Instances;
use Restfull\Core\Middleware\AplicationMiddleware;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;
use Restfull\Http\Middleware\Middleware;

/**
 *
 */
class Runner
{

    /**
     * @var array
     */
    private $queue = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var Middleware
     */
    private $middleware;

    /**
     *
     */
    public function __construct(Request $request, Response $response)
    {
        $this->queue = [];
        $this->response = $response;
        $this->request = $request;
        return $this;
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    public function add(string $class): Runner
    {
        $this->queue[] = $class;
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return object|Response
     * @throws Exceptions
     */
    public function run()
    {
        $this->middleware = new Middleware($this->request, $this->response);
        $this->request->bootstrap = (new AplicationMiddleware(
            $this->request, $this->response
        ))->bootstrap();
        $this->request->bootstrap('hash')->http(
            $this->request,
            $this->response
        );
        $this->request->path_info()->checkExistAPI()->methods();
        if ($this->request->route == '') {
            $route = 'main' . DS . 'index';
            if ($this->request->encryptKeys['general'] == 'verdadeiro') {
                $route = $this->request->bootstrap('hash')->encrypt($route);
                if (!empty($this->request->base)) {
                    if (stripos($route, $this->request->base) === false) {
                        $route = $this->request->base . DS . $route;
                    }
                }
                $this->response->redirect($route);
                return $this->response;
            }
            $this->request->route = $route;
        }
        if ($this->files($this->request->route)) {
            $this->middleware->queue($this->request->bootstrap('middleware'))
                ->run($this);
            $this->index = 0;
            return $this();
        }
        return $this;
    }

    /**
     * @param string $file
     *
     * @return bool
     * @throws Exceptions
     */
    public function files(string $file): bool
    {
        if (empty($file)) {
            $file = $this->request->route;
        }
        $datas = explode(DS, $file);
        if (in_array($datas[0], ['img', 'files', 'js', 'css', 'temp'])
            === false
        ) {
            return true;
        }
        $file = new File($file);
        if ($file->folder()->exists()) {
            $extension = explode(".", $datas[count($datas) - 1]);
            if (in_array($extension[1], ['jpg', 'png'])) {
                if ($file->exists()) {
                    $this->response->file($file->pathinfo())->body('');
                    return false;
                }
                return true;
            }
            if ($file->exists()) {
                $this->response->file($file->pathinfo())->body('');
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * @return object
     * @throws Exceptions
     */
    public function __invoke(): object
    {
        $next = $this->queue[$this->index];
        if (!is_object($this->queue[$this->index])) {
            if ((new File($next . "php"))->exists()) {
                throw new Exceptions("Middleware {$next} was not found.");
            }
            $next = (new Instances)->resolveClass(
                $next,
                $this->middleware->http()
            );
        }
        $this->index++;
        return $next($this);
    }

}
