<?php

namespace ComboStrap;

use dokuwiki\Menu\PageMenu;
use dokuwiki\Menu\SiteMenu;
use dokuwiki\Menu\UserMenu;

/**
 * A fetcher for the menu rail bar
 *
 * The idea was to add Javascript in the generated page
 * that would call this fetcher.
 *
 * The problem is that it's difficult for now to share the code with the template
 * For now, there is a comment inserted that is replaced at runtime on strap
 *
 * And this code is just FYI
 *
 * https://material.io/components/navigation-rail|Navigation rail
 */
class FetcherRailBar extends IFetcherAbs implements IFetcherString
{

    use FetcherTraitLocalPath;

    const CANONICAL = "railbar";
    const FIXED_LAYOUT = "fixed";
    const OFFCANVAS_LAYOUT = "offcanvas";
    const VIEWPORT_WIDTH = "viewport";
    const LAYOUT_ATTRIBUTE = "layout";

    private int $requestedViewPort = 1000;
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
        if ($this->notPrinted()) {
            return "";
        }

        $wikiRequest = WikiRequestEnvironment::createAndCaptureState()
            ->setNewRunningId($this->getOriginalPath()->getWikiId())
            ->setNewRequestedId($this->getOriginalPath()->getWikiId())
            ->setNewAct("show");

        try {
            $railBarHtmlListItems = $this->getRailBarHtmlListItems();
            $railBarLayout = $this->getLayout();
            switch ($railBarLayout) {
                case self::FIXED_LAYOUT:
                    $railBar = $this->toFixedLayout($railBarHtmlListItems);
                    break;
                case self::OFFCANVAS_LAYOUT:
                    $railBar = $this->toOffCanvasLayout($railBarHtmlListItems);
                    break;
            }

            $snippetManager = SnippetManager::getOrCreate();
            $snippetManager->attachCssInternalStylesheetForRequest("railbar");
            $snippetManager->attachCssInternalStylesheetForRequest("railbar-" . $this->getLayout());

            $snippets = $snippetManager->toHtmlForAllSnippets();
            $snippetClass = self::getSnippetClass();
            return <<<EOF
<div id="$snippetClass" class="$snippetClass">
$snippets
</div>
$railBar
EOF;
        } finally {
            $wikiRequest->restoreState();
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
        return self::CANONICAL;
    }

    public function setRequestedPageWikiId(string $wikiId)
    {
        $this->path = WikiPath::createPagePathFromId($wikiId);
        return $this;
    }

    public static function getSnippetClass(): string
    {
        return SnippetManager::getClassFromSnippetId(self::CANONICAL);
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

    private function toOffCanvasLayout(string $railBarHtmlListItems): string
    {
        $railBarOffCanvasPrefix = "railbar-offcanvas";
        $railBarOffCanvasId = StyleUtility::addComboStrapSuffix($railBarOffCanvasPrefix);
        $railBarOffCanvasWrapperId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-wrapper");
        $railBarOffCanvasLabelId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-label");
        $railBarOffcanvasBodyId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-body");
        $railBarOffCanvasCloseId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-close");
        $railBarOffCanvasOpenId = StyleUtility::addComboStrapSuffix("{$railBarOffCanvasPrefix}-open");
        return <<<EOF
<div id="$railBarOffCanvasWrapperId">
    <button id="$railBarOffCanvasOpenId" class="btn" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#$railBarOffCanvasId" aria-controls="railbar-offcanvas">
    </button>

    <div id="$railBarOffCanvasId" class="offcanvas offcanvas-end" tabindex="-1" aria-labelledby="$railBarOffCanvasLabelId"
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

    public function getLayout(): string
    {

        if (isset($this->requestedLayout)) {
            return $this->requestedLayout;
        }

        $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
        if ($bootstrapVersion === Bootstrap::BootStrapFourMajorVersion) {
            return self::FIXED_LAYOUT;
        }
        $breakPoint = $this->getBreakPointInPixel(); // to implement
        if ($this->getRequestedViewPort() > $breakPoint) {
            return self::FIXED_LAYOUT;
        } else {
            return self::OFFCANVAS_LAYOUT;
        }

    }

    public function setRequestedViewPort(int $viewPort): FetcherRailBar
    {
        $this->requestedViewPort = $viewPort;
        return $this;
    }

    /**
     * @return int
     */
    public function getRequestedViewPort(): int
    {
        return $this->requestedViewPort;
    }

    private function notPrinted(): bool
    {
        try {
            Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
            if (
                tpl_getConf(TplUtility::CONF_PRIVATE_RAIL_BAR) === 1
                && empty($_SERVER['REMOTE_USER'])
            ) {
                return true;
            }
        } catch (ExceptionCompile $e) {
            //
        }
        return false;

    }

    private function getBreakPointInPixel(): int
    {
        try {
            Site::loadStrapUtilityTemplateIfPresentAndSameVersion();
        } catch (ExceptionCompile $e) {
            return Breakpoint::getPixelFromShortName("lg");
        }
        $breakpointName = tpl_getConf(TplUtility::CONF_BREAKPOINT_RAIL_BAR, TplUtility::BREAKPOINT_LARGE_NAME);
        if ($breakpointName === "never") {
            return 9999;
        }
        return Breakpoint::getPixelFromName($breakpointName);

    }

    private function toFixedLayout(string $railBarHtmlListItems): string
    {
        $fixedId = StyleUtility::addComboStrapSuffix("railbar-fixed");
        $zIndexRailbar = 1000; // A navigation bar (below the drop down because we use it in the search box for auto-completion)
        return <<<EOF
<div id="$fixedId" class="d-flex" style="z-index: $zIndexRailbar;">
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

}
