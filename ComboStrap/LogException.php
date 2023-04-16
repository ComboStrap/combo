<?php


namespace ComboStrap;



use Throwable;

/**
 * Class RuntimeException
 * @package ComboStrap
 *
 * An exception thrown during test when the
 * {@link LogUtility} level is higher than warning
 *
 * We can then test
 * if we receive the wanted exception
 */
class LogException extends \RuntimeException {

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }


}
