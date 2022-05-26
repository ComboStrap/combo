<?php

namespace ComboStrap;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;

class Snapshot
{
    const ENDPOINT = 'http://localhost:4444/';

    /**
     * @throws ExceptionNotFound
     * @throws \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    static public function snapshot(Page $page = null)
    {

        $capabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments(['--headless']);
//        $options->addArguments(['--start-fullscreen']);
//        $options->addArguments(["window-size=750,450"]);
//        $options->addArguments(['--start-maximized']);

        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $webDriver = RemoteWebDriver::create(
            self::ENDPOINT,
            $capabilities,
            1000
        );
        try {


            // navigate to the page
            //$localUri = LocalPath::createHomeDirectory()->resolve("Desktop")->resolve("test.html")->toUriString();
            $localUri = "https://datacadamia.com";
            $webDriver->get($localUri);

            // wait until the target page is loaded
            // https://github.com/php-webdriver/php-webdriver/wiki/HowTo-Wait
            $webDriver->wait()->until(
                function () use ($webDriver) {
                    $state = $webDriver->executeScript("return document.readyState");
                    return $state === "complete";
                },
                'The page was not loaded'
            );

            $body = $webDriver->findElement(WebDriverBy::tagName('body'));
            $bodyOffsetHeight = $body->getDomProperty("offsetHeight");
            $bodyOffsetWidth = $body->getDomProperty("offsetWidth");

            /**
             * Because each page has a different height if you want
             * to take a full height and width, you need to set it manually after
             * the DOM has rendered
             */
            $heightCorrection = 15; // don't know why but yeah
            $fullPageDimension = new WebDriverDimension($bodyOffsetWidth, $bodyOffsetHeight+ $heightCorrection);
            $webDriver->manage()
                ->window()
                ->setSize($fullPageDimension);


            $localPath = LocalPath::createHomeDirectory()->resolve("Desktop")->resolve("screenshot.png");
            $webDriver->takeScreenshot($localPath);

        } finally {
            /**
             * terminate the session and close the browser
             */
            $webDriver->quit();
        }

    }
}
