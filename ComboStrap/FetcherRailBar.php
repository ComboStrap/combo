<?php

namespace ComboStrap;

use dokuwiki\Menu\PageMenu;
use dokuwiki\Menu\SiteMenu;
use dokuwiki\Menu\UserMenu;

/**
 *
 * https://material.io/components/navigation-rail|Navigation rail
 */
class FetcherRailBar extends FetcherAbs
{

    const CANONICAL = "railbar";
    private WikiPath $path;


    public static function createRailBar(): FetcherRailBar
    {
        return new FetcherRailBar();
    }

    function getFetchPath(): Path
    {
        throw new ExceptionRuntimeInternal("Not implemented");
    }

    function getFetchString(): string
    {

        $wikiRequest = WikiRequestEnvironment::createAndCaptureState()
            ->setNewRunningId($this->path->getWikiId())
            ->setNewRequestedId($this->path->getWikiId())
            ->setNewAct("show");

        try {
            $liUserTools = (new UserMenu())->getListItems('action');
            $pageMenu = new PageMenu();
            $liPageTools = $pageMenu->getListItems();
            $liSiteTools = (new SiteMenu())->getListItems('action');
            // FYI: The below code outputs all menu in mobile (in another HTML layout)
            // echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']);
            $snippets = SnippetManager::getOrCreate()->toHtml();
            return <<<EOF
<div id="snippet-railbar-cs">
$snippets
</div>
<ul class="railbar">
    <li><a href="#" style="height: 19px;line-height: 17px;text-align: left;font-weight:bold"><span>User</span><svg style="height:19px"></svg></a></li>
    $liUserTools
    <li><a href="#" style="height: 19px;line-height: 17px;text-align: left;font-weight:bold"><span>Page</span><svg style="height:19px"></svg></a></li>
    $liPageTools
    <li><a href="#" style="height: 19px;line-height: 17px;text-align: left;font-weight:bold"><span>Website</span><svg style="height:19px"></svg></a></li>
    $liSiteTools
</ul>
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
}
