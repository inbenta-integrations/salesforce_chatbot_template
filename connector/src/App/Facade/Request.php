<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Facade;

class Request implements I\Request
{
    /**
     * @var string 'POST'|'GET'|'PUT'|'DELETE'|'PATCH'
     */
    private $requestMethod;

    /**
     * @var Request\Parameters
     */
    private $headers;

    /**
     * @var Request\Parameters
     */
    private $env;

    /**
     * @var Request\Parameters
     */
    private $query;

    /**
     * @var Request\Parameters
     */
    private $post;

    /**
     * @var Request\Parameters
     */
    private $body;

    /**
     * @var Request\Parameters
     */
    private $server;

    public function __construct()
    {
        $this->setHeaders(new Request\Parameters($this->getRequestHeaders()));
        $this->setEnv(new Request\Parameters($_ENV));
        $this->setQuery(new Request\Parameters($_GET));
        $this->setPost(new Request\Parameters($_POST));
        $this->setBody(new Request\Parameters(json_decode(file_get_contents('php://input'), true)));
        $this->setServer(new Request\Parameters($_SERVER));
    }

    /**
     * Get the request method used, taking overrides into account.
     *
     * @return string The Request method to handle
     */
    public function getRequestMethod()
    {
        if ($this->requestMethod) {
            return $this->requestMethod;
        }
        // Take the method as found in $_SERVER
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];

        if ($this->requestMethod == 'POST') {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                $this->requestMethod = $headers['X-HTTP-Method-Override'];
            }
        }

        return $this->requestMethod;
    }

    /**
     * Get all request headers.
     *
     * @return array The request headers
     */
    private function getRequestHeaders()
    {
        $headers = [];
        // If getallheaders() is available, use that
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            // getallheaders() can return false if something went wrong
            if ($headers !== false) {
                return $headers;
            }
        }

        // Method getallheaders() not available or went wrong: manually extract
        foreach ($_SERVER as $name => $value) {
            if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
                $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    /**
     * @return Request\Parameters
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @param Request\Parameters $env
     */
    public function setEnv(Request\Parameters $env): void
    {
        $this->env = $env;
    }

    /**
     * @return Request\Parameters
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Request\Parameters $query
     */
    public function setQuery(Request\Parameters $query): void
    {
        $this->query = $query;
    }

    /**
     * @return Request\Parameters
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param Request\Parameters $post
     */
    public function setPost(Request\Parameters $post): void
    {
        $this->post = $post;
        foreach ($post->getData() as $key => $value) {
            $this->post->setData($key, filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS));
        }
    }

    /**
     * @return Request\Parameters
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param Request\Parameters $server
     */
    public function setServer(Request\Parameters $server): void
    {
        $this->server = $server;
    }

    /**
     * @return Request\Parameters
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param Request\Parameters $body
     */
    public function setBody(Request\Parameters $body): void
    {
        $this->body = $body;
    }

    /**
     * @return Request\Parameters
     */
    public function getHeaders(): Request\Parameters
    {
        return $this->headers;
    }

    /**
     * @param Request\Parameters $headers
     */
    public function setHeaders(Request\Parameters $headers): void
    {
        $this->headers = $headers;
    }

    public function getHeader($key)
    {
        return $this->headers->getData($key);
    }
}