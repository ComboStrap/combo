<?php


namespace ComboStrap;


use ArrayAccess;

/**
 * Class ArrayCaseInsensitive
 * @package ComboStrap
 *
 * Wrapper around an array to make it case access insensitive
 */
class ArrayCaseInsensitive implements ArrayAccess
{

    /**
     * @var array
     */
    private $_lowerCaseArray = array();
    /**
     * @var array
     */
    private $sourceArray;


    public function __construct(array &$source = array())
    {
        $this->sourceArray = &$source;
        array_walk($source, function ($value, &$key) {
            $this->_lowerCaseArray[strtolower($key)] = $value;
        });
    }

    public function offsetSet($offset, $value)
    {

        if (is_null($offset)) {
            $this->_lowerCaseArray[] = $value;
        } else {
            if (is_string($offset)) {
                $lowerCaseOffset = strtolower($offset);
                $this->_lowerCaseArray[$lowerCaseOffset] = $value;
                $this->sourceArray[$offset] = $value;
            } else {
                LogUtility::msg("The offset should be a string", LogUtility::LVL_MSG_ERROR);
            }

        }
    }

    public function offsetExists($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        return isset($this->_lowerCaseArray[$offset]);
    }

    public function offsetUnset($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        unset($this->_lowerCaseArray[$offset]);
    }

    public function offsetGet($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        return isset($this->_lowerCaseArray[$offset])
            ? $this->_lowerCaseArray[$offset]
            : null;
    }

    function getOriginalArray()
    {
        return $this->sourceArray;
    }
}
