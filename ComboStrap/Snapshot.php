<?php

namespace ComboStrap;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;

class Snapshot
{
    const ENDPOINT = 'http://localhost:4444/';

    /**
     * @throws ExceptionNotFound
     */
    static public function snapshot(Page $page = null)
    {

        $capabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $options->addArguments(['--headless']);
//        $options->addArguments(["window-size=750,450"]);
//        $options->addArguments(['--start-maximized']);
//        $options->addArguments(['--start-fullscreen']);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        $webDriver = RemoteWebDriver::create(
            self::ENDPOINT,
            $capabilities,
            1000
        );
        try {

            /**
             * Because each page has a different height if you want
             * to take a full height and width, you need to set it manually after
             * the DOM has rendered
             */
            $dem = new WebDriverDimension(300, 300);
            $webDriver->manage()
                ->window()
                ->setSize($dem);

            // navigate to the page
            //$localUri = LocalPath::createHomeDirectory()->resolve("Desktop")->resolve("test.html")->toUriString();
            $localUri = "https://datacadamia.com";
            $webDriver->get($localUri);

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
