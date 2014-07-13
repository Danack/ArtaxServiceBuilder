<?php

namespace AABTest;


/**
 * Trait DataMapper
 *
 * Allows objects to be created directly from an array of data, mapping keynames in the array to member
 * variable of an object. Each class that uses this trait _must_ define a static $dataMap variable to define
 * how their data should be mapped.
 *
 * static protected $dataMap = array(
 *     [
 *			'propertyName', 				Index 0 - the property name in the object
 *			['result', 'property_name'],	Index 1 - the property path in the data returned by the API. For multi-level
 * 											path, use an array for each segment of the path.
 *			'optional' => true				Index 'optional' - Optional flag of whether the property is optional.
 * 											Default is false and an Exception will be thrown if the value is not
 * 											available in the data.
 * 			'mutliple' => true 				Index 'multiple' - optional flag of whether the property should be created as
 * 											an array. Default is false.
 * 			'class'	=> 'Intahwebz\FlickrGuzzle\PropertyName'   Index 'class' - optional flag of whether to create the
 * 																property as an object. Value must be fully namespaced.
 * 			'unindex' => '_content'				Index 'unindex' - Optional flag of whether to 'unindex' the value from an
 * 											array to a plain value, if the value is an array. e.g.
 * 											$tag = $tag['_content']. Has no effect if the value is not an array or if that 											key is not set.
 *     ]
 * );
 *
 * Using the $dataMap above would convert the data in:
 *
 * $jsonData['result']['property_name'] into multiple instances of the class 'PropertyName' name and assign them
 * as the property '$this->propertyName' in the class that uses this trait.
 *
 * @package Intahwebz\FlickrGuzzle
 */
trait DataMapper {

    /**
     * @param $data array The map of how the data is mapped from the PHP object from the structure returned in the API.
     * @return static An instance of the class that the trait is used in. 'Static' is meant to be the 'late static class' - not many IDEs support this DOC comment yet.
     * @throws DataMapperException
     */
    static function createFromJson($jsonData){
        if (property_exists(__CLASS__, 'dataMap') == FALSE){
            throw new DataMapperException("Class ".__CLASS__." is using DataMapper but has no DataMap property.");
        }

        $instance = new static();
        $instance->mapPropertiesFromJSON($jsonData);
        return $instance;
    }
    
    
    /**
     * @param $data
     * @throws DataMapperException
     */
    function mapPropertiesFromJSON($data){
        foreach(static::$dataMap as $dataMapElement){
            if (is_array($dataMapElement) == FALSE) {
                $string = var_export(static::$dataMap, TRUE);
                throw new DataMapperException("DataMap is meant to be composed of arrays of entries. You've missed some brackets in class ".__CLASS__." : ".$string);
            }

            $dataFound = FALSE;

            $sourceValue = self::extractValueFromData($data, $dataMapElement, $dataFound);
            if ($dataFound == TRUE) {
                $this->setPropertyFromValue($dataMapElement, $sourceValue);
            }
        }
    }

    /**
     * Look in the $data for the value to be used for the mapping according to the rules set in $dataMapElement.
     *
     * @param $data
     * @param $dataMapElement
     * @return array|null
     * @throws DataMapperException
     */
    static function extractValueFromData($data, $dataMapElement, &$dataFound){
        $dataFound = FALSE;

        $dataVariableNameArray = $dataMapElement[1];
        if ($dataVariableNameArray == NULL) {
            //value is likely to be a class that has been merged into the Json at the root level,
            //so pass back same array, so that the class that will be instantiated has access to all of it.
            $dataFound = TRUE;
            return $data;
        }

        if (is_array($dataVariableNameArray) == FALSE){
            $dataVariableNameArray = array($dataVariableNameArray);
        }

        $value = $data;

        foreach($dataVariableNameArray as $dataVariableName){
            if (is_array($value) == FALSE ||
                array_key_exists($dataVariableName, $value) == FALSE){
                if (array_key_exists('optional', $dataMapElement) == TRUE &&
                    $dataMapElement['optional'] == TRUE){
                    return NULL;  //This value shouldn't be used as $dataFound is not set to true.
                }

                $dataPath = implode('->', $dataVariableNameArray);
                throw new DataMapperException("DataMapper cannot find value from $dataPath in source JSON to map to actual value in class ".__CLASS__);
            }

            $value = $value[$dataVariableName];
        }

        //Some API are badly behaved and return data either as
        //$tag = 'string' or $tag['_content'] = 'string'
        $value = self::unindexValue($value, $dataMapElement);
        $dataFound = TRUE;
        return $value;
    }

    /**
     * Unindex arrays to plain values if required. e.g. change
     * $title = array('_content' => 'Actual title');
     * to
     * $title = 'Actual title';
     *
     * @param $value
     * @param $dataMapElement
     * @return array
     */
    public static function unindexValue($value, $dataMapElement){
        if (array_key_exists('unindex', $dataMapElement) == TRUE) {
            $index = $dataMapElement['unindex'];
            if (is_array($value)) {
                if (array_key_exists($index, $value) == TRUE) {
                    $value = $value[$index];
                }
            }
        }

        return $value;
    }

    /**
     * Apply the value (or array of values) retrieved from Json and apply it to the instances property. If
     * the value represent a class, instantiate that class and map it's variables before setting it as the
     * properties value.
     *
     * @param $dataMapElement
     * @param $sourceValue
     */
    function setPropertyFromValue($dataMapElement, $sourceValue){
        $classVariableName = $dataMapElement[0];
        $className = FALSE;
        $multiple = FALSE;

        if(array_key_exists('class', $dataMapElement) == TRUE){
            $className = $dataMapElement['class'];
        }
        if(array_key_exists('multiple', $dataMapElement) == TRUE){
            $multiple = $dataMapElement['multiple'];
        }

        if ($sourceValue === null) {
            //TODO - add 'optional' == true check
            //Or even better a nullable option?
            $this->{$classVariableName} = null;
            return;
        }

        if($multiple == TRUE){
            if ($this->{$classVariableName} == NULL) {
                $this->{$classVariableName} = array();
            }

            foreach($sourceValue as $sourceValueInstance){
                if($className != FALSE){
                    $object = $className::createFromJson($sourceValueInstance);
                    $this->{$classVariableName}[] = $object;
                }
                else{
                    $this->{$classVariableName}[] = $sourceValueInstance;
                }
            }
        }
        else{
            if($className != FALSE){
                $object = $className::createFromJson($sourceValue);
                $this->{$classVariableName} = $object;
            }
            else{
                $this->{$classVariableName} = $sourceValue;
            }
        }
    }
}