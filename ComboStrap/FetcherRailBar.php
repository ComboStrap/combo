<?php

namespace ComboStrap;

use dokuwiki\Menu\PageMenu;
use dokuwiki\Menu\SiteMenu;
use dokuwiki\Menu\UserMenu;

/**
 * A fetcher for a menu rail bar
 * https://material.io/components/navigation-rail
 *
 *
 * Note: this class is a fetcher but it does not still work to call it via a javascript function included in the page.
 * Why ? The problem is that plugins that add a item would expect
 * to be loaded with the page and the related javascript is generally wrapped in a listener waiting for the page load event.
 * It means that it would never be triggered.
 *
 */
class FetcherRailBar extends IFetcherAbs implements IFetcherString
{

    use FetcherTraitWikiPath;

    const CANONICAL = self::NAME;
    const NAME = "railbar";
    const FIXED_LAYOUT = "fixed";
    const OFFCANVAS_LAYOUT = "offcanvas";
    const VIEWPORT_WIDTH = "viewport";
    const LAYOUT_ATTRIBUTE = "layout";
    /**
     * Do we show the rail bar for anonymous user
     */
    public const CONF_PRIVATE_RAIL_BAR = "privateRailbar";
    /**
     * When do we toggle from offcanvas to fixed railbar
     */
    public const CONF_BREAKPOINT_RAIL_BAR = "breakpointRailbar";
    const BOTH_LAYOUT = "all_layout";


    private int $requestedViewPort;
    private string $requestedLayout;


    public static function createRailBar(): FetcherRailBar
    {
        return new FetcherRailBar();
    }

