<?php


namespace ArtaxApiBuilder;


use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\DocBlockGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\DocBlock\Tag\GenericTag; 
use Danack\Code\Generator\PropertyGenerator;


class OperationGenerator {

    private $className;
    /**
     * @var OperationDefinition
     */
    private $operationDefinition;
    private $outputPath;
    private $namespace;
    private $apiClassname;
    
    /**
     * @var \Danack\Code\Generator\ClassGenerator
     */
    private $classGenerator;

    /**
     * @var APIGenerator
     */
    private $apiGenerator;

    /**
     * @param $namespace
     * @param $className
     * @param OperationDefinition $operation
     * @param $outputPath
     * @param APIGenerator $api
     */
    function __construct(
        $namespace,
        $className,
        OperationDefinition $operation,
        $outputPath,
        \ArtaxApiBuilder\APIGenerator $api
    ) {
        $this->namespace = $namespace;
        $this->className = $className;
        $this->operationDefinition = $operation;
        $this->outputPath = $outputPath;
        $this->classGenerator = new ClassGenerator();
        $this->apiGenerator = $api;
    }


    /**
     * @param $apiFQCN
     */
    function setAPIClassname($apiFQCN) {
        $this->apiClassname = $apiFQCN;
    }
    
    /**
     * 
     */
    function addProperties() {
        $requiredProperties = [
            'api' => '\\'.$this->apiClassname,
            'parameters' => 'array'
        ];

        //TODO - deal with clashes between this and bits of the actual api
        foreach ($requiredProperties as $propertyName => $typehint) {
            $propertyGenerator = new PropertyGenerator($propertyName, null);
            $docBlock = new DocBlockGenerator('@var $api '.$typehint);
            $propertyGenerator->setDocBlock($docBlock);
            $this->classGenerator->addPropertyFromGenerator($propertyGenerator);
        }
    }

    /**
     * 
     */
    function addSetAPIMethod() {
        $methodGenerator = new MethodGenerator('setAPI');
        $methodGenerator->setBody('$this->api = $api;');
        $parameterGenerator = new ParameterGenerator('api', $this->apiClassname);
        $methodGenerator->setParameter($parameterGenerator);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }

