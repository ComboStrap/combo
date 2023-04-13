<?php


namespace ComboStrap;


/**
 * Class Template
 * @package ComboStrap
 * https://stackoverflow.com/questions/17869964/replacing-string-within-php-file
 */
class Template
{

    public const DOLLAR_VARIABLE_PREFIX = "$";

    public const DOLLAR_ESCAPE = '\\';

    public const CAPTURE_PATTERN_SHORT = Template::DOLLAR_ESCAPE . Template::DOLLAR_VARIABLE_PREFIX . self::VARIABLE_NAME_EXPRESSION;
    public const CAPTURE_PATTERN_LONG = Template::DOLLAR_ESCAPE . Template::LONG_PREFIX . "[^}\r\n]+" . self::LONG_EXIT;


    const CANONICAL = "variable-template";
    public const LONG_PREFIX = self::DOLLAR_VARIABLE_PREFIX . self::LONG_ENTER;
    public const LONG_EXIT = '}';
    const LONG_ENTER = '{';
    const VARIABLE_NAME_EXPRESSION = "[A-Za-z0-9_]+";
    const LONG_VARIABLE_NAME_CAPTURE_EXPRESSION = self::DOLLAR_ESCAPE . self::LONG_PREFIX . '\s*(' . self::VARIABLE_NAME_EXPRESSION . ')[^\r\n]*' . self::LONG_EXIT;


    protected string $_string;
    protected array $_data = array();

    public function __construct($string = null)
    {
        $this->_string = $string;
    }

    /**
     * @param $string
     * @return Template
     */
    public static function create($string): Template
    {
        return new Template($string);
    }

    public static function toValidVariableName(string $name)
    {
        return str_replace("-", "", $name);
    }

    public function setProperty($key, $value): Template
    {
        $this->_data[$key] = $value;
        return $this;
    }

    public function render(): string
    {

        $pattern = '/' .
            '(' . self::DOLLAR_ESCAPE . self::DOLLAR_VARIABLE_PREFIX . self::VARIABLE_NAME_EXPRESSION . ')' .
            '|' .
            '(' . self::DOLLAR_ESCAPE . self::LONG_PREFIX . '\s*' . self::VARIABLE_NAME_EXPRESSION . '[^\r\n]*' . self::LONG_EXIT . ')' .
            '/im';
        $splits = preg_split($pattern, $this->_string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $rendered = "";
        foreach ($splits as $part) {
            if (substr($part, 0, 1) === self::DOLLAR_VARIABLE_PREFIX) {
                if (substr($part, 1, 1) === self::LONG_ENTER) {
                    $matches = [];
                    preg_match('/' . self::LONG_VARIABLE_NAME_CAPTURE_EXPRESSION . '/im', $part, $matches);
                    $variable = $matches[1];
                } else {
                    $variable = trim(substr($part, 1));
                }
                if (isset($this->_data[$variable])) {
                    $value = $this->_data[$variable];
                } else {
                    LogUtility::warning("The variable ($variable) was not found in the data and has not been replaced", self::CANONICAL);
                    $value = $variable;
                }
            } else {
                $value = $part;
            }
            $rendered .= $value;
        }
        return $rendered;

    }

    /**
     *
     * @return false|string
     * @deprecated Just for demo, don't use because the input is not validated
     *
     */
    public function renderViaEval()
    {
        extract($this->_data);
        ob_start();
        eval("echo $this->_string ;");
        return ob_get_clean();
    }

    /**
     * @return array - an array of variable name
     */
    public function getVariablesDetected(): array
    {
        /** @noinspection RegExpUnnecessaryNonCapturingGroup */
        $pattern = '/' .
            '(?:' . self::DOLLAR_ESCAPE . self::DOLLAR_VARIABLE_PREFIX . '(' . self::VARIABLE_NAME_EXPRESSION . '))' .
            '|' .
            '(?:' . self::LONG_VARIABLE_NAME_CAPTURE_EXPRESSION . ')' .
            '/im';
        $result = preg_match_all($pattern, $this->_string, $matches);
        if ($result >= 1) {
            $returnedMatch = [];
            $firstExpressionMatches = $matches[1];
            $secondExpressionMatches = $matches[2];
            foreach ($firstExpressionMatches as $key => $match) {
                if (empty($match)) {
                    $returnedMatch[] = $secondExpressionMatches[$key];
                    continue;
                }
                $returnedMatch[] = $match;
            }
            return $returnedMatch;
        } else {
            return [];
        }

    }

    public function setProperties(array $properties): Template
    {
        foreach ($properties as $key => $val) {
            $this->setProperty($key, $val);
        }
        return $this;

    }

}
