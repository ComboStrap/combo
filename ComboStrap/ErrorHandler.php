<?php

namespace ComboStrap;

class ErrorHandler
{




    /**
     * @return void
     */
    public static function phpErrorAsException(): void
    {
        set_error_handler(function($errorNumber, $errorMessage, $errorFile, $errorLine) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }
            throw (
                (new ExceptionPhpError($errorMessage, 0, $errorNumber ))
                ->setErrorFile($errorFile)
                ->setErrorLine($errorLine)
            );

        });
    }


    static public function restore()
    {
        restore_error_handler();
    }
}
