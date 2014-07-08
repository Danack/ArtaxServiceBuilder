<?php


namespace ArtaxApiBuilder;


class OperationDefinition {

    private $name = null;
    private $httpMethod = null;
    private $needsSigning = null;
    private $responseClass = null;
    private $responseFactory = null;

    /**
     * @var \ArtaxApiBuilder\Parameter[]
     */
    private $parameters = [];
    private $summary = null;
    private $url = null;
    
    
    function __construct() {
    }
    
    function setName($name) {
        $this->name = $name;
    }

    /**
     * @return null
     */
    public function getHttpMethod() {
        return $this->httpMethod;
    }

    /**
     * @return null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null
     */
    public function getNeedsSigning() {
        return $this->needsSigning;
    }

    /**
     * @return Parameter[]
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @return null
     */
    public function getResponseClass() {
        return $this->responseClass;
    }

    /**
     * @return null
     */
    public function getResponseFactory() {
        return $this->responseClass;
    }
    

    /**
     * @return null
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * @return null
     */
    public function getURL() {
        return $this->url;
    }

    /**
     * Get all the params that are either not optional and don't have a default value 
     * @return \ArtaxAPIBuilder\Parameter[]
     */
    public function getRequiredParams() {
        $requiredParams = [];
        
        foreach ($this->parameters as $parameter) {
            if ($parameter->getIsOptional() || $parameter->hasDefault()) {
                continue;
            }
            $requiredParams[]  = $parameter;
        }

        return $requiredParams;
    }

    /**
     * Get all the params that have a default value
     * @return \ArtaxApiBuilder\Parameter[]
     */
    public function getDefaultParams() {
        $defaultParams = [];
        foreach ($this->parameters as $parameter) {
            if ($parameter->hasDefault()) {
                if ($parameter->getIsAPIParameter() == false) {
                    $defaultParams[] = $parameter;
                }
            }
        }

        return $defaultParams;
    }
    
    
    /**
     * @param $baseURL
     */
    function setURL($baseURL) {
        $this->url = $baseURL;
    }

    /**
     * @param $description
     */
    function setFromServiceDescription($description, APIGenerator $api) {
        if (isset($description["parameters"])) {
            foreach ($description["parameters"] as $paramName => $parameterDescription) {
                $parameter = new Parameter($paramName);
                if (isset($parameterDescription['location'])) {
                    //$parameterDescription['location']
                }

                if (isset($parameterDescription['optional'])) {
                    $parameter->setOptional(true);
                }

                if (isset($parameterDescription['default'])) {
                    $parameter->setDefault($parameterDescription['default']);
                }

                if (in_array($paramName, $api->getAPIParameters())) {
                    $parameter->setIsAPIParameter(true);
                }
                
                
                $this->parameters[] = $parameter;
            }
        }

        $operationParams = ["extends", "httpMethod", "needsSigning", "responseClass", "responseFactory", "summary"];
        foreach ($operationParams as $simpleParam) {
            if (isset($description[$simpleParam])) {
                $this->{$simpleParam} = $description[$simpleParam];
            }
        } 
    }
}

 