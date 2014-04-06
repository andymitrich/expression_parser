<?php

/**
 * Class Parser
 * @version 0.0.2
 *
 * @author andymitrich <andymitrich@gmail.com>
 */
class Parser
{
    const STATUS_COMPLETE = 1;
    const STATUS_FAILURE = 0;

    private $optionsCounter;
    private $allowOptions;
    private $allowOperands;
    private $synonyms = array();

    private $levelDelimiter = '$';
    private $levelDelimiterCounter = 0;
    private $operandDelimiter = '*';
    private $optionDelimiter = ',';
    private $openParenthesisDelimiter = '(';
    private $closeParenthesisDelimiter = ')';

    public $data;
    public $status;
    public $comment;

    public function setOperandDelimiter($delimiter)
    {
        $this->operandDelimiter = $delimiter;
    }

    public function getOperandDelimiter()
    {
        return $this->operandDelimiter;
    }

    public function getDelimiter()
    {
        return $this->levelDelimiter;
    }

    public function getLevelDelimiter($level = null)
    {
        if ($level) {
            return $this->levelDelimiter . $level . $this->levelDelimiter;
        } else {
            return $this->levelDelimiter . $this->levelDelimiterCounter . $this->levelDelimiter;
        }
    }

    public function getOpenParenthesisDelimiter()
    {
        return $this->openParenthesisDelimiter;
    }

    public function setOpenParenthesisDelimiter($delimiter)
    {
        $this->openParenthesisDelimiter = $delimiter;
    }

    public function getCloseParenthesisDelimiter()
    {
        return $this->closeParenthesisDelimiter;
    }

    public function setCloseParenthesisDelimiter($delimiter)
    {
        $this->closeParenthesisDelimiter = $delimiter;
    }

    public function getOptionDelimiter()
    {
        return $this->optionDelimiter;
    }

    public function setOptionDelimiter($delimiter)
    {
        $this->optionDelimiter = $delimiter;
    }

