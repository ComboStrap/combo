<?php

namespace ComboStrap;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;

class Snapshot
{
    const ENDPOINT = 'http://localhost:4444/';

    /**
     * @throws ExceptionNotFound
     * @throws \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    static public function snapshot(Path $path): LocalPath
    {

        $capabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments(['--headless']);
        $options->addArguments(["window-size=1024,768"]);

        /**
         *
         * https://docs.travis-ci.com/user/chrome#sandboxing
         * For security reasons, Google Chrome is unable to provide sandboxing when it is running in the container-based environment.
         * Error:
         *  * unknown error: Chrome failed to start: crashed
         *  * The SUID sandbox helper binary was found, but is not configured correctly
         */
        if (PluginUtility::isCi()) {
            $options->addArguments(["--no-sandbox"]);
        }

//      $options->addArguments(['--start-fullscreen']);
//      $options->addArguments(['--start-maximized']);

        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $webDriver = RemoteWebDriver::create(
            self::ENDPOINT,
            $capabilities,
            1000
        );
        try {

            // navigate to the page
            $webDriver->get($path->toUriString());

            // wait until the target page is loaded
            // https://github.com/php-webdriver/php-webdriver/wiki/HowTo-Wait
            $webDriver->wait(2, 500)->until(
                function () use ($webDriver) {
                    $state = $webDriver->executeScript("return document.readyState");
                    return $state === "complete";
                },
                'The page was not loaded'
            );

            /**
             * Scroll to the end to download the image
             */
            $body = $webDriver->findElement(WebDriverBy::tagName('body'));
            $webDriver->executeScript("window.scrollTo(0, document.body.scrollHeight)");
            // Scrolling by sending keys does not work to download lazy loaded image
            // $body->sendKeys(WebDriverKeys::encode([WebDriverKeys::CONTROL, WebDriverKeys::END]));
            // Let the time to the image to download, we could also scroll to each image and get the status ?
            $webDriver->wait(2, 500)->until(
                function () use ($webDriver) {
                    $images = $webDriver->findElements(WebDriverBy::tagName("img"));
                    foreach ($images as $img) {
                        $complete = DataType::toBoolean($img->getAttribute("complete"), false);
                        if ($complete === true) {
                            $naturalHeight = DataType::toInteger($img->getAttribute("naturalHeight"), 0);
                            if ($naturalHeight !== 0)
                                return false;
                        }
                    }
                    return true;
                },
                'The image were not loaded on time'
            );

            /**
             * Get the new dimension
             */
            $bodyOffsetHeight = $body->getDomProperty("offsetHeight");
            $bodyOffsetWidth = $body->getDomProperty("offsetWidth");

            /**
             * Because each page has a different height if you want
             * to take a full height and width, you need to set it manually after
             * the DOM has rendered
             */
            $heightCorrection = 15; // don't know why but yeah
            $fullPageDimension = new WebDriverDimension($bodyOffsetWidth, $bodyOffsetHeight + $heightCorrection);
            $webDriver->manage()
                ->window()
                ->setSize($fullPageDimension);

            $lastNameWithoutExtension = $path->getLastNameWithoutExtension();
            if (empty($lastNameWithoutExtension)) {
                $lastNameWithoutExtension = $path->getHost();
            }
            $screenShotPath = LocalPath::createHomeDirectory()->resolve("Desktop")->resolve($lastNameWithoutExtension . ".png");
            $webDriver->takeScreenshot($screenShotPath);
            return $screenShotPath;

        } finally {
            /**
             * terminate the session and close the browser
             */
            $webDriver->quit();
        }

    }
}
