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
     * A mapping between lower key and original key (ie offset)
     * @var array
     */
    private $_keyMapping = array();
    /**
     * @var array
     */
    private $sourceArray;


    public function __construct(array &$source = array())
    {
        $this->sourceArray = &$source;
        array_walk($source, function ($value, &$key) {
            $this->_keyMapping[strtolower($key)] = $key;
        });
    }

    public function offsetSet($offset, $value)
    {

        if (is_null($offset)) {
            LogUtility::msg("The offset (key) is null and this is not supported");
        } else {
            if (is_string($offset)) {
                $lowerCaseOffset = strtolower($offset);
                $this->_keyMapping[$lowerCaseOffset] = $offset;
                $this->sourceArray[$offset] = $value;
            } else {
                LogUtility::msg("The offset should be a string", LogUtility::LVL_MSG_ERROR);
            }

        }
    }

    public function offsetExists($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        return isset($this->_keyMapping[$offset]);
    }

    public function offsetUnset($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        $originalOffset = $this->_keyMapping[$offset];
        unset($this->sourceArray[$originalOffset]);
        unset($this->_keyMapping[$offset]);

    }

    public function offsetGet($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        $sourceOffset = $this->_keyMapping[$offset];
        return isset($this->sourceArray[$sourceOffset])
            ? $this->sourceArray[$sourceOffset]
            : null;
    }

    function getOriginalArray()
    {
        return $this->sourceArray;
    }
}