    /**
     * 
     */
    function addSetParameterMethod() {
        $methodGenerator = new MethodGenerator('setParams');
        $parameterGenerator = new ParameterGenerator('params', 'array');
        $methodGenerator->setParameter($parameterGenerator);
        $body = '';
        foreach($this->operationDefinition->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $translatedParam = $this->apiGenerator->translateParameter($paramName);
            $setString = <<< END
if (array_key_exists('%s', \$params)) {
     \$this->parameters['%s'] = \$params['%s'];
}
END;
            $body .= sprintf($setString, $translatedParam, $paramName, $translatedParam);
            $body .= PHP_EOL;
        }

        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     * Adds a method to allow checking of the scope requirement for an operation.
     */
    function addCheckScopeMethod() {

        $scopes = $this->operationDefinition->getScopes();
        if (count($scopes) == 0) {
            //TODO - should the method be added anyway? For now, no.
            return;
        }

        $methodGenerator = new MethodGenerator('checkScopeRequirement');
        $parameterGenerator = new ParameterGenerator('allowedScopes', 'array');
        $methodGenerator->setParameter($parameterGenerator);
        
        $body = '//For each of the elements, all of the scopes in that element'.PHP_EOL;
        $body .= '//must be satisfied'.PHP_EOL;
        $body .= '$requiredScopesArray = ['.PHP_EOL;
        
        foreach ($scopes as $scopeList) {
            $body .= '    [';
            $separator = '';
            foreach ($scopeList as $scope) {
                $body .= sprintf("%s'%s'", $separator, $scope);
                $separator = ', ';
            }
            $body .= ']'.PHP_EOL;
        }

        $body .= '];'.PHP_EOL.PHP_EOL;

        $body .= <<< 'END'
foreach($requiredScopesArray as $requiredScopes) {
     $requirementMet = true;
     foreach ($requiredScopes as $requiredScope) {
         if (in_array($requiredScope, $allowedScopes) == false) {
             $requirementMet = false;
             break;
         }
     }

    if ($requirementMet == true) {
        return true;
    }
}

return false;

END;

        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    

    /**
     * 
     */
    function addCreateRequestMethod() {

        $body = '$request = new \Artax\Request();'.PHP_EOL;

        $url = $this->operationDefinition->getURL();
        $body .= sprintf('$url = "%s";'.PHP_EOL, addslashes($url));
        $body .= sprintf('$request->setMethod(\'%s\');'.PHP_EOL, $this->operationDefinition->getHttpMethod());
        $body .= '$queryParameters = [];'.PHP_EOL;

        $body .= ''.PHP_EOL;
        $body .= '//Add parameters that are defined at the API level, not the'.PHP_EOL;
        $body .= '//operation level'.PHP_EOL;
        $apiParameters = $this->apiGenerator->getAPIParameters();
        foreach ($this->operationDefinition->getParameters() as $operationParameter) {
            foreach ($apiParameters as $apiParameter) {
                if ($apiParameter === $operationParameter->getName()) {

                    $translatedParam = ucfirst($this->apiGenerator->translateParameter($operationParameter->getName()));
                    
                    $body .= sprintf(
                            "\$queryParameters['%s'] = \$this->api->get%s();",
                            $apiParameter,
                            $translatedParam
                        ).PHP_EOL;
                }
            }
        }

        $hasBody = false;
        $hasJson = false;

        $body .= ''.PHP_EOL;

        //We only create a form body if one is needed. This string gets set 
        //to '' after it is used.
        

        $body .= '$formBody = null;'.PHP_EOL;

        $body .= '$jsonParams = [];'.PHP_EOL;
        
        
        foreach ($this->operationDefinition->getParameters() as $operationParameter) {

            if ($operationParameter->getLocation() === 'postField' ||
                $operationParameter->getLocation() === 'postFile') {
                $body .= '$formBody = new \Artax\FormBody;'.PHP_EOL;
                break;
            }
            
        }


        foreach ($this->operationDefinition->getParameters() as $operationParameter) {

            $body .= sprintf(
                'if (array_key_exists(\'%s\', $this->parameters) == true) {'.PHP_EOL,
                $operationParameter->getName()
            );


            
            switch($operationParameter->getLocation()) {

                case 'postField': {     
                    $body .= sprintf(
                        '    $formBody->addField(\'%s\', $this->parameters[\'%s\']);'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );

                    $hasBody = true;
                    break;
                }

                case 'postFile': {
                    $body .= sprintf(
                        '    $formBody->addFileField(\'%s\', $this->parameters[\'%s\']);'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                    $hasBody = true;
                    break;
                }
                    
                    
                case 'json': {
                    $body .= sprintf(
                        '    $jsonParams[\'%s\'] = $this->parameters[\'%s\'];'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                    $hasJson = true;
                    break;
                }

                case ('header'): {
                    $body .= sprintf( 
                        '    $request->setHeader(\'%s\', $this->parameters[\'%s\']);'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                    break;
                }

                default:
                case 'query': {
                    $body .= sprintf(
                        '    $queryParameters[\'%s\'] = $this->parameters[\'%s\'];'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                }
            }

            $body .= '}'.PHP_EOL;
        }

        if ($hasBody == true) {
            $body .= '$request->setBody($formBody);'.PHP_EOL;
        }

        if ($hasJson == true) {

            $body .= 'if (count($jsonParams)) {'.PHP_EOL;
            $body .= '    $jsonBody = json_encode($jsonParams);'.PHP_EOL;
            $body .= '    $request->setHeader("Content-Type", "application/json");'.PHP_EOL;
            $body .= '    $request->setBody($jsonBody);'.PHP_EOL;
            $body .= '}'.PHP_EOL;
        }

        $body .= '$uri = $url;'.PHP_EOL;
        $body .= 'if (count($queryParameters)) {'.PHP_EOL;
        $body .= '    $uri = $url.\'?\'.http_build_query($queryParameters, \'\', \'&\', PHP_QUERY_RFC3986);'.PHP_EOL;
        $body .= '}'.PHP_EOL;

        $body .= '$request->setUri($uri);'.PHP_EOL;
        $body .= ''.PHP_EOL;
        $body .= 'return $request;'.PHP_EOL;

        $methodGenerator = new MethodGenerator('createRequest');
        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    
    /**
     * 
     */
    function addAccessorMethods() {
        foreach($this->operationDefinition->getParameters() as $parameter) {

            $translatedParam = $this->apiGenerator->translateParameter($parameter->getName());
            
            $methodGenerator = new MethodGenerator('set'.ucfirst($translatedParam));
            $body = sprintf('$this->parameters[\'%s\'] = $%s;', $parameter->getName(), $translatedParam);
            $methodGenerator->setBody($body);
            $methodGenerator->setParameter($translatedParam);
            $this->classGenerator->addMethodFromGenerator($methodGenerator);
        }

        $methodGenerator = new MethodGenerator('getParameters');
        $body = 'return $this->parameters;';
        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    /**
     * 
     */
    private function addConstructorMethod() {
        $requiredParameters = $this->operationDefinition->getRequiredParams();
        $methodGenerator = new MethodGenerator('__construct');
        $defaultParams = $this->operationDefinition->getDefaultParams();

        $body = '';
        if (count($defaultParams)) {
            $body = '$defaultParams = ['.PHP_EOL;
            foreach ($defaultParams as $param) {
                $body .= sprintf("    '%s' => '%s',", $param->getName(), $param->getDefault());
                $body .= PHP_EOL;
            }
            $body .= '];'.PHP_EOL;
            $body .= '$this->setParams($defaultParams);'.PHP_EOL;
        }

        $constructorParams = [];
        foreach ($requiredParameters as $param) {
            $body .= sprintf(
                "\$this->parameters['%s'] = $%s;".PHP_EOL,
                $param->getName(),
                $param->getName()
            );

            $constructorParams[] = $param->getName();
        }

        $apiParameters = $this->apiGenerator->getAPIParameters();
        
        foreach ($requiredParameters as $param) {
            if (in_array($param->getName(), $apiParameters) == true) {
                if (in_array($param->getName(), $constructorParams) == false) { //TODO - how could this be possible?
                    $constructorParams[] = $param->getName();
                }
            }
        }

        $methodGenerator->setParameters($constructorParams);
        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    /**
     * 
     */
    private function addOptionalParamMethods() {

    }

    /**
     * 
     */
    function addExecuteMethod() {
        $methodGenerator = new MethodGenerator('execute');
        
        $body = '';
        $body .= '$request = $this->createRequest();'.PHP_EOL;

        if ($this->operationDefinition->getNeedsSigning()) {
            $body .= '$request = $this->api->signRequest($request);'.PHP_EOL;
        }
        
        $body .= '$response = $this->api->callAPI($request);'.PHP_EOL;

        $responseClass = $this->operationDefinition->getResponseClass();
        $responseFactory = $this->operationDefinition->getResponseFactory();
        
        if ($responseFactory) {
            //Response is turned by $responseFactory into $responseClass
            $body .= <<< END
\$instance = \\$responseClass::createFromResponse(\$response, \$this);

return \$instance;
END;
        }
        else if ($responseClass) {
            //Response is turned into $responseClass by a static method on that class
            $body .= <<< END
\$instance = \\$responseClass::createFromResponse(\$response, \$this);

return \$instance;
END;
        }
        else {
            //No hydrating of data done.
            $body .= 'return $response->getBody();';
        }

        $methodGenerator->setBody($body);
        $docBlock = new DocBlockGenerator('Execute the operation', null);
        if ($responseClass) {
            $tags[] = new GenericTag('return', '\\'.$responseClass);
        }
        else {
            $tags[] = new GenericTag('return', 'mixed');
        }
        $docBlock->setTags($tags);
        $methodGenerator->setDocBlock($docBlock);

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }

    /**
     *
     */
    function addDispatchMethod() {
        $methodGenerator = new MethodGenerator('dispatch');

        $body = '';

        if ($this->operationDefinition->getNeedsSigning()) {
            $body .= '$request = $this->api->signRequest($request);'.PHP_EOL;
        }

        $body .= '$response = $this->api->callAPI($request);'.PHP_EOL;

        $responseClass = $this->operationDefinition->getResponseClass();
        $responseFactory = $this->operationDefinition->getResponseFactory();

        if ($responseFactory) {
            //Response is turned by $responseFactory into $responseClass
            $body .= <<< END
\$instance = \\$responseClass::createFromResponse(\$response, \$this);

return \$instance;
END;
        }
        else if ($responseClass) {
            //TODO - encapsulate this to allow re-use in execute
            //Response is turned into $responseClass by a static method on that class
            $body .= <<< END
\$instance = \\$responseClass::createFromResponse(\$response, \$this);

return \$instance;
END;
        }
        else {
            //No hydrating of data done.
            $body .= 'return $response->getBody();';
        }

        $methodGenerator->setBody($body);
        $docBlock = new DocBlockGenerator(
            'Dispatch the request for this operation and process the response.', 
            'Allows you to modify the request before it is sent.'
        );
        if ($responseClass) {
            $tags[] = new GenericTag('return', '\\'.$responseClass);
        }
        else {
            $tags[] = new GenericTag('return', 'mixed');
        }
        $docBlock->setTags($tags);
        $methodGenerator->setDocBlock($docBlock);

        $parameter = new ParameterGenerator('request', 'Artax\Request');
        $methodGenerator->setParameter($parameter);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    
    
    /**
     * @throws \ArtaxApiBuilder\APIBuilderException
     */
    function generate() {

        if ($this->namespace) {
            $fqcn = $this->namespace.'\\'.$this->className;
        }
        else {
            $fqcn = $this->className;
        }

        $this->addProperties();
        $this->addConstructorMethod();
        $this->addSetAPIMethod();
        $this->addSetParameterMethod();
        $this->addCheckScopeMethod();
        $this->addCreateRequestMethod();
        
        $this->addAccessorMethods();
        $this->addOptionalParamMethods();
        $this->addExecuteMethod();
        $this->addDispatchMethod();

        $this->classGenerator->setImplementedInterfaces(['ArtaxApiBuilder\Operation']);
        $this->classGenerator->setFQCN($fqcn);
        $text = $this->classGenerator->generate();
        saveFile($this->outputPath, $fqcn, $text);
    }
}

 