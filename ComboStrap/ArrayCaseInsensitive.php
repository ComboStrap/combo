<?php


namespace ComboStrap;


use ArrayAccess;
use ArrayObject;
use Countable;

/**
 * Class ArrayCaseInsensitive
 * @package ComboStrap
 *
 * Wrapper around an array to make it case access insensitive
 */
class ArrayCaseInsensitive implements ArrayAccess, \Iterator, Countable
{

    /**
     * A mapping between lower key and original key (ie offset)
     * @var array
     */
    private array $_keyMapping = array();
    /**
     * @var array
     */
    private array $sourceArray;
    /**
     * @var false|mixed
     */
    private $valid;
    private int $iteratorIndex = 0;
    /**
     * @var \ArrayIterator
     */
    private \ArrayIterator $iterator;


    public function __construct(array &$source = array())
    {
        $this->sourceArray = &$source;
        array_walk($source, function ($value, $key) {
            $this->_keyMapping[strtolower($key)] = $key;
        });

        /**
         * Iterator
         */
        $this->rewind();
    }

    public function offsetSet($offset, $value): void
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

    public function offsetExists($offset): bool
    {
        if (is_string($offset)) $offset = strtolower($offset);
        return isset($this->_keyMapping[$offset]);
    }

    public function offsetUnset($offset): void
    {

        if (is_string($offset)) $offset = strtolower($offset);
        $originalOffset = $this->_keyMapping[$offset] ?? null;
        unset($this->sourceArray[$originalOffset]);
        unset($this->_keyMapping[$offset]);

    }


    public function offsetGet($offset)
    {
        if (is_string($offset)) $offset = strtolower($offset);
        $sourceOffset = $this->_keyMapping[$offset] ?? null;
        if ($sourceOffset === null) {
            return null;
        }
        return $this->sourceArray[$sourceOffset] ?? null;
    }

    function getOriginalArray(): array
    {
        return $this->sourceArray;
    }


    public function current()
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $obj = new ArrayObject($this->sourceArray);
        $this->iterator = $obj->getIterator();
    }

    public function count(): int
    {
        return sizeof($this->sourceArray);
    }
}
