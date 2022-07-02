<?php

namespace ComboStrap;

class ExceptionPhpError extends ExceptionRuntime
{


    private string $errorFile;
    private int $errorLine;

    public function setErrorFile($errorFile): ExceptionPhpError
    {
        $this->errorFile = $errorFile;
        return $this;
    }

    public function setErrorLine($errorLine): ExceptionPhpError
    {
        $this->errorLine = $errorLine;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorFile(): string
    {
        return $this->errorFile;
    }

    /**
     * @return int
     */
    public function getErrorLine(): int
    {
        return $this->errorLine;
    }
}
