<?php

namespace ComboStrap;

use action_plugin_combo_router;
use ComboStrap\Meta\Field\Aliases;
use ComboStrap\Meta\Field\AliasType;
use ComboStrap\Meta\Store\MetadataDbStore;


/**
 * Class UrlManagerBestEndPage
 *
 * A class that implements the BestEndPage Algorithm for the {@link action_plugin_combo_router urlManager}
 */
class RouterBestEndPage
{

    /**
     * If the number of names part that match is greater or equal to
     * this configuration, an Id Redirect is performed
     * A value of 0 disable and send only HTTP redirect
     */
    const CONF_MINIMAL_SCORE_FOR_REDIRECT = 'BestEndPageMinimalScoreForAliasCreation';
    const CONF_MINIMAL_SCORE_FOR_REDIRECT_DEFAULT = '2';


    /**
     * @param MarkupPath $requestedPage
     * @return array - the best page id and its score
     * The score is the number of name that matches
     */
    public static function getBestEndPageId(MarkupPath $requestedPage): array
    {

        $pagesWithSameName = Index::getOrCreate()
            ->getPagesWithSameLastName($requestedPage);
        if (sizeof($pagesWithSameName) == 0) {
            return [null, null];
        }
        return self::getBestEndPageIdFromPages($pagesWithSameName, $requestedPage);


    }


    /**
     * @param MarkupPath $missingPage
     * @return array with the best page and the type of redirect
     */
    public static function process(MarkupPath $missingPage): array
    {

        $return = [null, null];

        $minimalScoreForARedirect = SiteConfig::getConfValue(self::CONF_MINIMAL_SCORE_FOR_REDIRECT, self::CONF_MINIMAL_SCORE_FOR_REDIRECT_DEFAULT);

        list($bestPage, $bestScore) = self::getBestEndPageId($missingPage);
        if ($bestPage != null) {
            $redirectType = RouterRedirection::REDIRECT_NOTFOUND_METHOD;
            if ($minimalScoreForARedirect != 0 && $bestScore >= $minimalScoreForARedirect) {
                try {
                    Aliases::createForPage($bestPage)
                        ->addAlias($missingPage, AliasType::REDIRECT)
                        ->sendToWriteStore()
                        ->setReadStore(MetadataDbStore::getOrCreateFromResource($bestPage))
                        ->sendToWriteStore();
                } catch (ExceptionBadArgument|ExceptionCompile $e) {
                    LogUtility::error("Error while creating an alias",LogUtility::SUPPORT_CANONICAL,$e);
                    return $return;
                }
                $redirectType = RouterRedirection::REDIRECT_PERMANENT_METHOD;
            }
            $return = array(
                $bestPage,
                $redirectType
            );
        }
        return $return;

    }

    /**
     * @param MarkupPath[] $candidatePagesWithSameLastName
     * @param MarkupPath $requestedPage
     * @return array
     */
    public static function getBestEndPageIdFromPages(array $candidatePagesWithSameLastName, MarkupPath $requestedPage): array
    {
        // Default value
        $bestScore = 0;
        $bestPage = $candidatePagesWithSameLastName[0];

        // The name of the dokuwiki id
        $requestedPageNames = $requestedPage->getPathObject()->getNames();

        // Loop
        foreach ($candidatePagesWithSameLastName as $candidatePage) {

            try {
                if ($candidatePage->getWikiId() === $requestedPage->getWikiId()) {
                    // when the index is not up to date
                    continue;
                }
            } catch (ExceptionBadArgument $e) {
                // should not happen but yeah
            }

            $candidatePageNames = $candidatePage->getPathObject()->getNames();
            $score = 0;
            foreach ($candidatePageNames as $candidatePageName) {
                if (in_array($candidatePageName, $requestedPageNames)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPage = $candidatePage;
            } else if ($score === $bestScore) {
                /**
                 * Best backlink count
                 */
                $candidateBacklinksCount = sizeof($candidatePage->getBacklinks());
                $bestPageBacklinksCount = sizeof($bestPage->getBacklinks());
                if ($candidateBacklinksCount > $bestPageBacklinksCount) {
                    $bestPage = $candidatePage;
                }
            }

        }

        return array(
            $bestPage,
            $bestScore
        );
    }
}
