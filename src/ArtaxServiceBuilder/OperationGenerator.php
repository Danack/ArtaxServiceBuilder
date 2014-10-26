<?php


namespace ArtaxServiceBuilder;


use Danack\Code\Generator\ClassGenerator;
use Danack\Code\Generator\DocBlockGenerator;
use Danack\Code\Generator\MethodGenerator;
use Danack\Code\Generator\ParameterGenerator;
use Danack\Code\Generator\DocBlock\Tag\GenericTag;
use Danack\Code\Reflection\DocBlock\Tag\ParamTag;
use Danack\Code\Generator\PropertyGenerator;

function createParamTag(ParameterGenerator $parameter, $description) {

    $paramType = $parameter->getType();

    $simpleTypes = [
        'array',
        'bool',
        'callable',
        'int',
        'mixed',
        'string'
    ];
    
    if (in_array(strtolower($paramType), $simpleTypes) == false) {
        $paramType = '\\'.$paramType;
    }
    
    $tag = new GenericTag(
        'param',
        sprintf(
            '%s $%s %s',
            $paramType,
            $parameter->getName(),
            $description
        )
    );
    return $tag;
}

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
     * Add the properties to the Operation that are always present.
     */
    function addProperties() {
        $requiredProperties = [
            'api' => '\\'.$this->apiClassname,
            'parameters' => 'array',
            'response' => '\Amp\Artax\Response'

        ];

        //TODO - deal with clashes between this and bits of the actual api
        foreach ($requiredProperties as $propertyName => $typehint) {
            $propertyGenerator = new PropertyGenerator($propertyName, null);
            $docBlock = new DocBlockGenerator('@var $api '.$typehint);
            $propertyGenerator->setDocBlock($docBlock);
            $this->classGenerator->addPropertyFromGenerator($propertyGenerator);
        }

        //We have to allow access to the last response for crappy APIs
        //that return information in the response headers.
        $docBlock = new DocBlockGenerator('Get the last response.');
        $body = 'return $this->response;';

        $methodGenerator = $this->createMethodGenerator('getResponse', $body, $docBlock, [], '\Amp\Artax\Response');
        $this->classGenerator->addMethodFromGenerator($methodGenerator);

        $docBlock = new DocBlockGenerator('Set the last response. This should only be used by the API class when the operation has been dispatched. Storing the response is required as some APIs store out-of-bound information in the headers e.g. rate-limit info, pagination that is not really part of the operation.');
        $body = '$this->response = $response;';

        $methodGenerator = $this->createMethodGenerator('setResponse', $body, $docBlock, [['response', 'Amp\Artax\Response']]);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
        
    }

    /**
     * Add the ability to change the API instance.
     */
    function addSetAPIMethod() {
        $methodGenerator = new MethodGenerator('setAPI');
        $methodGenerator->setBody('$this->api = $api;');
        $parameterGenerator = new ParameterGenerator('api', $this->apiClassname);
        $methodGenerator->setParameter($parameterGenerator);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }

    /**
     * Add the ability to set any/all params with an array of key value pairs.
     */
    function addSetParameterMethod() {
        $methodGenerator = new MethodGenerator('setParams');
        $parameterGenerator = new ParameterGenerator('params', 'array');
        $methodGenerator->setParameter($parameterGenerator);
        $body = '';
        foreach ($this->operationDefinition->getParameters() as $parameter) {
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
     * Generate the code for setting a parameter in the request
     * @param $indent
     * @param Parameter $operationParameter
     * @return string
     */
    private function generateParameterSetBlock($indent, \ArtaxServiceBuilder\Parameter $operationParameter) {

        switch ($operationParameter->getLocation()) {
            case 'absoluteURL': {
                return $indent.'$url = $value;'.PHP_EOL;
                break;
            }

            case 'postField': {
                return sprintf(
                    $indent.'$formBody->addField(\'%s\', $value);'.PHP_EOL,
                    $operationParameter->getSentAs()
                );
            }

            case 'postFile': {
                return sprintf(
                    $indent.'$formBody->addFileField(\'%s\', $value);'.PHP_EOL,
                    $operationParameter->getSentAs()
                );
                break;
            }

            case 'json': {
                return sprintf(
                    $indent.'$jsonParams[\'%s\'] = $value;'.PHP_EOL,
                    $operationParameter->getSentAs()
                );
            }

            case ('header'): {
                return sprintf(
                    $indent.'$request->setHeader(\'%s\', $value);'.PHP_EOL,
                    $operationParameter->getSentAs(),
                    $operationParameter->getName()
                );
            }

            default:
            case 'query': {
                return sprintf(
                    $indent.'$queryParameters[\'%s\'] = $value;'.PHP_EOL,
                    $operationParameter->getSentAs(),
                    $operationParameter->getName()
                );
            }
        }
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
     * Generate the method that creates an Ammp\Artax\Request from the operation.
     * 
     * TODO - refactor this into chunks when it's a bit more stable
     * TODO - use \Artax\Uri
     */
    function addCreateRequestMethod() {
        $body = '$request = new \Amp\Artax\Request();'.PHP_EOL;
        $url = $this->operationDefinition->getURL();
        $body .= '$url = null;'.PHP_EOL;
        $body .= sprintf('$request->setMethod(\'%s\');'.PHP_EOL, $this->operationDefinition->getHttpMethod());


        
        $body .= ''.PHP_EOL;

        $first = true;
        $hasQueryParams = false;
        $hasFormBody = false;
        $hasJsonBody = false;
        $hasURIVariables = false;

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


        if ($hasQueryParams) {
            $body .= '$queryParameters = [];'.PHP_EOL;
        }
        

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
                        "\$queryParameters['%s'] = \$this->api->get%s();".PHP_EOL,
                        ucfirst($apiParameter),
                        $translatedParam
                    );
                    $hasQueryParams = true;
                }
            }
        }

        $body .= ''.PHP_EOL;


        if ($hasFormBody) {
            $body .= '$formBody = new \Artax\FormBody;'.PHP_EOL;
        }

        if ($hasJsonBody == true) {
            $body .= '$jsonParams = [];'.PHP_EOL;
        }
        
        //TODO - check for multiple body types, either here or better yet in
        //operation definition. i.e. cannot set json body and multi-part in same request

        foreach ($this->operationDefinition->getParameters() as $operationParameter) {
            if ($operationParameter->getIsOptional() == true) {
                $body .= sprintf(
                    'if (array_key_exists(\'%s\', $this->parameters) == true) {'.PHP_EOL,
                    $operationParameter->getName()
                );
                $indent = '    ';
            }
            else {
                $indent = '';
            }

            $body .= sprintf(
                '    $value = $this->getFilteredParameter(\'%s\');'.PHP_EOL,
                $operationParameter->getName()
            );
            $closeSkipBlock = '';

            if ($operationParameter->getSkipIfNull() == true) {
                $body .= $indent.'if ($value != null) {'.PHP_EOL;
                $closeSkipBlock = $indent.'}'.PHP_EOL;
                $indent .= '    ';
            }

            $body .= $this->generateParameterSetBlock($indent, $operationParameter);
            $body .= $closeSkipBlock;

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

        //Nothing else has set the URL, use the one defined
        $body .= 'if ($url == null) {'.PHP_EOL;
        $body .= sprintf('    $url = "%s";'.PHP_EOL, addslashes($url));
        $body .= '}'.PHP_EOL;

        if ($hasURIVariables) {
            $body .= '$uriTemplate = new \ArtaxServiceBuilder\Service\UriTemplate\UriTemplate();'.PHP_EOL;
            $body .= '$url = $uriTemplate->expand($url, $this->parameters);'.PHP_EOL;
        }
        
        if ($hasQueryParams) {
            $body .= 'if (count($queryParameters)) {'.PHP_EOL;
            $body .= '    $url = $url.\'?\'.http_build_query($queryParameters, \'\', \'&\', PHP_QUERY_RFC3986);'.PHP_EOL;
            $body .= '}'.PHP_EOL;
        }

    
        $body .= '$request->setUri($url);'.PHP_EOL;
        $body .= ''.PHP_EOL;
        $body .= 'return $request;'.PHP_EOL;


        $tags[] = new GenericTag('return', '\Amp\Artax\Request');

        $docBlock = new DocBlockGenerator(
            'Create an Amp\Artax\Request object from the operation.',
            null,
            $tags
        );

        $methodGenerator = new MethodGenerator('createRequest');
        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    /**
     * Add methods to access each of the parameters in the operation.
     */
    function addAccessorMethods() {
        foreach($this->operationDefinition->getParameters() as $parameter) {
            $translatedParam = $this->apiGenerator->translateParameter($parameter->getName());
            $methodGenerator = new MethodGenerator('set'.ucfirst($translatedParam));
            $body = sprintf('$this->parameters[\'%s\'] = $%s;', $parameter->getName(), $translatedParam);
            $body .= "\n\n";
            $body .= 'return $this;';

            $tags = [];
            $docBlockTest = "Set $translatedParam";
            $description = trim($parameter->getDescription());
            $tags[] = new GenericTag('return', '$this');
            $docBlock = new DocBlockGenerator($docBlockTest, $description, $tags);
            $methodGenerator->setDocBlock($docBlock);
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
     * Add the constructor method for the operation
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
     * Helper method to avoid repitition
     * @return string
     */
    private function generateCreateFragment() {
        return '$request = $this->createRequest();'.PHP_EOL;
    }

    /**
     * Helper method to avoid repitition
     * @return string
     */
    private function generateExecuteFragment() {
        $body = '';

        if ($this->operationDefinition->getNeedsSigning()) {
            $body .= '$request = $this->api->signRequest($request);'.PHP_EOL;
        }

        $body .= '$response = $this->api->execute($request, $this);'.PHP_EOL;
        $body .= '$this->response = $response;'.PHP_EOL;

        return $body; 
    }

    /**
     * @return string
     */
    private function generateResponseFragment($indent = '') {
        $body = '';
        $responseClass = $this->operationDefinition->getResponseClass();
        $responseFactory = $this->operationDefinition->getResponseFactory();
        $responseCallable = $this->operationDefinition->getResponseCallable();

        if ($responseCallable) {
            throw new \Exception("This is not implemented yet.");
        }
        else if ($responseFactory) {
            //Response is turned by $responseFactory into $responseClass
            $body .= <<< END
${indent}\$instance = \\$responseClass::createFromResponse(\$response, \$this);

${indent}return \$instance;
END;
        }
        else if ($responseClass) {
            //Response is turned into $responseClass by a static method on that class
            $body .= <<< END
${indent}\$instance = \\$responseClass::createFromResponse(\$response, \$this);

${indent}return \$instance;
END;
        }
        else {
            //TODO - should this be like this or just return $response?
            //No hydrating of data done.
            $body .= $indent.'return $response->getBody();';
        }
        
        return $body;
    }

    /**
     * Generate a docblock generator for the execute method
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
     * Add the execute method to the operation
     */
    function addExecuteMethod() {
        $body  = $this->generateCreateFragment();
        $body .= 'return $this->dispatch($request);';
        $docBlock = $this->generateExecuteDocBlock('Execute the operation, returning the parsed response');

        $methodGenerator = new MethodGenerator('execute');
        $methodGenerator->setBody($body);
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }

    /**
     * Add the async execute method to the operation
     */
    function addExecuteAsyncMethod() {
        $body  = $this->generateCreateFragment();
        $body .= 'return $this->dispatchAsync($request, $callable);';
        $docBlock = $this->generateExecuteDocBlock('Execute the operation asynchronously, passing the parsed response to the callback');
        $callableParamGenerator = new ParameterGenerator('callable', 'callable');

        $methodGenerator = new MethodGenerator('executeAsync');
        $methodGenerator->setBody($body);
        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setParameters([$callableParamGenerator]);

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     * Generate the code required to filter a parameter of the operation.
     * @param Parameter $parameter
     * @return string
     * @throws APIBuilderException
     */
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

    /**
     * Add a method to get a parameter of this operation after it has been filtered.
     * @throws APIBuilderException
     */
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

        $parameterGenerator = new ParameterGenerator('name', 'string');
        
        $methodGenerator->setParameter($parameterGenerator);

        $tag = createParamTag($parameterGenerator, "The name of the parameter to get.");
        $docBlock->setTag($tag);
        
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);

    }
    
    
    /**
     * Add the method to create the request and execute it in one step
     */
    function addCreateAndExecuteMethod() {
        $methodGenerator = new MethodGenerator('createAndExecute');
        $body = '';
        $body .= $this->generateCreateFragment();
        $body .= $this->generateExecuteFragment();
        $body .= PHP_EOL;
        $body .= 'return $response;'.PHP_EOL;;
        $docBlock = new DocBlockGenerator('Create and execute the operation, returning the raw response from the server.', null);
        $tags[] = new GenericTag('return', '\Amp\Artax\Response');
        $docBlock->setTags($tags);
        
        $methodGenerator->setBody($body);
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     * Add the method that, creates the request, executes it, and processes the response
     * in one step. The method returns mixed rather than the opeartions response type,
     * as the api call may succeed, but not return data e.g. for HTTP 201 and 202 responses.
     */
    function addCallMethod() {
        $methodGenerator = new MethodGenerator('call');
        $body = '';
        $body .= $this->generateCreateFragment();
        $body .= $this->generateExecuteFragment().PHP_EOL;

        $responseClass = $this->operationDefinition->getResponseClass();

        $body .= 'if ($this->shouldResponseBeProcessed($response)) {'.PHP_EOL;
        $body .= $this->generateResponseFragment('    ');
        $body .= PHP_EOL.'}'.PHP_EOL;
        $body .= 'return $response;';
        
        $docBlock = new DocBlockGenerator('Create and execute the operation, then return the processed  response.', null);
        //The response will either be the response class, or mixed if the remote api returned
        //a response that does not contain data e.g. HTTP 201, HTTP 202
        $tags[] = new GenericTag('return', 'mixed|\\'.$responseClass);
        $docBlock->setTags($tags);

        $methodGenerator->setBody($body);
        $methodGenerator->setDocBlock($docBlock);
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    
    
    
    /**
     * Add a method to execute and parse the response of a previously created request.
     */
    function addDispatchMethod() {
        $methodGenerator = new MethodGenerator('dispatch');

        $body = '';
        $body .= $this->generateExecuteFragment();
        $body .= $this->generateResponseFragment();

        $docBlock = $this->generateExecuteDocBlock('Dispatch the request for this operation and process the response. Allows you to modify the request before it is sent.');

        $parameter = new ParameterGenerator('request', 'Amp\Artax\Request');
        $methodGenerator->setParameter($parameter);

        $tag = createParamTag($parameter, 'The request to be processed');
        $docBlock->setTag($tag);

        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);
        
        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     * Add a method to asynchronousl execute and parse the response of a previously created request.
     */
    function addDispatchAsyncMethod() {
        $methodGenerator = new MethodGenerator('dispatchAsync');
        $body = 'return $this->api->executeAsync($request, $this, $callable);';

        $docBlock = $this->generateExecuteDocBlock('Dispatch the request for this operation and process the response asynchronously. Allows you to modify the request before it is sent.');

        $requestParameter = new ParameterGenerator('request', 'Amp\Artax\Request');
        $methodGenerator->setParameter($requestParameter);
        $tag = createParamTag($requestParameter, 'The request to be processed');
        $docBlock->setTag($tag);

        $callableParameter = new ParameterGenerator('callable', 'callable');
        $methodGenerator->setParameter($callableParameter);
        $callableTag = createParamTag($callableParameter, 'The callable that processes the response');
        $docBlock->setTag($callableTag);

        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }
    

    /**
     * Add a method that process the response from the raw body, to the final type of 
     * the opeartion.
     */
    function addProcessResponseMethod() {

        $methodGenerator = new MethodGenerator('processResponse');

        $body = '';
        $body .= $this->generateResponseFragment();

        $docBlock = $this->generateExecuteDocBlock('Dispatch the request for this operation and process the response. Allows you to modify the request before it is sent.');

        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);

        $parameters = [];
        $parameters[] = new ParameterGenerator('response', 'Amp\Artax\Response');
        $methodGenerator->setParameters($parameters);
        $tag = createParamTag($parameters[0], 'The HTTP response.');
        $docBlock->setTag($tag);

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }

    /**
     * Helper method for generating trivial methods.
     * @param $methodName
     * @param $body
     * @param $docBlock
     * @param $parameterInfoArray
     * @return MethodGenerator
     */
    private function createMethodGenerator($methodName, $body, DocBlockGenerator $docBlock, $parameterInfoArray, $returnType = null) {
        $parameters = [];
        foreach ($parameterInfoArray as $parameterInfo) {
            $parameters[] = new ParameterGenerator($parameterInfo[0], $parameterInfo[1]);
        }

        $methodGenerator = new MethodGenerator($methodName);
        $methodGenerator->setParameters($parameters);
        
        if ($returnType != null) {
            if (is_array($returnType)) {
                $returnType = implode('|', $returnType);
            }
            
            $tags[] = new GenericTag('return', $returnType);
            $docBlock->setTags($tags);
        }
        
        $methodGenerator->setDocBlock($docBlock);
        $methodGenerator->setBody($body);
        
        return $methodGenerator;
    }


    
    /**
     * Add a method to determine whether the response is an error or not.
     * By default it delegates that decision to the main api class - override this method
     * to have a per operation decision.
     */
    function addIsErrorResponseMethod() {
        $body = 'return $this->api->isErrorResponse($response);';
        $docBlock = $this->generateExecuteDocBlock('Determine whether the response is an error. Override this method to have a per-operation decision, otherwise the function is the API class will be used.');

        $methodGenerator = $this->createMethodGenerator(
            'isErrorResponse', 
            $body,
            $docBlock,
            [['response', 'Amp\Artax\Response']]
        );

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     * Add a method to determine whether the response should be processed into a hydrated
     * response class. By default it delegates that decision to the main api class - override this method
     * to have a per operation decision.
     */
    public function addShouldResponseBeProcessedMethod() {
        $body = 'return $this->api->shouldResponseBeProcessed($response);';

        $docBlock = $this->generateExecuteDocBlock('Determine whether the response should be processed. Override this method to have a per-operation decision, otherwise the function is the API class will be used.');

        $methodGenerator = $this->createMethodGenerator(
            'shouldResponseBeProcessed',
            $body,
            $docBlock,
            [['response', 'Amp\Artax\Response']]
        );

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     * 
     */
    public function addShouldUseCachedResponseMethod() {
        $body = 'return $this->api->shouldUseCachedResponse($response);';

        $docBlock = $this->generateExecuteDocBlock('Determine whether the response indicates that we should use a cached response. Override this method to have a per-operation decision, otherwise the functionfrom the API class will be used.');

        $methodGenerator = $this->createMethodGenerator(
            'shouldUseCachedResponse',
            $body,
            $docBlock,
            [['response', 'Amp\Artax\Response']]
        );

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }


    /**
     *
     */
    public function addShouldResponseBeCachedMethod() {
        $body = 'return $this->api->shouldResponseBeCached($response);';

        $docBlock = $this->generateExecuteDocBlock('Determine whether the response should be cached. Override this method to have a per-operation decision, otherwise the function from the API class will be used.');

        $methodGenerator = $this->createMethodGenerator(
            'shouldResponseBeCached',
            $body,
            $docBlock,
            [['response', 'Amp\Artax\Response']]
        );

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }    

    /**
     * Add a method to determine whether the response should be processed into a hydrated
     * response class. By default it delegates that decision to the main api class - override this method
     * to have a per operation decision.
     */
    public function addTranslateResponseToExceptionMethod() {
        $body = 'return $this->api->translateResponseToException($response);';

        $docBlock = new DocBlockGenerator('Determine whether the response is an error. Override this method to have a per-operation decision, otherwise the function from the API class will be used.', null);

        $methodGenerator = $this->createMethodGenerator(
            'translateResponseToException',
            $body,
            $docBlock,
            [['response', 'Amp\Artax\Response']],
            ['null', '\ArtaxServiceBuilder\BadResponseException']
        );

        $this->classGenerator->addMethodFromGenerator($methodGenerator);
    }

    /**
     * Generate the complete operation class and save it to the filesystem.
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
        $this->addCreateRequestMethod();
        $this->addCreateAndExecuteMethod();
        $this->addCallMethod();
        
        $this->addExecuteMethod();
        $this->addExecuteAsyncMethod();
        $this->addDispatchMethod();
        $this->addDispatchAsyncMethod();
        $this->addProcessResponseMethod();
        //$this->addIsErrorResponseMethod();
        $this->addShouldResponseBeProcessedMethod();
        $this->addTranslateResponseToExceptionMethod();
        $this->addShouldUseCachedResponseMethod();
        $this->addShouldResponseBeCachedMethod();
        

        $this->classGenerator->setImplementedInterfaces(['ArtaxServiceBuilder\Operation']);
        $this->classGenerator->setFQCN($fqcn);
        $text = $this->classGenerator->generate();
        saveFile($this->outputPath, $fqcn, $text);
    }
}

 