<?php

/**
 * Class Parser
 *
 * [операнд*опция1,опция2('параметр1'),опция3('параметр1','параметр2)',.....]
 *
 * @return [data, status, comment]
 */
class Parser {
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

    public function parse($expression, $listAllowOperands = array(), $listAllowOptions = array())
    {
        $this->flush();
        if (sizeof($listAllowOptions)) {
            foreach ($listAllowOptions as $option=>$count) {
                $this->optionsCounter[$option] = $count;
            }
        }

        $expression = trim($expression, '[]');

        if (strpos($expression, $this->operandDelimiter) === false) {
            return $this->getError('Отсутствует разделитель для операнда');
        }

        list($operand, $stringOptions) = explode($this->operandDelimiter, $expression, 2);

        if (!$operand) {
            return $this->getError('Ошибка парсинга выражения: не указан операнд');
        }

        if (sizeof($listAllowOperands) && !in_array($operand, $listAllowOperands)) {
            return $this->getError('Ошибка парсинга выражения: недопустимый операнд');
        }

        // Меняем запятую в скобках на другой символ
        $stringOptionsEdited = preg_replace_callback('#\(.*\)#U', function($matches) {
                $matches[0] = str_replace(
                    "'" . $this->initialParamDelimiter . "'",
                    "'" . $this->modifiedParamDelimiter . "'",
                    $matches[0]);
                $matches[0] = str_replace(
                    array(
                        $this->initialParamDelimiter . "'",
                        "'" . $this->initialParamDelimiter,
                    ),
                    $this->modifiedParamDelimiter,
                    $matches[0]);
                $matches[0] = str_replace($this->initialBracketDelimiter, $this->modifiedBracketDelimiter, $matches[0]);
                return $matches[0];
            }, $stringOptions);
        $arrayOptions = explode($this->optionsDelimiter, $stringOptionsEdited);
        $options = array();

        if (sizeof($arrayOptions)) {
            foreach ($arrayOptions as $option) {
                $bracketPosition = strpos($option, '(');

                if ($bracketPosition !== false) {
                    $stringParameters = substr($option, $bracketPosition);
                    $option = substr($option, 0, $bracketPosition);
                    $stringParameters = trim($stringParameters, "(,)");

                    if (sizeof($listAllowOptions) && !in_array($option, array_keys($listAllowOptions))) {
                        return $this->getError('Ошибка парсинга опции: недопустимая опция');
                    }

                    if (!$stringParameters) {
                        return $this->getError('Ошибка парсинга параметров: Параметры не указаны');
                    }

                    if ($stringParameters[0] === $this->modifiedParamDelimiter
                        || $stringParameters[strlen($stringParameters)-1] === $this->modifiedParamDelimiter
                    ) {
                        return $this->getError('Ошибка парсинга параметров: Не указан параметр');
                    }

                    if (strpos($stringParameters, "'") !== false) {
                        $arrayParameters = explode($this->modifiedParamDelimiter, $stringParameters);
                    }
                    else {
                        $arrayParameters = explode($this->modifiedBracketDelimiter, $stringParameters);
                    }

                    $parameters = array();

                    foreach ($arrayParameters as $parameter) {
                        if (empty($parameter)) {
                            return $this->getError('Ошибка парсинга параметров: Не указан параметр');
                        }

                        $parameter = trim($parameter, "'");
                        $parameter = str_replace($this->modifiedBracketDelimiter, $this->initialBracketDelimiter, $parameter);
                        $parameters[] = $parameter;
                    }

                    if (sizeof($listAllowOptions))
                        if (isset($this->optionsCounter[$option])) {
                            if ($this->optionsCounter[$option] != sizeof($parameters)) {
                                return $this->getError('Ошибка парсинга опции: неверное число параметров для опции ' . $option);
                            }
                        }
                        else return $this->getError('Ошибка парсинга опции: недопустимая опция');

                    $options[] = array(
                        'name' => $option,
                        'parameters' => $parameters
                    );
                }
                else {
                    if (strpos($option, $this->modifiedParamDelimiter) !== false) {
                        return $this->getError('Ошибка парсинга опции: найден разделитель опции');
                    }
                    else {
                        if (sizeof($listAllowOptions) && !in_array($option, array_keys($listAllowOptions))) {
                            return $this->getError('Ошибка парсинга опции: недопустимая опция');
                        }

                        if (sizeof($this->optionsCounter[$option]) && $this->optionsCounter[$option] > 0) {
                            return $this->getError('Ошибка парсинга опции: неверное число параметров для опции ' . $option);
                        }

                        $options[] = array(
                            'name' => $option,
                            'parameters' => array()
                        );
                    }
                }
            }
        }

        $this->status = 1;
        $this->comment = '';
        $this->data = array(
            'tag' => $operand,
            'options' => $options
        );

        return $this;
    }

    public function flush()
    {
        $this->data = array();
        $this->status = 0;
        $this->comment = '';
        $this->optionsCounter = null;
    }

    private function getError($message)
    {
        $this->status = 0;
        $this->comment = $message;
        $this->data = array();
        return $this;
    }
}