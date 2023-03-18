<?php

namespace Restfull\Http;

use Restfull\Authentication\Auth;
use Restfull\Error\Exceptions;

/**
 *
 */
class Request
{

    /**
     * @var string
     */
    public $route = '';

    /**
     * @var string
     */
    public $base = '';

    /**
     * @var string
     */
    public $controller = '';

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var string
     */
    public $action = '';

    /**
     * @var bool
     */
    public $ajax = false;

    /**
     * @var array
     */
    public $server = [];

    /**
     * @var bool
     */
    public $erroExision = false;

    /**
     * @var array
     */
    public $bootstrap = [];

    /**
     * @var Auth
     */
    public $auth;

    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @var bool
     */
    public $renderCsrf = false;

    /**
     * @var array
     */
    public $encryptKeys = [];

    /**
     * @var string
     */
    public $userAgent;

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var bool
     */
    public $blockedRoute = false;

    /**
     * @var bool
     */
    private $api = false;

    /**
     * @var array
     */
    private $home = ['htdocs', 'public_html', 'www'];

    /**
     * @var array
     */
    private $get;

    /**
     * @var array
     */
    private $post;

    /**
     * @var array
     */
    private $put;

    /**
     * @var array
     */
    private $patch;

    /**
     * @var array
     */
    private $delete;

    /**
     * @var array
     */
    private $files;

    /**
     * @var mixed
     */
    private $attachments;

    /**
     * @param array $server
     */
    public function __construct(array $server)
    {
        $this->server = $server;
        if ($this->server['HTTP_HOST'] == 'localhost') {
            $this->server['REMOTE_ADDR'] = '187.85.61.4';
            $this->erroExision = true;
        }
        $this->userAgent();
        return $this;
    }

    /**
     * @return $this
     */
    public function userAgent(): Request
    {
        $this->useAgent = stripos($this->server['HTTP_USER_AGENT'], 'Mobile')
        !== false ? 'mobile' : 'desktop';
        return $this;
    }

    /**
     * @return $this
     */
    public function path_info(): Request
    {
        $this->base();
        $uri = $this->server['REQUEST_URI'];
        if (stripos($uri, '?') === false) {
            $this->url = parse_url(urldecode($uri), PHP_URL_PATH);
        } else {
            $this->url = parse_url(
                urldecode(substr($uri, 0, stripos($uri, '?'))),
                PHP_URL_PATH
            );
        }
        if (isset($this->base)) {
            $this->url = substr($this->url, strlen($this->base));
        }
        $this->ajax = isset($this->server['HTTP_X_REQUESTED_WITH']) ? true
            : false;
        $hash = $this->bootstrap['hash'];
        $url = stripos(substr($this->url, 1), DS) === false ? substr($this->url, 1) : $this->url;
        if ($hash->valideDecrypt($url)) {
            $url = $hash->decrypt(substr($url, stripos($url, DS)));
        }
        if (strlen($url) > 1) {
            $url = explode(DS, $url);
            if (count($url) > 3) {
                if (in_array($this->base, $url)) {
                    unset($url[array_search($this->base, $url)]);
                }
            }
            if ($url[0] == '') {
                unset($url[0]);
            }
            $url = implode(DS, array_values($url));
        }
        $this->route = $url;
        return $this;
    }

    /**
     * @return $this
     */
    public function base(): Request
    {
        $project = explode(DS, substr(ROOT, 0, -1));
        $count = count($this->home);
        for ($a = 0; $a < $count; $a++) {
            if (in_array($this->home[$a], $project) !== false) {
                $numberHomeArray = array_search($this->home[$a], $project);
                break;
            }
        }
        $this->base = (count($project) - 1) == $numberHomeArray ? ''
            : DS . $project[$numberHomeArray + 1];
        unset($project);
        return $this;
    }

    /**
     * @return $this
     */
    public function checkExistAPI(): Request
    {
        $url = explode(DS, $this->route);
        if (in_array($this->base, $url)) {
            unset($url[array_search($this->base, $url)]);
        }
        foreach ($url as $route) {
            $routes[] = $route;
        }
        if (in_array("api", $routes)) {
            $this->api = true;
            unset($routes[array_search("api", $routes)]);
            $this->route = implode(DS . $routes);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function methods(): Request
    {
        $this->bootstrap['security']->superGlobal();
        $this->get = $_GET ?? null;
        $this->post = $_POST ?? null;
        $this->put = $_PUT ?? null;
        $this->patch = $_PATCH ?? null;
        $this->delete = $_DELETE ?? null;
        if (isset($_FILES['attachments'])) {
            $this->attachments = $_FILES['attachments'];
            unset($_FILES['attachments']);
        }
        $this->files = $_FILES ?? null;
        $this->auth = $this->bootstrap['logged'];
        unset($this->bootstrap['logged']);
        return $this;
    }

    /**
     * @return $this
     */
    public function ativation(): Request
    {
        $this->ativations['paginator'] = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function bolleanApi(): bool
    {
        return $this->api;
    }

    /**
     * @param array $keys
     *
     * @return $this
     * @throws Exceptions
     */
    public function urlParamsDecrypt(array $keys = []): Request
    {
        if (count($this->params) > 0) {
            $keys = count($keys) > 0 ? array_merge($keys, ['page', 'id'])
                : ['page', 'id'];
            foreach ($this->params as $key => $param) {
                if (in_array($key, $keys) !== false) {
                    if ($this->bootstrap['security']->validBase64($param)) {
                        throw new Exceptions(
                            'Parameter passed is not properly configured.', 404
                        );
                    }
                    $this->params[$key] = base64_decode($param);
                }
            }
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function bootstrap(string $name)
    {
        return $this->bootstrap[$name];
    }

    /**
     * @return string
     */
    public function csrfPost(): string
    {
        if (isset($this->post['csrf'])) {
            $csrf = $this->post['csrf'];
            unset($this->post['csrf']);
            return $csrf;
        }
        return '';
    }

    /**
     * @param string|null $modo
     *
     * @return array|mixed
     */
    public function data(string $modo = null)
    {
        if (isset($modo)) {
            if ($modo == 'method') {
                return $this->server['REQUEST_METHOD'];
            } else {
                return $this->{$modo};
            }
        }
        return $this->post;
    }

    /**
     * @return $this
     */
    public function requestMethodGet(): Request
    {
        $this->renderCsrf = true;
        $this->server['REQUEST_METHOD'] = 'GET';
        return $this;
    }

    /**
     * @return $this
     */
    public function requestMethod(): Request
    {
        if (isset($this->post['_METHOD'])) {
            $method = $this->post['_METHOD'];
            unset($this->post['_METHOD']);
            if (is_array($method)) {
                $newMethod = $method[0];
                $method = '';
                $method = $newMethod;
                unset($mewMethod);
            }
            $this->server['REQUEST_METHOD'] = strtoupper(
                $method
            );
        }
        return $this;
    }

}
