<?php

/**
 * Class Parser
 *
 * [операнд*опция1,опция2('параметр1'),опция3('параметр1','параметр2)',.....]
 *
 * @return [data, status, comment]
 */
class Parser {
    const STATUS_COMPLETE = 1;
    const STATUS_FAILURE = 0;

    public $data;
    public $status;
    public $comment;

    private $operandDelimiter = '*';
    private $optionsDelimiter = ',';
    private $initialParamDelimiter = ',';
    private $modifiedParamDelimiter = '|';
    private $initialBracketDelimiter = ',';
    private $modifiedBracketDelimiter = '~';

    private $optionsCounter;
    private $allowOptions;
    private $allowOperands;
    private $synonyms = array();

    public function __toString(){ return 'this'; }
    public function __invoke($field) { return $this->$field; }

    public function setOperandDelimiter($delimiter)
    {
        $this->operandDelimiter = $delimiter;
    }

    public function getOperandDelimiter()
    {
        return $this->operandDelimiter;
    }

    public function getInitialParamDelimiter() {
        return $this->initialParamDelimiter;
    }

    public function getModifiedParamDelimiter() {
        return $this->modifiedParamDelimiter;
    }

    public function getInitialBracketDelimiter() {
        return $this->initialBracketDelimiter;
    }

    public function getModifiedBracketDelimiter() {
        return $this->modifiedBracketDelimiter;
    }

    public function parse($expression, $listAllowOperands = array(), $listAllowOptions = array())
    {
        $this->flush();
        $this->allowOperands = $listAllowOperands;
        $this->allowOptions = $listAllowOptions;

        if (sizeof($this->allowOptions)) {
            foreach ($this->allowOptions as $option=>$count) {
                $this->optionsCounter[$option] = $count;
            }
        }

        $expression = trim($expression, '[]');

        /**
         * @output
         * If there is not delimiter for operand - we suppose that expression consist from one element.
         */
        if (strpos($expression, $this->getOperandDelimiter()) === false) {
            $this->status = self::STATUS_COMPLETE;
            $this->comment = '';
            $this->data = array(
                'tag' => $expression,
                'options' => array()
            );
            return $this;
        }

        list($operand, $stringOptions) = explode($this->getOperandDelimiter(), $expression, 2);

        if (!$operand) {
            /** @error */
            return $this->getError('Parsing expression error: the operand is not specified');
        }

        if (sizeof($this->allowOperands) && !in_array($operand, $this->allowOperands)) {
            /** @error */
            return $this->getError('Parsing expression error: illegal operand');
        }

        // Меняем запятую в скобках на другой символ
        $stringOptionsEdited = preg_replace_callback('#\(.*\)#U', function($matches) {
                extract(array('this' => requesting_class()));
                $matches[0] = str_replace(
                    "'" . $$this->getInitialParamDelimiter() . "'",
                    "'" . $$this->getModifiedParamDelimiter() . "'",
                    $matches[0]);
                $matches[0] = str_replace(
                    array(
                        $$this->getInitialParamDelimiter() . "'",
                        "'" . $$this->getInitialParamDelimiter(),
                    ),
                    $$this->getModifiedParamDelimiter(),
                    $matches[0]);
                $matches[0] = str_replace($$this->getInitialBracketDelimiter(), $$this->getModifiedBracketDelimiter(), $matches[0]);
                return $matches[0];
            }, $stringOptions);
        $arrayOptions = explode($this->optionsDelimiter, $stringOptionsEdited);
        $options = array();

        if (sizeof($arrayOptions)) {
            foreach ($arrayOptions as $option) {
                try {
                    $options[] = $this->processOption($option);
                }
                catch(Exception $e) {
                    return $this->getError($e->getMessage());
                }
            }
        }

        /** @output */
        $this->status = self::STATUS_COMPLETE;
        $this->comment = '';
        $this->data = array(
            'tag' => $operand,
            'options' => $options
        );
        return $this;
    }

