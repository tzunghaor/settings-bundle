<?php

namespace TestApp\UnitSettings;

/**
 * class to test ObjectHydrator
 */
class HydratorSetting
{
    private $privateA;

    private $privateB;

    private $privateC;

    protected $protectedA;

    public $publicA;

    public function __construct($privateA, $privateB)
    {
        $this->privateA = $privateA;
        $this->privateB = $privateB;
    }

    /**
     * @param mixed $privateC
     */
    public function setPrivateC($privateC): void
    {
        $this->privateC = $privateC;
    }

    /**
     * @param mixed $protectedA
     */
    public function setProtectedA($protectedA): void
    {
        $this->protectedA = $protectedA;
    }

    /**
     * @return mixed
     */
    public function getPrivateA()
    {
        return $this->privateA;
    }

    /**
     * @return mixed
     */
    public function getPrivateB()
    {
        return $this->privateB;
    }

    /**
     * @return mixed
     */
    public function getPrivateC()
    {
        return $this->privateC;
    }

    /**
     * @return mixed
     */
    public function getProtectedA()
    {
        return $this->protectedA;
    }
}