<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Facade\I;

/**
 * Interface Response
 * @package App\Facade\I
 */
interface Response
{
    /**
     * @param bool $status
     * @return mixed
     */
    public function setSuccess(bool $status);

    /**
     * @param $data
     * @return mixed
     */
    public function setBody($data);

    /**
     * @return mixed
     */
    public function getResponse();

    /**
     * @return string
     */
    public function getJsonFormatted();
}