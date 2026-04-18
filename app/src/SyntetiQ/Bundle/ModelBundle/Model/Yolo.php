<?php

namespace SyntetiQ\Bundle\ModelBundle\Model;

class Yolo
{
    private string $train = '';
    private string $val = '';
    private string $test = '';
    private array $names = [];

    public function getTrain()
    {
        return $this->train;
    }

    /**
     * @return self
     */
    public function setTrain($value)
    {
        $this->train = $value;

        return $this;
    }


    public function getVal()
    {
        return $this->val;
    }

    /**
     * @return self
     */
    public function setVal($value)
    {
        $this->val = $value;

        return $this;
    }

    public function getTest()
    {
        return $this->test;
    }

    /**
     * @return self
     */
    public function setTest($value)
    {
        $this->test = $value;

        return $this;
    }

    public function getNames()
    {
        return $this->names;
    }

    /**
     * @return self
     */
    public function setNames($value)
    {
        $this->names = $value;

        return $this;
    }
}
