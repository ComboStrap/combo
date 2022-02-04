<?php


namespace ComboStrap;
use Psr\Log\LogLevel;

/**
 * Class Template
 * @package ComboStrap
 * https://stackoverflow.com/questions/17869964/replacing-string-within-php-file
 */
class Template
{

    const VARIABLE_PREFIX = "$";
    const VARIABLE_PATTERN_CAPTURE_VARIABLE = "/(\\" . self::VARIABLE_PREFIX . "[\w]*)/im";
    const VARIABLE_PATTERN_CAPTURE_NAME = "/\\" . self::VARIABLE_PREFIX . "([\w]*)/im";
    const CANONICAL = "template";

    protected $_string;
    protected $_data = array();

    public function __construct($string = null)
    {
        $this->_string = $string;
    }

    /**
     * @param $string
     * @return Template
     */
    public static function create($string)
    {
        return new Template($string);
    }

    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    public function render(): string
    {


        $variablePattern = self::VARIABLE_PATTERN_CAPTURE_VARIABLE;
        $splits = preg_split($variablePattern, $this->_string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $rendered = "";
        foreach ($splits as $part) {
            if (substr($part, 0, 1) === self::VARIABLE_PREFIX) {
                $variable = trim(substr($part, 1));
                if(isset($this->_data[$variable])) {
                    $value = $this->_data[$variable];
                } else  {
                    LogUtility::msg("The variable ($variable) was not found in the data and has not been replaced", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
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
        $result = preg_match_all(self::VARIABLE_PATTERN_CAPTURE_NAME, $this->_string, $matches);
        if ($result >= 1) {
            return $matches[1];
        } else {
            return [];
        }


    }
}