    private static function getComponentClass(): string
    {
        return StyleUtility::addComboStrapSuffix(self::CANONICAL);
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher
    {
        /**
         * Capture the id
         */
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        /**
         * Capture the view port
         */
        $viewPortWidth = $tagAttributes->getValueAndRemoveIfPresent(self::VIEWPORT_WIDTH);
        if ($viewPortWidth !== null) {
            try {
                $this->setRequestedViewPort(DataType::toInteger($viewPortWidth));
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The viewport width is not a valid integer. Error:{$e->getMessage()}", self::CANONICAL);
            }
        }
        /**
         * Capture the layout
         */
        $layout = $tagAttributes->getValueAndRemoveIfPresent(self::LAYOUT_ATTRIBUTE);
        if ($layout !== null) {
            try {
                $this->setRequestedLayout($layout);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The layout is not a valid. Error:{$e->getMessage()}", self::CANONICAL);
            }
        }
        return parent::buildFromTagAttributes($tagAttributes);
    }


    function getFetchPath(): Path
    {
        throw new ExceptionRuntimeInternal("No fetch path: Railbar is not a file but a dynamic HTML document");
    }

    function getFetchString(): string
    {

        if (!$this->shouldBePrinted()) {
            return "";
        }

        $localWikiRequest = null;
        $localWikiId = null;
        try {
            ExecutionContext::getActualContext();
        } catch (ExceptionNotFound $e) {

            /**
             * No actual request (called via ajax)
             */
            $localWikiId = $this->getSourcePath()->getWikiId();
            $localWikiRequest = ExecutionContext::createFromWikiId($localWikiId);

            /**
             * page info is needed and used by all other plugins
             * in all hooks (should be first)
             */
            global $INFO;
            $INFO = pageinfo();

            /**
             * Uses by {@link action_plugin_move_rename} to set
             * if it will add the button
             */
            $tmp = array();
            \dokuwiki\Extension\Event::createAndTrigger('DOKUWIKI_STARTED', $tmp);

        }


        try {

            $snippetManager = SnippetManager::getOrCreate();
            $railBarHtmlListItems = $this->getRailBarHtmlListItems();
            $railBarLayout = $this->getLayoutTypeToApply();
            switch ($railBarLayout) {
                case self::FIXED_LAYOUT:
                    $railBar = $this->toFixedLayout($railBarHtmlListItems);
                    $snippetManager->attachCssInternalStylesheetForRequest("railbar-$railBarLayout");
                    break;
                case self::OFFCANVAS_LAYOUT:
                    $railBar = $this->toOffCanvasLayout($railBarHtmlListItems);
                    $snippetManager->attachCssInternalStylesheetForRequest("railbar-$railBarLayout");
                    break;
                case self::BOTH_LAYOUT:
                default:
                    $snippetManager->attachCssInternalStylesheetForRequest("railbar-" . self::FIXED_LAYOUT);
                    $snippetManager->attachCssInternalStylesheetForRequest("railbar-" . self::OFFCANVAS_LAYOUT);
                    $breakpoint = $this->getBreakPointConfiguration();
                    $railBar = $this->toFixedLayout($railBarHtmlListItems, $breakpoint)
                        . $this->toOffCanvasLayout($railBarHtmlListItems, $breakpoint);
                    break;
            }


            $snippetManager->attachCssInternalStylesheetForRequest("railbar");

            if ($localWikiRequest !== null) {
                $snippets = $snippetManager->toHtmlForAllSnippets();
                $snippetClass = self::getSnippetClass();
                /**
                 * Snippets should be after the html because they works
                 * on the added HTML
                 */
                $railBar = <<<EOF
$railBar
<div id="$snippetClass" class="$snippetClass">
$snippets
</div>
EOF;
            }

            return $railBar;


        } finally {
            if ($localWikiRequest !== null) {
                $localWikiRequest->close($localWikiId);
            }
        }

    }

    function getBuster(): string
    {
        return "";
    }

    public function getMime(): Mime
    {
        return Mime::getHtml();
    }

    public function getFetcherName(): string
    {
        return self::NAME;
    }

    public function setRequestedPageWikiId(string $wikiId): FetcherRailBar
    {
        $path = WikiPath::createPagePathFromId($wikiId);
        return $this->setRequestedPath($path);
    }

    public static function getSnippetClass(): string
    {
        return Snippet::getClassFromSnippetId(self::CANONICAL);
    }

    private function getRailBarHtmlListItems(): string
    {
        $liUserTools = (new UserMenu())->getListItems('action');
        $pageMenu = new PageMenu();
        $liPageTools = $pageMenu->getListItems();
        $liSiteTools = (new SiteMenu())->getListItems('action');
        // FYI: The below code outputs all menu in mobile (in another HTML layout)
        // echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']);
        $componentClass = self::getComponentClass();
        return <<<EOF
<ul class="$componentClass">
    <li><a href="#" style="height: 19px;line-height: 17px;text-align: left;font-weight:bold"><span>User</span><svg style="height:19px"></svg></a></li>
    $liUserTools
    <li><a href="#" style="height: 19px;line-height: 17px;text-align: left;font-weight:bold"><span>Page</span><svg style="height:19px"></svg></a></li>
    $liPageTools
    <li><a href="#" style="height: 19px;line-height: 17px;text-align: left;font-weight:bold"><span>Website</span><svg style="height:19px"></svg></a></li>
    $liSiteTools
</ul>
EOF;

    }

    private function toOffCanvasLayout(string $railBarHtmlListItems, Breakpoint $hideFromBreakpoint = null): string
    {
        $breakpointHiding = "";
        if ($hideFromBreakpoint !== null) {
            $breakpointHiding = "d-{$hideFromBreakpoint->getShortName()}-none";
        }
        $railBarOffCanvasPrefix = "railbar-offcanvas";
        $railBarClass = StyleUtility::addComboStrapSuffix(self::NAME);
        $railBarOffCanvasClassAndId = StyleUtility::addComboStrapSuffix($railBarOffCanvasPrefix);
        $railBarOffCanvasWrapperId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-wrapper");
        $railBarOffCanvasLabelId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-label");
        $railBarOffcanvasBodyId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-body");
        $railBarOffCanvasCloseId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-close");
        $railBarOffCanvasOpenId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-open");
        return <<<EOF
<div id="$railBarOffCanvasWrapperId" class="$railBarClass $railBarOffCanvasClassAndId $breakpointHiding">
    <button id="$railBarOffCanvasOpenId" class="btn" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#$railBarOffCanvasClassAndId" aria-controls="railbar-offcanvas">
    </button>

    <div id="$railBarOffCanvasClassAndId" class="offcanvas offcanvas-end" tabindex="-1" aria-labelledby="$railBarOffCanvasLabelId"
         style="visibility: hidden;" aria-hidden="true">
         <h5 class="d-none" id="$railBarOffCanvasLabelId">Railbar</h5>
        <!-- Pseudo relative element  https://stackoverflow.com/questions/6040005/relatively-position-an-element-without-it-taking-up-space-in-document-flow -->
        <div style="position: relative; width: 0; height: 0">
            <button id="$railBarOffCanvasCloseId" class="btn" type="button" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div id="$railBarOffcanvasBodyId" class="offcanvas-body" style="align-items: center;display: flex;">
            $railBarHtmlListItems
        </div>
    </div>
</div>
EOF;

    }

    public function getLayoutTypeToApply(): string
    {

        if (isset($this->requestedLayout)) {
            return $this->requestedLayout;
        }
        $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
        if ($bootstrapVersion === Bootstrap::BootStrapFourMajorVersion) {
            return self::FIXED_LAYOUT;
        }
        try {
            $breakPointConfigurationInPixel = $this->getBreakPointConfiguration()->getWidth();
        } catch (ExceptionInfinite $e) {
            // no breakpoint
            return self::OFFCANVAS_LAYOUT;
        }

        try {
            if ($this->getRequestedViewPort() > $breakPointConfigurationInPixel) {
                return self::FIXED_LAYOUT;
            } else {
                return self::OFFCANVAS_LAYOUT;
            }
        } catch (ExceptionNotFound $e) {
            // no known target view port
            // we send them both then
            return self::BOTH_LAYOUT;
        }

    }

    public function setRequestedViewPort(int $viewPort): FetcherRailBar
    {
        $this->requestedViewPort = $viewPort;
        return $this;
    }

    /**
     * The call may indicate the view port that the railbar will be used for
     * (ie breakpoint)
     * @return int
     * @throws ExceptionNotFound
     */
    public function getRequestedViewPort(): int
    {
        if (!isset($this->requestedViewPort)) {
            throw new ExceptionNotFound("No requested view port");
        }
        return $this->requestedViewPort;
    }

    private function shouldBePrinted(): bool
    {

        if (
            PluginUtility::getConfValue(self::CONF_PRIVATE_RAIL_BAR, 0) === 1
            && !Identity::isLoggedIn()
        ) {
            return false;
        }
        return true;

    }

    private function getBreakPointConfiguration(): Breakpoint
    {
        $name = PluginUtility::getConfValue(self::CONF_BREAKPOINT_RAIL_BAR, Breakpoint::BREAKPOINT_LARGE_NAME);
        return Breakpoint::createFromLongName($name);
    }


    /**
     * @param string $railBarHtmlListItems
     * @param Breakpoint|null $showFromBreakpoint
     * @return string
     */
    private function toFixedLayout(string $railBarHtmlListItems, Breakpoint $showFromBreakpoint = null): string
    {
        $showFromBreakpointClasses = "";
        if ($showFromBreakpoint !== null) {
            $showFromBreakpointClasses = "d-none d-{$showFromBreakpoint->getShortName()}-flex";
        }
        $railBarClass = StyleUtility::addComboStrapSuffix(self::NAME);
        $railBarFixedClassOrId = StyleUtility::addComboStrapSuffix(self::NAME . "-fixed");
        $zIndexRailbar = 1000; // A navigation bar (below the drop down because we use it in the search box for auto-completion)
        return <<<EOF
<div id="$railBarFixedClassOrId" class="$railBarClass $railBarFixedClassOrId d-flex $showFromBreakpointClasses" style="z-index: $zIndexRailbar;">
    <div>
        $railBarHtmlListItems
    </div>
</div>
EOF;

    }

    /**
     * The layout may be requested (example in a landing page where you don't want to see it)
     * @param string $layout
     * @return FetcherRailBar
     * @throws ExceptionBadArgument
     */
    public function setRequestedLayout(string $layout): FetcherRailBar
    {
        if (!in_array($layout, [self::FIXED_LAYOUT, self::OFFCANVAS_LAYOUT])) {
            throw new ExceptionBadArgument("The layout ($layout) is not valid");
        }
        $this->requestedLayout = $layout;
        return $this;
    }

    public function setRequestedPath(WikiPath $requestedPath): FetcherRailBar
    {
        $this->setSourcePath($requestedPath);
        return $this;
    }


}
