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

    const CANONICAL = "railbar";
    const FIXED_LAYOUT = "fixed";
    const OFFCANVAS_LAYOUT = "offcanvas";

    private WikiPath $path;
    private int $requestedViewPort = 1000;


    public static function createRailBar(): FetcherRailBar
    {
        return new FetcherRailBar();
    }

    private static function getComponentClass(): string
    {
        return StyleUtility::addComboStrapSuffix(self::CANONICAL);
    }

    function getFetchPath(): Path
    {
        throw new ExceptionRuntimeInternal("No fetch path: Railbar is not a file but a dynamic HTML document");
    }

    function getFetchString(): string
    {

        $wikiRequest = WikiRequestEnvironment::createAndCaptureState()
            ->setNewRunningId($this->path->getWikiId())
            ->setNewRequestedId($this->path->getWikiId())
            ->setNewAct("show");

        try {
            $railBarHtmlListItems = $this->getRailBarHtmlListItems();
            $railBarLayout = $this->getLayout();
            switch ($railBarLayout) {
                case self::FIXED_LAYOUT:
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
        $railBarOffCanvasWrapperId = StyleUtility::addComboStrapSuffix("railbar-offcanvas-wrapper");
        $railBarOffCanvasId = StyleUtility::addComboStrapSuffix("railbar-offcanvas");
        $railBarOffCanvasLabelId = StyleUtility::addComboStrapSuffix("railbar-offcanvas-label");
        $railBarOffcanvasBodyId = StyleUtility::addComboStrapSuffix("railbar-offcanvas-body");
        $railBarOffCanvasCloseId = StyleUtility::addComboStrapSuffix("railbar-offcanvas-close");
        $railBarOffCanvasOpenId = StyleUtility::addComboStrapSuffix("railbar-offcanvas-open");
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

    private function getLayout(): string
    {
        $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
        if ($bootstrapVersion === Bootstrap::BootStrapFourMajorVersion) {
            return self::FIXED_LAYOUT;
        }
        $breakPoint = 1000; // to implement
        if($this->getRequestedViewPort()> $breakPoint){
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

}
