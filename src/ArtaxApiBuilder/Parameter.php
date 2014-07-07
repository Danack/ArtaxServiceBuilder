<?php


namespace ArtaxApiBuilder;


class Parameter {

    private $hasDefault;
    private $defaultValue;
    private $isOptional;
    private $name;
    private $isAPIParameter;
    
    function __construct($name) {
        $this->name = $name;
        $this->isAPIParameter = false;
    }

    /**
     * @param $isAPIParameter
     */
    function setIsAPIParameter($isAPIParameter) {
        $this->isAPIParameter = $isAPIParameter;
    }

    function getIsAPIParameter() {
        return $this->isAPIParameter;
    }
    
    /**
     * @return mixed
     */
    public function hasDefault() {
        return $this->hasDefault;
    }

    public function getDefault() {
        return $this->defaultValue;
    }
    
    /**
     * @return mixed
     */
    public function getIsOptional() {
        return $this->isOptional;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param $defaultValue
     */
    public function setDefault($defaultValue) {
        $this->defaultValue = $defaultValue;
        $this->hasDefault = true;
    }

    /**
     * @param $isOptional
     */
    public function setOptional($isOptional) {
        $this->isOptional = $isOptional;
    }
}

 