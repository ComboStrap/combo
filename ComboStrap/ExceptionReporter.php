<?php

namespace ComboStrap;

class ExceptionReporter
{

    private \Exception $e;

    public function __construct(\Exception $e)
    {
        $this->e = $e;
    }


    public static function createForException(\Exception $e): ExceptionReporter
    {
        return new ExceptionReporter($e);
    }

    public function getExceptionTraceAsHtml()
    {
        return str_replace("\n", "<br/>", $this->e->getTraceAsString());
    }

    public function getHtmlPage($reporterMessage): string
    {

        if (Identity::isManager()) {
            $errorMessage = $this->e->getMessage();
            $errorTrace = $this->getExceptionTraceAsHtml();
            $errorHtml = <<<EOF
<br/>
<p>Error (only seen by manager):</p>
<p>$errorMessage</p>
<p>$errorTrace</p>
EOF;
        } else {
            $errorHtml = "<br/><p>The error was logged in the log file. Errors are only visible by managers</p>";
        }
        return <<<EOF
<html lang="en">
<head>
<title>Error</title>
</head>
<body>
<h1>An error has occurred</h1>
<p>$reporterMessage</p>
$errorHtml
</body>
EOF;
    }
}
