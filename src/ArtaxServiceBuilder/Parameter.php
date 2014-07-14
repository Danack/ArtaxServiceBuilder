<?php


namespace ArtaxApiBuilder;


class Parameter {

    private $hasDefault;
    private $defaultValue;
    private $isOptional;
    private $name;
    private $isAPIParameter;
    private $description;
    private $location;
    
    private $permissions;
    private $scopes;
    
    function __construct($name) {
        $this->name = $name;
        $this->isAPIParameter = false;
    }

    
    function getDescription() {
        return $this->description;
    }

    function setDescription($description) {
        $this->description = $description;
    }
    
    function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    function setScopes($scopes) {
        $this->scopes = $scopes;
    }
    

    /**
     * @return string
     */
    public function getLocation() {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location) {
        $this->location = $location;
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

 