    private function processOption($option)
    {
        $bracketPosition = strpos($option, '(');
        $hasExclamation = false;

        if ($bracketPosition !== false) {
            /** Extract string with parameters */
            $stringParameters = substr($option, $bracketPosition);
            /** Extract option name */
            $option = substr($option, 0, $bracketPosition);

            /** Exclamation mark processing */
            if (substr($option, 0, 1) == "!") {
                $hasExclamation = true;
                $option = substr($option, 1);
            }

            if (sizeof($this->allowOptions) && !in_array($option, array_keys($this->allowOptions))) {
                /** @breakpoint */
                throw new Exception('Function parsing error: illegal function');
            }

            $optionNameHash = md5($option);
            $optionName = (isset($this->synonyms[$optionNameHash])) ? $this->synonyms[$optionNameHash] : $option;
            return array(
                'name' => $optionName,
                'hasExclamation' => $hasExclamation,
                'parameters' => $this->processOptionParameters($stringParameters, $option)
            );
        }
        else {
            if (strpos($option, $this->modifiedParamDelimiter) !== false) {
                /** @breakpoint */
                throw new Exception('Function parsing error: the delimiter is founded');
            }
            else {
                if (sizeof($this->allowOptions) && !in_array($option, array_keys($this->allowOptions))) {
                    /** @breakpoint */
                    throw new Exception('Function parsing error: illegal function');
                }

                if (sizeof($this->optionsCounter[$option]) && $this->optionsCounter[$option] > 0) {
                    /** @breakpoint */
                    throw new Exception('Function parsing error: the number of parameters is not valid');
                }

                $optionNameHash = md5($option);
                $optionName = (isset($this->synonyms[$optionNameHash])) ? $this->synonyms[$optionNameHash] : $option;
                return array(
                    'name' => $optionName,
                    'hasExclamation' => $hasExclamation,
                    'parameters' => array()
                );
            }
        }
    }

    private function processOptionParameters($stringParameters, $option = '')
    {
        /** Delete parentheses */
        if (strpos($stringParameters, '(') === 0) {
            $stringParameters = substr($stringParameters, 1);
        }

        if (strrpos($stringParameters, ')') === strlen($stringParameters)-1) {
            $stringParameters = substr($stringParameters, 0, strlen($stringParameters)-1);
        }

        $parameters = array();

        if ($stringParameters) {
            if ($stringParameters[0] === $this->modifiedParamDelimiter
                || $stringParameters[strlen($stringParameters)-1] === $this->modifiedParamDelimiter
            ) {
                /** @breakpoint */
                throw new Exception('Parameter parsing error: the parameter does not exist');
            }

            $parameters = $this->extractParameters($stringParameters);

            if ($option && sizeof($this->allowOptions)) {
                if (isset($this->optionsCounter[$option])) {
                    if ($this->optionsCounter[$option] != sizeof($parameters)) {
                        /** @breakpoint */
                        throw new Exception('Function parsing error: the number of parameters is not valid');
                    }
                }
            }
        }

        return $parameters;
    }

    private function extractParameters($stringParameters)
    {
        $parameters = array();

        /** Explode string on modified delimiter of parameters or modified bracket delimiter */
        if (strpos($stringParameters, "'") !== false) {
            $arrayParameters = explode($this->modifiedParamDelimiter, $stringParameters);
        }
        else {
            $arrayParameters = explode($this->modifiedBracketDelimiter, $stringParameters);
        }

        foreach ($arrayParameters as $parameter) {
            if (empty($parameter)) {
                /** @breakpoint */
                throw new Exception('Parameter parsing error: the parameter does not exist');
            }

            if ((strpos($parameter, "'") !== 0) && (strrpos($parameter, "'") !== strlen($parameter)-1)) {
                $bracket = strpos($parameter, '(');

                if ($bracket !== false) {
                    $parameter = $this->processOption($parameter);
                }
            }
            else {
                $parameter = trim($parameter, "'");
                $parameter = str_replace($this->modifiedBracketDelimiter, $this->initialBracketDelimiter, $parameter);
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    public function flush()
    {
        $this->data = array();
        $this->status = self::STATUS_FAILURE;
        $this->comment = '';
        $this->optionsCounter = null;
    }

    private function getError($message)
    {
        $this->status = self::STATUS_FAILURE;
        $this->comment = $message;
        $this->data = array();
        return $this;
    }

    public function setSynonym($optionName, $synonymName)
    {
        $optionNameHash = md5($optionName);

        if (!isset($this->synonyms[$optionNameHash])) {
            $this->synonyms[$optionNameHash] = $synonymName;
        }
        else throw new Exception("Synonym for '$optionName' exists already", 1);
    }

    public function setSynonymList(array $list)
    {
        if (sizeof($list)) {
            foreach ($list as $option => $synonym) {
                $this->setSynonym($option, $synonym);
            }
        }
    }
}

function requesting_class()
{
    foreach(debug_backtrace(true) as $stack){
        if(isset($stack['object'])){
            return $stack['object'];
        }
    }

    return false;
}