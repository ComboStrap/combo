<?php

namespace ComboStrap;


use Exception;
use syntax_plugin_combo_container;

class FetcherPage extends FetcherAbs implements FetcherSource
{

    use FetcherTraitLocalPath;

    const CANONICAL = "page";

    const PAGE_CORE_AREA = "page-core";
    const PAGE_SIDE_AREA = "page-side";
    const PAGE_HEADER_AREA = "page-header";
    const PAGE_FOOTER_AREA = "page-footer";
    const PAGE_MAIN_AREA = "page-main";
    const MAIN_SIDE_AREA = "main-side";
    const MAIN_CONTENT_AREA = "main-content";
    const MAIN_HEADER_AREA = "main-header";
    const MAIN_FOOTER_AREA = "main-footer";
    const AREAS = [
        self::PAGE_CORE_AREA,
        self::PAGE_SIDE_AREA,
        self::PAGE_HEADER_AREA,
        self::PAGE_MAIN_AREA,
        self::PAGE_FOOTER_AREA,
        self::MAIN_HEADER_AREA,
        self::MAIN_CONTENT_AREA,
        self::MAIN_SIDE_AREA,
        self::MAIN_FOOTER_AREA,
    ];


    public static function createPageFetcherFromObject(PageFragment $param): FetcherPage
    {
        $fetcherPage = new FetcherPage();
        $fetcherPage->setOriginalPath($param->getPath());
        return $fetcherPage;
    }

