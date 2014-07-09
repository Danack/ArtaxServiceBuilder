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
    private $operation;
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
        $this->operation = $operation;
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
        foreach($this->operation->getParameters() as $parameter) {
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
     * 
     */
    function addCreateRequestMethod() {

        $body = '$request = new \Artax\Request();'.PHP_EOL;

        $url = $this->operation->getURL();
        $body .= sprintf('$url = "%s";'.PHP_EOL, addslashes($url));
        $body .= sprintf('$request->setMethod(\'%s\');'.PHP_EOL, $this->operation->getHttpMethod());
        $body .= '$queryParameters = [];'.PHP_EOL;

        $body .= ''.PHP_EOL;
        $body .= '//Add parameters that are defined at the API level, not the'.PHP_EOL;
        $body .= '//operation level'.PHP_EOL;
        $apiParameters = $this->apiGenerator->getAPIParameters();
        foreach ($this->operation->getParameters() as $operationParameter) {
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

        $body .= ''.PHP_EOL;

        //We only create a form body if one is needed. This string gets set 
        //to '' after it is used.
        

        $body .= '$formBody = null;'.PHP_EOL;
        
        
        foreach ($this->operation->getParameters() as $operationParameter) {

            if ($operationParameter->getLocation() === 'postField' ||
                $operationParameter->getLocation() === 'postFile') {
                $body .= '$formBody = new \Artax\FormBody;'.PHP_EOL;
                break;
            }
        }


        foreach ($this->operation->getParameters() as $operationParameter) {

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
        foreach($this->operation->getParameters() as $parameter) {

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
        $requiredParameters = $this->operation->getRequiredParams();
        $methodGenerator = new MethodGenerator('__construct');
        $defaultParams = $this->operation->getDefaultParams();

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
        $body .= '$response = $this->api->callAPI($request);'.PHP_EOL;

        $responseClass = $this->operation->getResponseClass();
        $responseFactory = $this->operation->getResponseFactory();
        
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
        $this->addCreateRequestMethod();
        
        $this->addAccessorMethods();
        $this->addOptionalParamMethods();
        $this->addExecuteMethod();

        $this->classGenerator->setImplementedInterfaces(['ArtaxApiBuilder\Operation']);
        $this->classGenerator->setFQCN($fqcn);
        $text = $this->classGenerator->generate();
        saveFile($this->outputPath, $fqcn, $text);
    }
}

 