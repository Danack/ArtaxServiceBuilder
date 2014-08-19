<?php


namespace ArtaxServiceBuilder;


class Parameter {

    private $hasDefault = false;
    private $defaultValue;
    private $isOptional = false;
    private $name;
    private $isAPIParameter;
    private $description;
    private $location;
    
    private $sentAs;
    private $skipIfNull;
    private $permissions;
    private $scopes;
    
    private $filters = [];
    

    function __construct($name) {
        $this->name = $name;
        $this->sentAs = $name;
        $this->isAPIParameter = false;
    }


    /**
     * @param $paramName
     * @param $parameterDescription
     * @param $isAPIParameter
     * @return Parameter
     */
    public static function createFromDescription(
        $paramName,
        $parameterDescription,
        $isAPIParameter
    ) {
        $parameter = new Parameter($paramName);
        if (isset($parameterDescription['location'])) {
            $parameter->location = $parameterDescription['location'];
        }

        if (isset($parameterDescription['filters'])) {
            $parameter->filters = $parameterDescription['filters'];
        }

        if (isset($parameterDescription['optional'])) {
            if ($parameterDescription['optional']) {
                $parameter->isOptional = true;
            }
        }

        if (isset($parameterDescription['default'])) {
            $parameter->hasDefault = true;
            $parameter->defaultValue = $parameterDescription['default'];
            $parameter->isOptional = true;
        }

        if (isset($parameterDescription['description'])) {
            $parameter->description = $parameterDescription['description'];
        }

        if (isset($parameterDescription['sentAs'])) {
            $parameter->sentAs = $parameterDescription['sentAs'];
        }

        if (isset($parameterDescription['skipIfNull'])) {
            $parameter->skipIfNull = $parameterDescription['skipIfNull'];
        }

        $parameter->isAPIParameter = $isAPIParameter;
        
        return $parameter;
    }
    
    /**
     * @return mixed
     */
    function getDescription() {
        return $this->description;
    }
    
    /**
     * @return string
     */
    public function getLocation() {
        return $this->location;
    }


    public function getFilters() {
        return $this->filters;
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
    
    public function getSentAs() {
        return $this->sentAs;
    }
    
    public function getSkipIfNull() {
        return $this->skipIfNull;
    }


    public function getType() {
        return '';
    }
}

 