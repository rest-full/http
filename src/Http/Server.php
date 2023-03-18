<?php

namespace Restfull\Http;

use Restfull\Error\Exceptions;

/**
 *
 */
class Server
{

    /**
     * @var Runner
     */
    private $runner;

    /**
     * @var Aplication
     */
    private $app;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     *
     */
    public function __construct()
    {
        if (!defined('RESTFULL')) {
            require_once __DIR__ . '/../../config/pathServer.php';
        }
        $this->request = new Request($_SERVER);
        $this->response = new Response($this->request->server);
        $this->runner = new Runner($this->request, $this->response);
        return $this;
    }

    /**
     * @return $this
     * @throws Exceptions
     */
    public function execute()
    {
        $this->runner->run($this->request, $this->response);
        return $this;
    }

    /**
     * @return string
     */
    public function send()
    {
        return $this->response->send();
    }

}
