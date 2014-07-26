<?php


namespace ArtaxServiceBuilder;


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
        \ArtaxServiceBuilder\APIGenerator $api
    ) {
        $this->namespace = $namespace;
        $this->className = $className;
        $this->operationDefinition = $operation;
        $this->outputPath = $outputPath;
        $this->classGenerator = new ClassGenerator();
        $this->apiGenerator = $api;
    }

    function getFQCN() {
        return $this->namespace.'\\'.$this->className;
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
            'parameters' => 'array',
            'response' => '\Artax\Response'
        ];

        //TODO - deal with clashes between this and bits of the actual api
        foreach ($requiredProperties as $propertyName => $typehint) {
            $propertyGenerator = new PropertyGenerator($propertyName, null);
            $docBlock = new DocBlockGenerator('@var $api '.$typehint);
            $propertyGenerator->setDocBlock($docBlock);
            $this->classGenerator->addPropertyFromGenerator($propertyGenerator);
        }
        
        $docBlock = new DocBlockGenerator('Get the last response.');
        $tags[] = new GenericTag('return', '\Artax\Response');
        $docBlock->setTags($tags);

        $methodGenerator = new MethodGenerator('getResponse');
        $body = 'return $this->response;';
        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
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
     * The biggest function.
     * 
     * TODO - refactor this into chunks when it's a bit more stable
     * TODO - use \Artax\Uri
     */
    function addCreateRequestMethod() {
        $body = '$request = new \Artax\Request();'.PHP_EOL;
        $url = $this->operationDefinition->getURL();
        $body .= sprintf('$url = "%s";'.PHP_EOL, addslashes($url));
        $body .= sprintf('$request->setMethod(\'%s\');'.PHP_EOL, $this->operationDefinition->getHttpMethod());




        $body .= '$queryParameters = [];'.PHP_EOL;

        $body .= ''.PHP_EOL;

        $first = true;
        $hasQueryParams = false;
        
        $apiParameters = $this->apiGenerator->getAPIParameters();
        foreach ($this->operationDefinition->getParameters() as $operationParameter) {

            foreach ($apiParameters as $apiParameter) {
                if ($apiParameter === $operationParameter->getName()) {

                    if ($first) {
                        $body .= '//Add parameters that are defined at the API level, not the'.PHP_EOL;
                        $body .= '//operation level'.PHP_EOL;
                        $first = false;
                    }

                    $translatedParam = ucfirst($this->apiGenerator->translateParameter($operationParameter->getName()));
                    //TODO - this is wrong...they should be stored in just $params, then copied to query params
                    //if they are actually sent in the query.
                    $body .= sprintf(
                            "\$queryParameters['%s'] = \$this->api->get%s();",
                            ucfirst($apiParameter),
                            $translatedParam
                        ).PHP_EOL;
                    $hasQueryParams = true;
                }
            }
        }

        
        $hasFormBody = false;
        $hasJsonBody = false;
        $hasURIVariables = false;

        $body .= ''.PHP_EOL;
        foreach ($this->operationDefinition->getParameters() as $operationParameter) {

            if ($operationParameter->getLocation() === 'postField' ||
                $operationParameter->getLocation() === 'postFile') {
                $hasFormBody = true;
            }
            if ($operationParameter->getLocation() === 'json') {
                $hasJsonBody = true;
            }

            if ($operationParameter->getLocation() === 'uri') {
                $hasURIVariables = true;
            }

            if ($operationParameter->getLocation() === 'query') {
                $hasQueryParams = true;
            }

            if ($operationParameter->getLocation() === 'absoluteURL') {
                //$body .= '$url = '
                //TODO - throw an error if both absoluteURL and uri variables are set
            }
        }

        if ($hasURIVariables) {
            $body .= '$uriTemplate = new \ArtaxServiceBuilder\Service\UriTemplate\UriTemplate();'.PHP_EOL;
            $body .= '$url = $uriTemplate->expand($url, $this->parameters);'.PHP_EOL;
        }

        if ($hasFormBody) {
            $body .= '$formBody = new \Artax\FormBody;'.PHP_EOL;
        }

        if ($hasJsonBody == true) {
            $body .= '$jsonParams = [];'.PHP_EOL;
        }
        
        //TODO - check for multiple body types, either here or better yet in
        //operation definition.

        foreach ($this->operationDefinition->getParameters() as $operationParameter) {
            
            if ($operationParameter->getIsOptional() == true) {
                $body .= sprintf(
                    'if (array_key_exists(\'%s\', $this->parameters) == true) {'.PHP_EOL,
                    $operationParameter->getName()
                );
                $indent = '   ';
            }
            else {
                $indent = '';
            }

            switch($operationParameter->getLocation()) {
                case 'absoluteURL': {
                    $body .= sprintf(
                        $indent.'$url = $this->parameters[\'%s\'];'.PHP_EOL,
                        $operationParameter->getName()
                    );
                    break;
                }

                case 'postField': {
                    $body .= sprintf(
                        $indent.'$formBody->addField(\'%s\', $this->getFilteredParameter(\'%s\'));'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                    break;
                }

                case 'postFile': {
                    $body .= sprintf(
                        $indent.'$formBody->addFileField(\'%s\', $this->getFilteredParameter(\'%s\'));'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                    break;
                }

                case 'json': {
                    $body .= sprintf(
                        $indent.'$jsonParams[\'%s\'] = $this->getFilteredParameter(\'%s\');'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                    break;
                }

                case ('header'): {
                    $body .= sprintf(
                        $indent.'$request->setHeader(\'%s\', $this->getFilteredParameter(\'%s\'));'.PHP_EOL,
                        $operationParameter->getSentAs(),
                        $operationParameter->getName()
                    );
                    break;
                }

                default:
                case 'query': {
                    $body .= sprintf(
                        $indent.'$queryParameters[\'%s\'] = $this->getFilteredParameter(\'%s\');'.PHP_EOL,
                        $operationParameter->getName(),
                        $operationParameter->getName()
                    );
                }
            }

            if ($operationParameter->getIsOptional() == true) {
                $body .= '}'.PHP_EOL;
            }
        }

        $body .= PHP_EOL;
        $body .= '//Parameters are parsed and set, lets prepare the request'.PHP_EOL;
        
        if ($hasFormBody == true) {
            $body .= '$request->setBody($formBody);'.PHP_EOL;
        }

        if ($hasJsonBody == true) {
            $body .= 'if (count($jsonParams)) {'.PHP_EOL;
            $body .= '    $jsonBody = json_encode($jsonParams);'.PHP_EOL;
            $body .= '    $request->setHeader("Content-Type", "application/json");'.PHP_EOL;
            $body .= '    $request->setBody($jsonBody);'.PHP_EOL;
            $body .= '}'.PHP_EOL;
        }

        if ($hasQueryParams) {
            //$body .= '$url = $url;'.PHP_EOL;
            $body .= 'if (count($queryParameters)) {'.PHP_EOL;
            $body .= '    $url = $url.\'?\'.http_build_query($queryParameters, \'\', \'&\', PHP_QUERY_RFC3986);'.PHP_EOL;
            $body .= '}'.PHP_EOL;
        }

        $body .= '$request->setUri($url);'.PHP_EOL;
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
        $constructorParams[] = new ParameterGenerator('api', $this->apiGenerator->getFQCN());
        $body .= '$this->api = $api;'.PHP_EOL;

        foreach ($requiredParameters as $param) {
            $constructorParams[] = $param->getName();

            $body .= sprintf(
                "\$this->parameters['%s'] = $%s;".PHP_EOL,
                $param->getName(),
                $param->getName()
            );
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

    private function generateCreateFragment() {
        return '$request = $this->createRequest();'.PHP_EOL;
    }
    
    private function generateCallFragment() {
        $body = '';

        if ($this->operationDefinition->getNeedsSigning()) {
            $body .= '$request = $this->api->signRequest($request);'.PHP_EOL;
        }

        $body .= '$response = $this->api->callAPI($request);'.PHP_EOL;
        $body .= '$this->response = $response;'.PHP_EOL;
        

        return $body; 
    }
    
    private function generateResponseFragment() {
        $body = '';
        $responseClass = $this->operationDefinition->getResponseClass();
        $responseFactory = $this->operationDefinition->getResponseFactory();
        $responseCallable = $this->operationDefinition->getResponseCallable();

        if ($responseCallable) {
            //TODO -support this
            exit(0);
        }
        else if ($responseFactory) {
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
            //TODO - should this be like this or just return $response?
            //No hydrating of data done.
            $body .= 'return $response->getBody();';
        }
        
        return $body;
    }

    /**
     * Generate the docblock generator for methods that return a hydrated object
     * @return DocBlockGenerator
     */
    private function generateExecuteDocBlock($methodDescription) {

        $responseClass = $this->operationDefinition->getResponseClass();
        $docBlock = new DocBlockGenerator($methodDescription, null);
        if ($responseClass) {
            $tags[] = new GenericTag('return', '\\'.$responseClass);
        }
        else {
            $tags[] = new GenericTag('return', 'mixed');
        }
        $docBlock->setTags($tags);

        return $docBlock;
    }
    
    
    /**
     * 
     */
    function addExecuteMethod() {
        $methodGenerator = new MethodGenerator('execute');
        $body = '';
        $body .= $this->generateCreateFragment();
        $body .= $this->generateCallFragment();
        $body .= $this->generateResponseFragment();
        $methodGenerator->setBody($body);
        $docBlock = $this->generateExecuteDocBlock('Execute the operation');
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    function generateParamFilterBlock(\ArtaxServiceBuilder\Parameter $parameter) {

        $i1 = '    ';//Indent 1
        $i2 = '        ';//Indent 1
        
        
        $text = '';

        $text .= sprintf(
            $i1."case ('%s'): {".PHP_EOL,
            $parameter->getName()
        );
        
        foreach ($parameter->getFilters() as $filter) {

            if (is_array($filter)) {
                $text .= $i2.'$args = [];'.PHP_EOL;

                if (is_array($filter['args']) == false) {
                    throw new \ArtaxServiceBuilder\APIBuilderException("Filter args should be an array instead received ".var_export($filter['args'], true));
                }
                
                // Convert complex filters that hold value place holders
                foreach ($filter['args'] as $data) {
                    if ($data == '@value') {
                        $text .= $i2.'$args[] = $value;'.PHP_EOL;
                    }
                    elseif ($data == '@api') {
                        $data = $this;
                        $text .= $i2."\$args[] = \$this->\$api;".PHP_EOL;
                    }
                    else {
                        //It should be a string
                        $text .= $i2."\$args[] = $data;".PHP_EOL;
                    }
                }

                $text .= sprintf(
                    //TODO - we can do better than call_user_func_array
                    $i2.'$value = call_user_func_array(\'%s\', $args);'.PHP_EOL,
                    $filter['method']
                );
            }
            else {
                //TODO - get rid of call_user_func
                $text .= sprintf(
                    $i2.'call_user_func(\'%s\', $value);'.PHP_EOL,
                    $filter
                );
            }
        }

        $text .= $i1.'    break;'.PHP_EOL;
        $text .= $i1.'}'.PHP_EOL;

        return $text;
    }
    

    function addFilteredParameterMethod() {
        $methodGenerator = new MethodGenerator('getFilteredParameter');
        $body = 'if (array_key_exists($name, $this->parameters) == false) {'.PHP_EOL;
        //TODO - make this be the correct type
        $body .= '    throw new \Exception(\'Parameter \'.$name.\' does not exist.\');'.PHP_EOL;
        $body .= '}'.PHP_EOL;
        $body .= ''.PHP_EOL;
        $body .= '$value = $this->parameters[$name];'.PHP_EOL;
        $body .= ''.PHP_EOL;
        
        $paramFilterBlocks = [];

        foreach ($this->operationDefinition->getParameters() as $parameter) {
            $parameterFilters = $parameter->getFilters();
            
            if (count($parameterFilters)) {
                //Only generate the filter block if a filter actually need to be applied
                $paramFilterBlocks[] = $this->generateParamFilterBlock($parameter);
            }
        }
        
        if (count($paramFilterBlocks)) {
            $body .= 'switch ($name) {'.PHP_EOL;
            $body .= ''.PHP_EOL;
            foreach ($paramFilterBlocks as $paramFilterBlock) {
                $body .= $paramFilterBlock.PHP_EOL;
                $body .= ''.PHP_EOL;
            }
            $body .= '    default:{}'.PHP_EOL;
            $body .= ''.PHP_EOL;
            $body .= '}'.PHP_EOL;
        }

        $body .= ''.PHP_EOL;
        $body .= 'return $value;'.PHP_EOL;
        
        $methodGenerator->setBody($body);
        $docBlock = $this->generateExecuteDocBlock('Apply any filters necessary to the parameter');
        $methodGenerator->setParameter('name');
        
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);

    }
    
    
    /**
     * 
     */
    function addCreateAndCallMethod() {
        $methodGenerator = new MethodGenerator('createAndCall');
        $body = '';
        $body .= $this->generateCreateFragment();
        $body .= $this->generateCallFragment();
        $body .= PHP_EOL;
        $body .= 'return $response;'.PHP_EOL;;
        $docBlock = new DocBlockGenerator('Create and call the operation, returning the raw response from the server.', null);
        $tags[] = new GenericTag('return', '\Artax\Response');
        $docBlock->setTags($tags);
        
        $methodGenerator->setBody($body);
        //$docBlock = $this->generateExecuteDocBlock('Execute the operation');
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    
    
    /**
     *
     */
    function addDispatchMethod() {
        $methodGenerator = new MethodGenerator('dispatch');

        $body = '';
        $body .= $this->generateCallFragment();
        $body .= $this->generateResponseFragment();

        $docBlock = $this->generateExecuteDocBlock('Dispatch the request for this operation and process the response.Allows you to modify the request before it is sent.');
        
        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);

        $parameter = new ParameterGenerator('request', 'Artax\Request');
        $methodGenerator->setParameter($parameter);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    function addProcessResponseMethod() {

        $methodGenerator = new MethodGenerator('processResponse');

        $body = '';
        $body .= $this->generateResponseFragment();

        $docBlock = $this->generateExecuteDocBlock('Dispatch the request for this operation and process the response.Allows you to modify the request before it is sent.');

        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);

        $parameter = new ParameterGenerator('response', 'Artax\Response');
        $methodGenerator->setParameter($parameter);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    
    
    /**
     * @throws \ArtaxServiceBuilder\APIBuilderException
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
        $this->addAccessorMethods();
        $this->addFilteredParameterMethod();
        $this->addOptionalParamMethods();
        $this->addCreateRequestMethod();
        $this->addCreateAndCallMethod();
        $this->addExecuteMethod();
        $this->addDispatchMethod();
        $this->addProcessResponseMethod();
        
        

        $this->classGenerator->setImplementedInterfaces(['ArtaxServiceBuilder\Operation']);
        $this->classGenerator->setFQCN($fqcn);
        $text = $this->classGenerator->generate();
        saveFile($this->outputPath, $fqcn, $text);
    }
}

 