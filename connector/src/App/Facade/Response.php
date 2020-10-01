<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Facade;

/**
 * Class Response
 * @package App\Facade
 */
class Response extends \App\Model\Varien\DataObject implements I\Response
{
    /**
     * @param bool $status
     * @return $this|mixed
     */
    public function setSuccess(bool $status)
    {
        $this->setData('success', $status);

        return $this;
    }

    /**
     * @param $data
     * @return $this|mixed
     */
    public function setBody($data)
    {
        $this->setData('data', $data);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        if (!$this->getData() === null)
            $this->setData('success', false);
        try {
            $date = new \DateTime(null, new \DateTimeZone('UTC'));
            $this->setData('datetime_utc', $date->format('Y-m-d H:i:s'));
        } catch (\Exception $ex) {}

        return $this->getData();
    }

    public function getJsonFormatted()
    {
        return json_encode($this->getResponse());
    }
}