    /**
     *
     */
    public function getFetchPath(): LocalPath
    {

        /**
         * We first collect the depend file to
         * check if we can use the cache
         */
        $requestedPage = PageFragment::createPageFromPathObject($this->getOriginalPath());
        $layoutName = PageLayout::createFromPage($requestedPage)->getValueOrDefault();

        $layoutDirectory = WikiPath::createWikiPath(":layout:$layoutName:", WikiPath::COMBO_DRIVE);
        if (!FileSystems::exists($layoutDirectory)) {
            throw new ExceptionNotFound("The layout directory ($layoutName) does not exist at $layoutDirectory", self::CANONICAL);
        }
        $layoutCssPath = $layoutDirectory->resolve("$layoutName.css");
        $layoutJsPath = $layoutDirectory->resolve("$layoutName.js");
        $bodyLayoutHtmlPath = $layoutDirectory->resolve("$layoutName.html");
        $layoutJsonPath = $layoutDirectory->resolve("$layoutName.json");

        $cache = FetcherCache::createFrom($this)
            ->addFileDependency($layoutCssPath)
            ->addFileDependency($layoutJsPath)
            ->addFileDependency($bodyLayoutHtmlPath)
            ->addFileDependency($layoutJsonPath);

        $htmlBodyDomElement = $this->htmlTemplatePathToHtmlDom($bodyLayoutHtmlPath);

        $pageAreas = $this->buildAndGetAreas($htmlBodyDomElement);
        foreach ($pageAreas as $pageArea) {
            try {
                $path = $pageArea->getFragmentPath();
                $cache->addFileDependency($path);
            } catch (ExceptionNotFound $e) {
                // no area path to render found
            }
        }

        /**
         * Do we create the page or return
         */
        if ($cache->isCacheUsable()) {
            return $cache->getFile();
        }


        global $ACT;
        switch ($ACT) {
            case "preview": // edit preview
            case "edit": // edit
            case "admin": // admin page
            case "media": // media manager
                /**
                 * Note: the secondary slot will not render because the act is not show
                 * This is for now not used by strap
                 */
                $layoutName = "hamburger";
                break;
            case "show":
                $layoutName = PageLayout::createFromPage($requestedPage)
                    ->getValueOrDefault();
                break;
            default:
            case "login": // login
            case "resendpwd": // passwd resend
            case "register": // register form
            case "profile": // profile form
            case "search": // search
            case "recent": // the revisions for the website
            case "index": // the website index
            case "diff": // diff between revisions
            case "revisions": // Known as old revisions (old version of the page)
                $layoutName = "median";
                break;
        }


        /**
         * Css and Js
         */
        $snippetManager = PluginUtility::getSnippetManager();
        try {
            $content = FileSystems::getContent($layoutCssPath);
            $snippetManager->attachCssInternalStylesheetForRequest(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // not a problem
        }

        try {
            $content = FileSystems::getContent($layoutJsPath);
            $snippetManager->attachJavascriptInternalForRequest(self::CANONICAL, $content);
        } catch (ExceptionNotFound $e) {
            // not a problem
        }


        /**
         * Body
         * {@link tpl_classes} will add the dokuwiki class.
         * See https://www.dokuwiki.org/devel:templates#dokuwiki_class
         * dokuwiki__top ID is needed for the "Back to top" utility
         * used also by some plugins
         */
        $tplClasses = tpl_classes();
        $bodyPositionRelativeClass = "position-relative"; // for absolutely positioning at the left corner of the viewport (message, tool, ...)
        try {
            $htmlBodyDomElement->querySelector("body")->addClass("$tplClasses {$bodyPositionRelativeClass}");
        } catch (ExceptionBadSyntax|ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The template ($bodyLayoutHtmlPath) does not have a body element");
        }


        /**
         * Area
         */
        $jsonConfigurations = $this->getJsonConfigurations($layoutJsonPath);

        $htmlOutputByAreaName = [];
        foreach ($pageAreas as $pageLayoutElement) {

            $areaDomElement = $pageLayoutElement->getDomElement();

            $areaName = $pageLayoutElement->getId();
            $attributes = $jsonConfigurations[$areaName];
            $jsonConfiguration = TagAttributes::createFromCallStackArray($attributes);

            // Container
            if ($areaName === self::PAGE_CORE_AREA) {
                // Page Header and Footer have a bar that permits to set the container
                // Page core does not have any
                // It's by default contained for all layout
                $container = $jsonConfiguration->getValueAndRemoveIfPresent("container", true);
                if ($container) {
                    $container = PluginUtility::getConfValue(syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_CONF, syntax_plugin_combo_container::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE);
                    $areaDomElement->addClass(syntax_plugin_combo_container::getClassName($container));
                }
            }

            /**
             * Special Classes and attributes
             */
            // relative
            // Relative positioning is important for the positioning of the pagetools (page-core),
            // edit button, ...
            $areaDomElement->addClass("position-relative");
            switch ($areaName) {
                case self::PAGE_FOOTER_AREA:
                case self::PAGE_HEADER_AREA:
                    // no print
                    $areaDomElement->addClass("d-print-none");
                    break;
                case self::PAGE_CORE_AREA:
                    $areaDomElement->addClass("layout-$layoutName-combo");
                    break;
                case self::MAIN_FOOTER_AREA:
                case self::PAGE_SIDE_AREA:
                case self::MAIN_SIDE_AREA:
                    $areaDomElement->setAttribute("role", "complementary");
                    $areaDomElement->addClass("d-print-none");
                    break;
            }

            /**
             * Rendering
             */
            if (!$pageLayoutElement->isSlot()) {
                // no rendering for container area, this is a parent
                continue;
            }

            $layoutVariable = $pageLayoutElement->getVariableName();

            try {
                $wikiPath = $pageLayoutElement->getFragmentPath();
            } catch (ExceptionNotFound $e) {
                // no fragment (page side for instance)
                // remove or empty ?
                $areaDomElement->remove();
                continue;
            }
            $htmlOutputByAreaName[$layoutVariable] = FetcherPageFragment::createPageFragmentFetcherFromPath($wikiPath)
                ->getFetchPathAsHtmlString();

            /**
             * Add the template variable
             */
            $areaDomElement->appendTextNode('$' . $layoutVariable);

        }

        if (sizeof($htmlOutputByAreaName) === 0) {
            LogUtility::internalError("No slot was rendered");
        }

        $htmlBodyDocumentString = $htmlBodyDomElement->toHtml();
        $finalHtmlBodyString = Template::create($htmlBodyDocumentString)->setProperties($htmlOutputByAreaName)->render();

        $cache->storeCache($finalHtmlBodyString);

        return $cache->getFile();

    }

    function getFetchPathAsHtmlString(): string
    {

        /**
         * TODO: add return {@link TplUtility::printMessage()}
         */
        return FileSystems::getContent($this->getFetchPath());


    }

    /**
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->getOriginalPath());
    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::HTML);
    }

    public function getFetcherName(): string
    {
        return self::CANONICAL;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getFetchPathAsHtmlDom(): XmlDocument
    {
        $content = $this->getFetchPathAsHtmlString();
        return XmlDocument::createXmlDocFromMarkup($content);
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    private function htmlTemplatePathToHtmlDom(WikiPath $layoutHtmlPath): XmlDocument
    {
        try {
            $bodyHtmlStringLayout = FileSystems::getContent($layoutHtmlPath);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("The layout file ($layoutHtmlPath) does not exist at $layoutHtmlPath", self::CANONICAL);
        }
        try {
            return XmlDocument::createHtmlDocFromMarkup("<body>$bodyHtmlStringLayout</body>");
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The html template file ($layoutHtmlPath) is not valid. Error: {$e->getMessage()}", self::CANONICAL);
        }
    }

    /**
     * @param XmlDocument $htmlBodyDomElement
     * @return PageElement[]
     */
    private function buildAndGetAreas(XmlDocument $htmlBodyDomElement): array
    {

        $areas = [];
        foreach (self::AREAS as $areaName) {

            /**
             * If the id is not in the html template we don't show it
             */
            try {
                $domElement = $htmlBodyDomElement->querySelector("#$areaName");
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("The selector should not have a bad syntax");
                continue;
            } catch (ExceptionNotFound $e) {
                continue;
            }

            $areas[] = new PageElement($domElement, $this);

        }
        return $areas;

    }

    public function getRequestedPath(): WikiPath
    {
        return $this->getOriginalPath();
    }

    private function getJsonConfigurations($layoutJsonPath): array
    {
        try {
            $jsonString = FileSystems::getContent($layoutJsonPath);
        } catch (ExceptionNotFound $e) {
            // The layout file ($layoutJsonPath) does not exist at $layoutJsonPath", self::CANONICAL, 1, $e);
            return [];
        }
        try {
            $json = Json::createFromString($jsonString);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionRuntimeInternal("The layout file ($layoutJsonPath) could not be loaded as json. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        }
        return $json->toArray();
    }


}
