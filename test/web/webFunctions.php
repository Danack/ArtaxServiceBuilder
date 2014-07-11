<?php


/**
 * @param $variable
 * @param bool $default
 * @param bool $minimum
 * @param bool $maximum
 * @return bool
 */
function getVariable($variable, $default = FALSE, $minimum = FALSE, $maximum = FALSE) {
    $variableName = $variable;

    if(isset($_REQUEST[$variableName]) == true){
        $result = $_REQUEST[$variableName];
    }
    else{
        $result = $default;
    }

    if($minimum !== FALSE){
        if($result < $minimum){
            $result = $minimum;
        }
    }

    if($maximum !== FALSE){
        if($result > $maximum){
            $result = $maximum;
        }
    }

    return $result;
}


function unsetSessionVariable($name){
    unset($_SESSION[SESSION_NAME][$name]);
}


function setSessionVariable($name, $value){
    $_SESSION[SESSION_NAME][$name] = $value;
    //$GLOBALS[$name] = $value;
}

function getSessionVariable($name, $default = FALSE, $clear = FALSE){

//    if(isset($GLOBALS['SESSION_FORBIDDEN']) && $GLOBALS['SESSION_FORBIDDEN'] == TRUE){
//        $GLOBALS['SESSION_FORBIDDEN'] = FALSE;
//        //logToFileFatal("Tried to access session variable [$name], when not in session.");
//        $GLOBALS['SESSION_FORBIDDEN'] = TRUE;
//    }

    if(isset($_SESSION[SESSION_NAME])){
        if(isset($_SESSION[SESSION_NAME][$name])){

            $value = $_SESSION[SESSION_NAME][$name];

            if($clear){
                unset($_SESSION[SESSION_NAME][$name]);
            }
            return $value;
        }
    }

    return $default;
}