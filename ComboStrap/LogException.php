<?php


namespace ComboStrap;



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
}
