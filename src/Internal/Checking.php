<?php

/******************************************************************************
 * An implementation of the "Formlets"-abstraction in PHP.
 * Copyright (c) 2014, 2015 Richard Klees <richard.klees@rwth-aachen.de>
 *
 * This software is licensed under The MIT License. You should have received 
 * a copy of the along with the code.
 */

namespace Lechimp\Formlets\Internal;

class Checking {
    static function typeName($arg) {
        $t = getType($arg);
        if ($t == "object") {
            return get_class($arg);
        }
        return $t;
    }


    static function guardIsString($arg) {
        if (!is_string($arg)) {
            throw new TypeError("string", typeName($arg));
        } 
    }

    static function guardIsInt($arg) {
        if (!is_int($arg)) {
            throw new TypeError("int", typeName($arg));
        } 
    }

    static function guardIsUInt($arg) {
        if (!is_int($arg) || $arg < 0) {
            throw new TypeError("unsigned int", typeName($arg));
        }
    }

    static function guardIsBool($arg) {
        if (!is_bool($arg)) {
            throw new TypeError("bool", typeName($arg));
        } 
    }

    static function guardIsArray($arg) {
        if(!is_array($arg)) {
            throw new TypeError("array", typeName($arg));
        }
    }

    static function guardIsObject($arg) {
        if(!is_object($arg)) {
            throw new TypeError("object", typeName($arg));
        }
    }

    static function guardIsCallable($arg) {
        if(!is_callable($arg)) {
            throw new TypeError("callable", typeName($arg));
        }
    }

    static function guardHasClass($class_name, $arg) {
        if (!($arg instanceof $arg)) {
            throw new TypeError($arg, typeName($arg));
        }
    }

    static function guardIsClosure($arg) {
        return guardHasClass("Closure", $arg);
    }

    static function guardIsException($arg) {
        return guardHasClass("Exception", $arg);
    }

    static function guardIsValue($arg) {
        return guardHasClass("Value", $arg);
    }

    static function guardIsErrorValue($arg) {
        return guardHasClass("ErrorValue", $arg);
    }

    static function guardIsHTML($arg) {
        return guardHasClass("HTML", $arg);
    }

    static function guardIsHTMLTag($arg) {
        return guardHasClass("HTMLTag", $arg);
    }

    static function guardIsFormlet ($arg) {
        return guardHasClass("Formlet", $arg);
    }

    static function guardHasArity(FunctionValue $fun, $arity) {
        if ($fun->arity() != $arity) {
            throw new TypeError( "FunctionValue with arity $arity"
                               , "FunctionValue with arity ".$fun->arity()
                               );
        }    
    }

    static function guardEach($vals, $fn) {
        guardIsArray($vals);
        foreach ($vals as $val) {
            call_user_func($fn, $val);
        }
    }

    static function guardEachAndKeys($vals, $fn_val, $fn_key) {
        guardIsArray($vals);
        foreach ($vals as $key => $val) {
            call_user_func($fn_val, $val);
            call_user_func($fn_key, $key);
        }
    }

    static function guardIfNotNull($val, $fn) {
        if ($val !== null) {
            call_user_func($fn, $val);
        }
    }
}

?>
