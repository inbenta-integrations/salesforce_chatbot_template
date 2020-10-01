<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Facade\I;

interface Router
{
    /**
     * @param string $methods
     * @param string $pattern
     * @param string | callable $callable
     * @return mixed
     */
    public function match($methods, $pattern, $callable);

    /**
     * @param null | callable $callback
     * @return mixed
     */
    public function run($callback = null);
}