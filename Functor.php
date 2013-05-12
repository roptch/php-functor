<?php

class FunctorInvalidConstructorException    extends Exception { }
class FunctorCallFailedException            extends Exception { }

class FunctorType
{
    const GLOBAL_FUNCTION   = 1;
    const CLASS_METHOD      = 2;
    const CLOSURE           = 3;
}

class Functor
{
    private $type;
    private $data;

    public function __construct()
    {
        $nbParams = func_num_args();
        $params = func_get_args();

        if ($nbParams == 1 && is_string($params[0])) {
            $this->type = FunctorType::GLOBAL_FUNCTION;
            $this->data = array('funcName' => $params[0]);
        }
        else if ($nbParams == 2 && is_string($params[1])) {
            $this->type = FunctorType::CLASS_METHOD;
            $this->data = array('object'    => $params[0],
                                'funcName'  => $params[1]);
        }
        else
            throw new FunctorInvalidConstructorException('Parameters of Functor constructor doesn\'t match any valid overloads of it.');
    }

    public function __invoke()
    {
        $nbParams   = func_num_args();
        $params     = func_get_args();

        try {
            $func = null;
            $callbackData = null;

            if ($this->type == FunctorType::GLOBAL_FUNCTION) {
                $func = new ReflectionFunction($this->data['funcName']);
                $callbackData = $this->data['funcName'];
            }
            else if ($this->type == FunctorType::CLASS_METHOD) {
                $obj = new ReflectionClass($this->data['object']);
                $func = $obj->getMethod($this->data['funcName']);
                $callbackData = array($this->data['object'], $this->data['funcName']);
            }

            if ($func->getNumberOfRequiredParameters() <= $nbParams)
                return call_user_func_array($callbackData, $params);
            else
                throw new FunctorCallFailedException('Function \'' . $this->data['funcName'] . '\' has ' . $func->getNumberOfRequiredParameters() . ' required parameters while passing it only ' . $nbParams . '.');
        }
        catch(ReflectionException $e) {
            throw new FunctorCallFailedException('Function \'' . $this->data['funcName'] . '\' is not valid (maybe it doesn\'t exist?).');
        }
    }
}
