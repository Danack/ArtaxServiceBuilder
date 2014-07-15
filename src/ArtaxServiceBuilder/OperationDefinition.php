<?php


namespace ArtaxServiceBuilder;


class OperationDefinition {

    private $name = null;
    private $httpMethod = null;
    private $needsSigning = null;
    private $responseClass = null;
    private $responseFactory = null;
    private $responseCallable;
    private $permissions = [];
    private $scopes = [];
    
    /**
     * @var \ArtaxServiceBuilder\Parameter[]
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
     * @return array
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * @return array
     */
    public function getScopes() {
        return $this->scopes;
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
        return $this->responseFactory;
    }

    /**
     * 
     */
    public function getResponseCallable() {
        return $this->responseCallable;
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
     * @return \ArtaxServiceBuilder\Parameter[]
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
     * @return \ArtaxServiceBuilder\Parameter[]
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
    function setFromServiceDescription($description, $baseURL, APIGenerator $api) {

        $operationParams = [
            "extends", 
            "httpMethod",
            "needsSigning",
            'permissions',
            "responseClass",
            "responseFactory",
            'responseCallable',
            'scopes',
            "summary"
        ];

        foreach ($operationParams as $simpleParam) {
            if (isset($description[$simpleParam])) {
                $this->{$simpleParam} = $description[$simpleParam];
            }
        }

        //Yep, guzzle switches between baseURL and URI
        //@TODO - allow URL or URI in both.
        if (isset($description['uri'])) {
            
            if (stripos($description['uri'], 'http') === 0 || 
                (isset($description['uriIsAbsolute']) && $description['uriIsAbsolute'])) {
                //It's an absolute URL
                $this->setURL($description['uri']);
            }
            else {
                //TODO - use \Artax\URI for RFC compliant combining.
                $this->setURL($baseURL.$description['uri']);
            }
        }

        if (isset($description["parameters"])) {
            foreach ($description["parameters"] as $paramName => $parameterDescription) {
                $parameter = new Parameter($paramName);
                if (isset($parameterDescription['location'])) {
                    $parameter->setLocation($parameterDescription['location']);
                }

                if (isset($parameterDescription['optional'])) {
                    $parameter->setOptional(true);
                }

                if (isset($parameterDescription['default'])) {
                    $parameter->setDefault($parameterDescription['default']);
                }

                if (isset($parameterDescription['description'])) {
                    $parameter->setDescription($parameterDescription['description']);
                }

                if (isset($parameterDescription['sentAs'])) {
                    $parameter->setSentAs($parameterDescription['sentAs']);
                }

                if (in_array($paramName, $api->getAPIParameters())) {
                    $parameter->setIsAPIParameter(true);
                }

                $this->parameters[] = $parameter;
            }
        }
    }
//    
//    TODO - make the param generation functions be on this class, rather
//    than spread between the api and operation generator.
}

 