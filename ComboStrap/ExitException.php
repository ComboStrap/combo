<?php


namespace ComboStrap;



/**
 * Class RuntimeException
 * @package ComboStrap
 *
 * An exception thrown during test when the
 * we exit
 *
 * We can then test
 * if we receive the wanted exception
 */
class ExitException extends \RuntimeException {
}
