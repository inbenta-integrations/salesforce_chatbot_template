<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Facade\I;

interface Request
{
    /**
     * @return string
     */
    public function getRequestMethod();

    /**
     * @return \App\Facade\Request\Parameters
     */
    public function getEnv();

    /**
     * @param \App\Facade\Request\Parameters $env
     */
    public function setEnv(\App\Facade\Request\Parameters $env);

    /**
     * @return \App\Facade\Request\Parameters
     */
    public function getQuery();

    /**
     * @param \App\Facade\Request\Parameters $query
     */
    public function setQuery(\App\Facade\Request\Parameters $query);

    /**
     * @return \App\Facade\Request\Parameters
     */
    public function getPost();

    /**
     * @param \App\Facade\Request\Parameters $post
     */
    public function setPost(\App\Facade\Request\Parameters $post);

    /**
     * @return \App\Facade\Request\Parameters
     */
    public function getServer();

    /**
     * @param \App\Facade\Request\Parameters $server
     */
    public function setServer(\App\Facade\Request\Parameters $server);

    /**
     * @return \App\Facade\Request\Parameters
     */
    public function getBody();

    /**
     * @param \App\Facade\Request\Parameters $body
     */
    public function setBody(\App\Facade\Request\Parameters $body);

    /**
     * @return \App\Facade\Request\Parameters
     */
    public function getHeaders();

    /**
     * @param \App\Facade\Request\Parameters $headers
     */
    public function setHeaders(\App\Facade\Request\Parameters $headers);
}