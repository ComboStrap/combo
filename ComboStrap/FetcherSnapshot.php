<?php

namespace ComboStrap;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;

/**
 * Download chrome driver with the same version
 *
 * Then run:
 * ```
 * chromedriver.exe --port=4444
 * ```
 */
class FetcherSnapshot extends FetcherImage
{


    const WEB_DRIVER_ENDPOINT = 'http://localhost:4444/';
    const CANONICAL = "snapshot";
    const URL = "url";

    private Url $url;


    public static function createSnapshotFromUrl(Url $urlToSnapshot): FetcherSnapshot
    {
        return (new FetcherSnapshot())
            ->setUrlToSnapshot($urlToSnapshot);

    }

    function getFetchUrl(Url $url = null): Url
    {
        $url = parent::getFetchUrl($url);
        try {
            $url->addQueryParameter(self::URL, $this->getUrlToSnapshot());
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $url;
    }


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherSnapshot
    {
        $urlString = $tagAttributes->getValue(self::URL);
        if ($urlString === null) {
            throw new ExceptionBadArgument("The `url` property is mandatory");
        }
        $this->url = Url::createFromString($urlString);
        parent::buildFromTagAttributes($tagAttributes);
        return $this;
    }


    /**
     * @return LocalPath
     * @throws ExceptionNotFound
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws UnsupportedOperationException
     * @throws ExceptionInternal
     */
    function getFetchPath(): LocalPath
    {

        $url = $this->getUrlToSnapshot();

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

        try {
            $webDriver = RemoteWebDriver::create(
                self::WEB_DRIVER_ENDPOINT,
                $capabilities,
                1000
            );
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (WebDriverCurlException $e) {
            // this exception is thrown even if it's not advertised
            throw new ExceptionInternal("Web driver is not available at " . self::WEB_DRIVER_ENDPOINT . ". Error: {$e->getMessage()}");
        }
        try {

            // navigate to the page
            $webDriver->get($url->toAbsoluteUrlString());

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


            if (!PluginUtility::isDevOrTest()) {
                // Cache
                $screenShotPath = FetcherCache::createFrom($this)->getFile();
            } else {
                // Desktop
                try {
                    $lastNameWithoutExtension = $url->getLastNameWithoutExtension();
                } catch (ExceptionNotFound $e) {
                    $lastNameWithoutExtension = $url->getHost();
                }
                $screenShotPath = LocalPath::createHomeDirectory()
                    ->resolve("Desktop")
                    ->resolve($lastNameWithoutExtension . "." . $this->getMime()->getExtension());
            }
            $webDriver->takeScreenshot($screenShotPath);
            return $screenShotPath;

        } finally {
            /**
             * terminate the session and close the browser
             */
            $webDriver->quit();
        }

    }


    /**
     * @throws \ReflectionException
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster(ClassUtility::getClassPath(FetcherSnapshot::class));
    }

    public function getMime(): Mime
    {
        return Mime::createFromExtension("png");
    }

    public function getFetcherName(): string
    {
        return self::CANONICAL;
    }

    public function getIntrinsicWidth(): int
    {
        try {
            return $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
            return 1024;
        }
    }

    public function getIntrinsicHeight(): int
    {
        try {
            return $this->getRequestedHeight();
        } catch (ExceptionNotFound $e) {
            return 768;
        }
    }

    private function setUrlToSnapshot(Url $urlToSnapshot): FetcherSnapshot
    {
        $this->url = $urlToSnapshot;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getUrlToSnapshot(): Url
    {
        if (!isset($this->url)) {
            throw new ExceptionNotFound("No url to snapshot could be determined");
        }
        return $this->url;
    }
}
