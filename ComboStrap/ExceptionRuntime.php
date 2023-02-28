<?php


namespace ComboStrap;


use Throwable;

/**
 * Class ExceptionCombo
 * @package ComboStrap
 * Adds the canonical
 *
 * An error that is in the code
 */
class ExceptionRuntime extends \RuntimeException
{
    /**
     * @var mixed|string
     */
    private $canonical;

    public function __construct($message = "", $canonical = "", $code = 0, Throwable $previous = null)
    {
        $this->canonical = $canonical;
        parent::__construct($message, $code, $previous);
    }


    public static function withError(Throwable $previous)
    {
         $calledClass = get_called_class();
         return new $calledClass($previous->getMessage(), "support", 1, $previous);
    }

    public static function withMessageAndError(string $message, Throwable $previous)
    {
        $calledClass = get_called_class();
        return new $calledClass($message, "support", 1, $previous);
    }

    /**
     * @return mixed|string
     */
    public function getCanonical()
    {
        return $this->canonical;
    }


}
