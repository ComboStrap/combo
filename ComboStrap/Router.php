<?php

namespace ComboStrap;

use ComboStrap\Meta\Field\AliasType;
use ComboStrap\Web\Url;

class Router
{


    public const GO_TO_SEARCH_ENGINE = 'GoToSearchEngine';
    public const GO_TO_NS_START_PAGE = 'GoToNsStartPage';
    public const GO_TO_EDIT_MODE = 'GoToEditMode';
    public const GO_TO_BEST_END_PAGE_NAME = 'GoToBestEndPageName';
    public const GO_TO_BEST_NAMESPACE = 'GoToBestNamespace';
    public const NOTHING = 'Nothing';
    public const GO_TO_BEST_PAGE_NAME = 'GoToBestPageName';
    private PageRules $pageRules;

    /**
     * @throws ExceptionSqliteNotAvailable
     * @throws ExceptionNotFound - no redirection found
     */
    public function getRedirection(): RouterRedirection
    {

        /**
         * Without SQLite, this module does not work further
         * It throws
         */
        Sqlite::createOrGetSqlite();

        /**
         * Initiate Page Rules
         */
        $this->pageRules = new PageRules();


        /**
         * Unfortunately, DOKUWIKI_STARTED is not the first event
         * The id may have been changed by
         * {@link action_plugin_combo_lang::load_lang()}
         * function, that's why we check against the {@link $_REQUEST}
         * and not the global ID
         */
        $originalId = self::getOriginalIdFromRequest();

        /**
         * Page is an existing id
         * in the database ?
         */
        global $ID;
        $requestedMarkupPath = MarkupPath::createMarkupFromId($ID);
        if (FileSystems::exists($requestedMarkupPath)) {

            /**
             * If this is not the root home page
             * and if the canonical id is the not the same (the id has changed)
             * and if this is not a historical page (revision)
             * redirect
             */
            if (
                $originalId !== $requestedMarkupPath->getUrlId() // The id may have been changed
                && $ID != Site::getIndexPageName()
                && !isset($_REQUEST["rev"])
            ) {
                /**
                 * TODO: When saving for the first time, the page is not stored in the database
                 *   but that's not the case actually
                 */
                $databasePageRow = $requestedMarkupPath->getDatabasePage();
                if ($databasePageRow->exists()) {
                    /**
                     * A move may leave the database in a bad state,
                     * unfortunately (ie page is not in index, unable to update, ...)
                     * We test therefore if the database page id exists
                     */
                    $targetPageId = $databasePageRow->getFromRow("id");
                    $targetPath = MarkupPath::createMarkupFromId($targetPageId);
                    if (FileSystems::exists($targetPath)) {
                        return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_PERMALINK_EXTENDED)
                            ->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)
                            ->setTargetMarkupPath($targetPath)
                            ->build();
                    }

                }
            }
        }

        $identifier = $ID;

        /**
         * Page Id in the url
         * Note that if the ID is a permalink, global $ID has already the real id
         * Why? because unfortunately, DOKUWIKI_STARTED is not the first event
         * {@link action_plugin_combo_lang::load_lang()} may have already
         * transformed a permalink into a real dokuwiki id
         *
         * We let it here because we don't know for sure that it will stay this way
         * What fucked up is fucked up
         */
        $shortPageId = PageUrlPath::getShortEncodedPageIdFromUrlId($requestedMarkupPath->getPathObject()->getLastNameWithoutExtension());
        if ($shortPageId != null) {
            $pageId = PageUrlPath::decodePageId($shortPageId);
        } else {
            /**
             * Permalink with id
             */
            $pageId = PageUrlPath::decodePageId($identifier);
        }
        if ($pageId !== null) {

            if ($requestedMarkupPath->getParent() === null) {
                $page = DatabasePageRow::createFromPageId($pageId)->getMarkupPath();
                if ($page !== null && $page->exists()) {
                    return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_PERMALINK)
                        ->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)
                        ->setTargetMarkupPath($page)
                        ->build();
                }
            }

            /**
             * Page Id Abbr ?
             * {@link PageUrlType::CONF_CANONICAL_URL_TYPE}
             */
            $page = DatabasePageRow::createFromPageIdAbbr($pageId)->getMarkupPath();
            if ($page === null) {
                // or the length of the abbr has changed
                $canonicalDatabasePage = new DatabasePageRow();
                try {
                    $row = $canonicalDatabasePage->getDatabaseRowFromAttribute("substr(" . PageId::PROPERTY_NAME . ", 1, " . strlen($pageId) . ")", $pageId);
                    $canonicalDatabasePage->setRow($row);
                    $page = $canonicalDatabasePage->getMarkupPath();
                } catch (ExceptionNotFound $e) {
                    // nothing to do
                }
            }
            if ($page !== null && $page->exists()) {
                /**
                 * If the url canonical id has changed, we show it
                 * to the writer by performing a permanent redirect
                 */
                if ($identifier != $page->getUrlId()) {
                    // Google asks for a redirect
                    // https://developers.google.com/search/docs/advanced/crawling/301-redirects
                    // People access your site through several different URLs.
                    // If, for example, your home page can be reached in multiple ways
                    // (for instance, http://example.com/home, http://home.example.com, or http://www.example.com),
                    // it's a good idea to pick one of those URLs as your preferred (canonical) destination,
                    // and use redirects to send traffic from the other URLs to your preferred URL.
                    return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_PERMALINK_EXTENDED)
                        ->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)
                        ->setTargetMarkupPath($page)
                        ->build();

                }

                return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_PERMALINK_EXTENDED)
                    ->setType(RouterRedirection::REDIRECT_TRANSPARENT_METHOD)
                    ->setTargetMarkupPath($page)
                    ->build();

            }
            // permanent url not yet in the database
            // Other permanent such as permanent canonical ?
            // We let the process go with the new identifier

        }

        /**
         * Identifier is a Canonical ?
         */
        $canonicalDatabasePage = DatabasePageRow::createFromCanonical($identifier);
        $canonicalPage = $canonicalDatabasePage->getMarkupPath();
        if ($canonicalPage !== null && $canonicalPage->exists()) {
            $builder = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_CANONICAL)
                ->setTargetMarkupPath($canonicalPage);
            /**
             * Does the canonical url is canonical name based
             * ie {@link  PageUrlType::CONF_VALUE_CANONICAL_PATH}
             */
            if ($canonicalPage->getUrlId() === $identifier) {
                $builder->setType(RouterRedirection::REDIRECT_TRANSPARENT_METHOD);
            } else {
                $builder->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD);
            }
            return $builder->build();

        }

        /**
         * Identifier is an alias
         */
        $aliasRequestedPage = DatabasePageRow::createFromAlias($identifier)->getMarkupPath();
        if (
            $aliasRequestedPage !== null
            && $aliasRequestedPage->exists()
            // The build alias is the file system metadata alias
            // it may be null if the replication in the database was not successful
            && $aliasRequestedPage->getBuildAlias() !== null
        ) {
            $buildAlias = $aliasRequestedPage->getBuildAlias();
            $builder = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_ALIAS)
                ->setTargetMarkupPath($aliasRequestedPage);
            switch ($buildAlias->getType()) {
                case AliasType::REDIRECT:
                    return $builder->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)->build();
                case AliasType::SYNONYM:
                    return $builder->setType(RouterRedirection::REDIRECT_TRANSPARENT_METHOD)->build();
                default:
                    LogUtility::msg("The alias type ({$buildAlias->getType()}) is unknown. A permanent redirect was performed for the alias $identifier");
                    return $builder->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)->build();
            }
        }

        /**
         * Do we have a page rules
         * If there is a redirection defined in the page rules
         */
        try {
            return $this->getRedirectionFromPageRules();
        } catch (ExceptionNotFound $e) {
            // no pages rules redirection
        }

        /**
         * No redirection found in the database by id
         */

        /**
         * Edit mode
         */
        $conf = ExecutionContext::getActualOrCreateFromEnv()->getConfig();
        if (Identity::isWriter() && $conf->getBooleanValue(self::GO_TO_EDIT_MODE, true)) {

            // Stop here
            return RouterRedirectionBuilder::createFromOrigin(self::GO_TO_EDIT_MODE)
                ->build();

        }

        /**
         *  We are still a reader, the redirection does not exist the user is not allowed to edit the page (public of other)
         */
        $actionReaderFirst = $conf->getValue('ActionReaderFirst');
        if ($actionReaderFirst == self::NOTHING) {
            throw new ExceptionNotFound();
        }

        // We are reader and their is no redirection set, we apply the algorithm
        $readerAlgorithms = array();
        $readerAlgorithms[0] = $actionReaderFirst;
        $readerAlgorithms[1] = $conf->getValue('ActionReaderSecond');
        $readerAlgorithms[2] = $conf->getValue('ActionReaderThird');

        while (
            ($algorithm = array_shift($readerAlgorithms)) != null
        ) {

            switch ($algorithm) {

                case self::NOTHING:
                    throw new ExceptionNotFound();

                case self::GO_TO_BEST_END_PAGE_NAME:

                    /**
                     * @var MarkupPath $bestEndPage
                     */
                    list($bestEndPage, $method) = RouterBestEndPage::process($requestedMarkupPath);
                    if ($bestEndPage != null) {
                        try {
                            $notSamePage = $bestEndPage->getWikiId() !== $requestedMarkupPath->getWikiId();
                        } catch (ExceptionBadArgument $e) {
                            LogUtility::error("The path should be wiki markup path", LogUtility::SUPPORT_CANONICAL, $e);
                            $notSamePage = false;
                        }
                        if ($notSamePage) {
                            $redirectionBuilder = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_BEST_END_PAGE_NAME)
                                ->setTargetMarkupPath($bestEndPage);
                            switch ($method) {
                                case RouterRedirection::REDIRECT_PERMANENT_METHOD:
                                    return $redirectionBuilder
                                        ->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)
                                        ->build();
                                case RouterRedirection::REDIRECT_NOTFOUND_METHOD:
                                    return $redirectionBuilder
                                        ->setType(RouterRedirection::REDIRECT_NOTFOUND_METHOD)
                                        ->build();
                                default:
                                    LogUtility::error("This redirection method ($method) was not expected for the redirection algorithm ($algorithm)");
                            }
                        }

                    }
                    break;

                case self::GO_TO_NS_START_PAGE:

                    $redirectBuilder = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_START_PAGE)
                        ->setType(RouterRedirection::REDIRECT_NOTFOUND_METHOD);

                    // Start page with the conf['start'] parameter
                    $startPage = getNS($identifier) . ':' . $conf['start'];
                    $startPath = MarkupPath::createMarkupFromId($startPage);
                    if (FileSystems::exists($startPath)) {
                        return $redirectBuilder->setTargetMarkupPath($startPath)->build();
                    }

                    // Start page with the same name than the namespace
                    $startPage = getNS($identifier) . ':' . curNS($identifier);
                    $startPath = MarkupPath::createMarkupFromId($startPage);
                    if (FileSystems::exists($startPath)) {
                        return $redirectBuilder->setTargetMarkupPath($startPath)->build();
                    }

                    break;

                case self::GO_TO_BEST_PAGE_NAME:

                    $bestPageId = null;

                    $bestPage = $this->getBestPage($identifier);
                    $bestPageId = $bestPage['id'];
                    $scorePageName = $bestPage['score'];

                    // Get Score from a Namespace
                    $bestNamespace = $this->scoreBestNamespace($identifier);
                    $bestNamespaceId = $bestNamespace['namespace'];
                    $namespaceScore = $bestNamespace['score'];

                    // Compare the two score
                    if ($scorePageName > 0 or $namespaceScore > 0) {
                        $redirectionBuilder = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_BEST_PAGE_NAME)
                            ->setType(RouterRedirection::REDIRECT_NOTFOUND_METHOD);
                        if ($scorePageName > $namespaceScore) {
                            return $redirectionBuilder
                                ->setTargetMarkupPath(MarkupPath::createMarkupFromId($bestPageId))
                                ->build();
                        }
                        return $redirectionBuilder
                            ->setTargetMarkupPath(MarkupPath::createMarkupFromId($bestNamespaceId))
                            ->build();
                    }
                    break;

                case self::GO_TO_BEST_NAMESPACE:

                    $scoreNamespace = $this->scoreBestNamespace($identifier);
                    $bestNamespaceId = $scoreNamespace['namespace'];
                    $score = $scoreNamespace['score'];

                    if ($score > 0) {
                        return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_BEST_NAMESPACE)
                            ->setType(RouterRedirection::REDIRECT_NOTFOUND_METHOD)
                            ->setTargetMarkupPath(MarkupPath::createMarkupFromId($bestNamespaceId))
                            ->build();
                    }
                    break;

                case self::GO_TO_SEARCH_ENGINE:

                    return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_SEARCH_ENGINE)
                        ->setType(RouterRedirection::REDIRECT_NOTFOUND_METHOD)
                        ->build();

            }

        }

        throw new ExceptionNotFound();

    }


    /**
     * @return string|null
     *
     * Return the original id from the request
     * ie `howto:how-to-get-started-with-combostrap-m3i8vga8`
     * if `/howto/how-to-get-started-with-combostrap-m3i8vga8`
     *
     * Unfortunately, DOKUWIKI_STARTED is not the first event
     * The id may have been changed by
     * {@link action_plugin_combo_lang::load_lang()}
     * function, that's why we have this function
     * to get the original requested id
     */
    static function getOriginalIdFromRequest(): ?string
    {
        $originalId = $_GET["id"] ?? null;
        if ($originalId === null) {
            return null;
        }
        // We may get a `/` as first character
        // because we return an id, we need to delete it
        if (substr($originalId, 0, 1) === "/") {
            $originalId = substr($originalId, 1);
        }
        // transform / to :
        return str_replace("/", WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $originalId);
    }

    /**
     * Return a redirection declared in the redirection table or throw if not found
     * @throws ExceptionNotFound
     */
    private function getRedirectionFromPageRules(): RouterRedirection
    {
        global $ID;

        $calculatedTarget = null;
        $ruleMatcher = null; // Used in a warning message if the target page does not exist
        // Known redirection in the table
        // Get the page from redirection data
        $rules = $this->pageRules->getRules();
        foreach ($rules as $rule) {

            $ruleMatcher = strtolower($rule[PageRules::MATCHER_NAME]);
            $ruleTarget = $rule[PageRules::TARGET_NAME];

            // Glob to Rexgexp
            $regexpPattern = '/' . str_replace("*", "(.*)", $ruleMatcher) . '/i';

            // Match ?
            // https://www.php.net/manual/en/function.preg-match.php
            $pregMatchResult = @preg_match($regexpPattern, $ID, $matches);
            if ($pregMatchResult === false) {
                // The `if` to take into account this problem
                // PHP Warning:  preg_match(): Unknown modifier 'd' in /opt/www/datacadamia.com/lib/plugins/combo/action/router.php on line 972
                LogUtility::log2file("processing Page Rules An error occurred with the pattern ($regexpPattern)", LogUtility::LVL_MSG_WARNING);
                throw new ExceptionNotFound();
            }
            if ($pregMatchResult) {
                $calculatedTarget = $ruleTarget;
                foreach ($matches as $key => $match) {
                    if ($key == 0) {
                        continue;
                    } else {
                        $calculatedTarget = str_replace('$' . $key, $match, $calculatedTarget);
                    }
                }
                break;
            }
        }

        if ($calculatedTarget == null) {
            throw new ExceptionNotFound();
        }

        // If this is an external redirect (other domain)
        try {
            $url = Url::createFromString($calculatedTarget);
            // Unfortunately, the page id `my:page` is a valid url after parsing with the scheme `my`
            try {
                $isHttp = strpos($url->getScheme(), "http") === 0;
            } catch (ExceptionNotFound $e) {
                $isHttp = false;
            }
            if ($isHttp) {
                return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_PAGE_RULES)
                    ->setTargetUrl($url)
                    ->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)
                    ->build();
            }
        } catch (ExceptionBadSyntax|ExceptionBadArgument $e) {
            // not an URL
        }


        // If the page exist
        // This is DokuWiki Id and should always be lowercase
        // The page rule may have change that
        $calculatedTarget = strtolower($calculatedTarget);
        $markupPath = MarkupPath::createMarkupFromId($calculatedTarget);
        if (FileSystems::exists($markupPath)) {

            return RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_PAGE_RULES)
                ->setTargetMarkupPath($markupPath)
                ->setType(RouterRedirection::REDIRECT_PERMANENT_METHOD)
                ->build();

        }

        LogUtility::error("The calculated target page ($calculatedTarget) (for the non-existing page `$ID` with the matcher `$ruleMatcher`) does not exist");
        throw new ExceptionNotFound();

    }


    /**
     * @param $id
     * @return array
     */
    private
    function getBestPage($id): array
    {

        // The return parameters
        $bestPageId = null;
        $scorePageName = null;

        // Get Score from a page
        $pageName = noNS($id);
        $pagesWithSameName = ft_pageLookup($pageName);
        if (count($pagesWithSameName) > 0) {

            // Search same namespace in the page found than in the Id page asked.
            $bestNbWordFound = 0;


            $wordsInPageSourceId = explode(':', $id);
            foreach ($pagesWithSameName as $targetPageId => $title) {

                // Nb of word found in the target page id
                // that are in the source page id
                $nbWordFound = 0;
                foreach ($wordsInPageSourceId as $word) {
                    $nbWordFound = $nbWordFound + substr_count($targetPageId, $word);
                }

                if ($bestPageId == null) {

                    $bestNbWordFound = $nbWordFound;
                    $bestPageId = $targetPageId;

                } else {

                    if ($nbWordFound >= $bestNbWordFound && strlen($bestPageId) > strlen($targetPageId)) {

                        $bestNbWordFound = $nbWordFound;
                        $bestPageId = $targetPageId;

                    }

                }

            }
            $config = ExecutionContext::getActualOrCreateFromEnv()->getConfig();
            $weightFactorForSamePageName = $config->getValue('WeightFactorForSamePageName');
            $weightFactorForSameNamespace = $config->getValue('WeightFactorForSameNamespace');
            $scorePageName = $weightFactorForSamePageName + ($bestNbWordFound - 1) * $weightFactorForSameNamespace;
            return array(
                'id' => $bestPageId,
                'score' => $scorePageName);
        }
        return array(
            'id' => $bestPageId,
            'score' => $scorePageName
        );

    }

    /**
     * getBestNamespace
     * Return a list with 'BestNamespaceId Score'
     * @param $id
     * @return array
     */
    private
    function scoreBestNamespace($id): array
    {

        $nameSpaces = array();
        $pathNames = array();

        // Parameters
        $requestedPath = MarkupPath::createMarkupFromId($id);
        try {
            $pageNameSpace = $requestedPath->getParent();
            $pathNames = array_slice($pageNameSpace->getNames(), 0, -1);
            if (FileSystems::exists($pageNameSpace)) {
                $nameSpaces = array($pageNameSpace->toAbsoluteId());
            } else {
                global $conf;
                $nameSpaces = ft_pageLookup($conf['start']);
            }
        } catch (ExceptionNotFound $e) {
            // no parent, root
        }

        // Parameters and search the best namespace
        $bestNbWordFound = 0;
        $bestNamespaceId = null;
        foreach ($nameSpaces as $nameSpace) {

            $nbWordFound = 0;
            foreach ($pathNames as $pathName) {
                if (strlen($pathName) > 2) {
                    $nbWordFound = $nbWordFound + substr_count($nameSpace, $pathName);
                }
            }
            if ($nbWordFound > $bestNbWordFound) {
                // Take only the smallest namespace
                if ($bestNbWordFound == null || strlen($nameSpace) < strlen($bestNamespaceId)) {
                    $bestNbWordFound = $nbWordFound;
                    $bestNamespaceId = $nameSpace;
                }
            }
        }
        $config = ExecutionContext::getActualOrCreateFromEnv()->getConfig();
        $startPageFactor = $config->getValue('WeightFactorForStartPage');
        $nameSpaceFactor = $config->getValue('WeightFactorForSameNamespace');
        if ($bestNbWordFound > 0) {
            $bestNamespaceScore = $bestNbWordFound * $nameSpaceFactor + $startPageFactor;
        } else {
            $bestNamespaceScore = 0;
        }


        return array(
            'namespace' => $bestNamespaceId,
            'score' => $bestNamespaceScore
        );

    }


}