    /**
     * Parse expression
     * @param $expression
     * @param array $listAllowOperands
     * @param array $listAllowOptions
     * @return $this
     */
    public function parse($expression, $listAllowOperands = array(), $listAllowOptions = array())
    {
        $this->flush();
        $this->allowOperands = $listAllowOperands;
        $this->allowOptions = $listAllowOptions;

        if (sizeof($this->allowOptions)) {
            foreach ($this->allowOptions as $option => $count) {
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

        try {
            $stringOptionsEdited = $this->replaceDelimiters($stringOptions);
        }
        catch(Exception $e) {
            return $this->getError($e->getMessage());
        }

        $arrayOptions = explode($this->getLevelDelimiter(), $stringOptionsEdited);
        $options = array();

        if (sizeof($arrayOptions)) {
            foreach ($arrayOptions as $option) {
                try {
                    $options[] = $this->processOption($option);
                } catch (Exception $e) {
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

    /**
     * Replacing commas onto level delimiters
     * @param $input
     * @return string
     * @throws Exception
     */
    private function replaceDelimiters($input)
    {
        $level = 0;
        $out = '';
        $isStringInitiated = false;

        for ($i = 0; $i < strlen($input); $i++) {
            switch ($input[$i]) {
                case $this->getOpenParenthesisDelimiter():
                    $level++;
                    $out .= $input[$i];
                    break;
                case $this->getOptionDelimiter():
                    if ($isStringInitiated) {
                        $out .= $input[$i];
                    } else {
                        $out .= $this->getLevelDelimiter($level);
                    }
                    break;
                case $this->getCloseParenthesisDelimiter():
                    $level--;
                    $out .= $input[$i];
                    break;
                case "'":
                    if ($input[$i - 1] == $this->getOpenParenthesisDelimiter()
                        || $input[$i - 1] == $this->getOptionDelimiter()
                    ) {
                        $out .= $input[$i];

                        if ($isStringInitiated) throw new Exception("Expression parsing error: illegal number of quotes");
                        else $isStringInitiated = true;
                    } elseif ($input[$i + 1] == $this->getOptionDelimiter()
                        || $input[$i + 1] == $this->getCloseParenthesisDelimiter()
                    ) {
                        $out .= $input[$i];
                        $isStringInitiated = false;
                    } elseif ($input[$i - 1] == "\\") {
                        $out .= $input[$i];
                    }
                    break;
                default:
                    $out .= $input[$i];
                    break;
            }
        }

        if ($isStringInitiated) {
            throw new Exception("Expression parsing error: illegal number of quotes");
        }

        if ($level) {
            throw new Exception("Expression parsing error: illegal number of parentheses");
        }

        return $out;
    }

    /**
     * Option processing
     * @param $option
     * @return array
     * @throws Exception
     */
    private function processOption($option)
    {
        $bracketPosition = strpos($option, $this->getOpenParenthesisDelimiter());
        $hasExclamation = false;

        /** If there are parentheses - try to extract parameters */
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
        } else {
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

    /**
     * Extract parameters for option
     * @param $stringParameters String with parameters
     * @param string $option Option title
     * @return array
     * @throws Exception
     */
    private function processOptionParameters($stringParameters, $option = '')
    {
        /** Delete parentheses */
        if (strpos($stringParameters, $this->getOpenParenthesisDelimiter()) === 0) {
            $stringParameters = substr($stringParameters, 1);
        }

        if (strrpos($stringParameters, $this->getCloseParenthesisDelimiter()) === strlen($stringParameters) - 1) {
            $stringParameters = substr($stringParameters, 0, strlen($stringParameters) - 1);
        }

        $parameters = array();

        /** If string with parameters exists, try to extract their */
        if ($stringParameters) {
            $this->levelDelimiterCounter++;
            $parameters = $this->extractParameters($stringParameters);
            $this->levelDelimiterCounter--;

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

    /**
     * Extract parameters from string
     * @param $stringParameters String with parameters
     * @return array
     * @throws Exception
     */
    private function extractParameters($stringParameters)
    {
        $parameters = array();
        /** Getting level delimiter for current depth */
        $arrayParameters = explode($this->getLevelDelimiter(), $stringParameters);

        foreach ($arrayParameters as $parameter) {
            if (empty($parameter)) {
                /** @breakpoint */
                throw new Exception('Parameter parsing error: the parameter does not exist');
            }

            /**
             * Try to determine does inner option exist
             * If inner option exists - process it
             */
            if ((strpos($parameter, "'") !== 0) && (strrpos($parameter, "'") !== strlen($parameter) - 1)) {
                $parenthesisPosition = strpos($parameter, $this->getOpenParenthesisDelimiter());

                if ($parenthesisPosition !== false) {
                    $parameter = $this->processOption($parameter);
                }
            } else {
                $parameter = trim($parameter, "'");
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * Flush inner fields
     */
    public function flush()
    {
        $this->data = array();
        $this->status = self::STATUS_FAILURE;
        $this->comment = '';
        $this->optionsCounter = null;
    }

    /**
     * Display error
     * @param $message
     * @return $this
     */
    private function getError($message)
    {
        $this->status = self::STATUS_FAILURE;
        $this->comment = $message;
        $this->data = array();
        return $this;
    }

    /**
     * Set synonym for option
     * @param $optionName
     * @param $synonymName
     * @throws Exception
     */
    public function setSynonym($optionName, $synonymName)
    {
        $optionNameHash = md5($optionName);

        if (!isset($this->synonyms[$optionNameHash])) {
            $this->synonyms[$optionNameHash] = $synonymName;
        } else {
            throw new Exception("Synonym for '$optionName' exists already", 1);
        }
    }

    /**
     * Set list of synonyms
     * @param array $list
     */
    public function setSynonymList(array $list)
    {
        if (sizeof($list)) {
            foreach ($list as $option => $synonym) {
                $this->setSynonym($option, $synonym);
            }
        }
    }
}