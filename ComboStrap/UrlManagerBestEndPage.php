<?php

namespace ComboStrap;

use action_plugin_combo_router;


/**
 * Class UrlManagerBestEndPage
 *
 * A class that implements the BestEndPage Algorithm for the {@link action_plugin_combo_router urlManager}
 */
class UrlManagerBestEndPage
{

    /**
     * If the number of names part that match is greater or equal to
     * this configuration, an Id Redirect is performed
     * A value of 0 disable and send only HTTP redirect
     */
    const CONF_MINIMAL_SCORE_FOR_REDIRECT = 'BestEndPageMinimalScoreForAliasCreation';
    const CONF_MINIMAL_SCORE_FOR_REDIRECT_DEFAULT = '2';


    /**
     * @param $pageId
     * @return array - the best poge id and its score
     * The score is the number of name that matches
     */
    public static function getBestEndPageId($pageId): array
    {

        $result = array();

        $pagesWithSameName = Index::getOrCreate()->getPagesWithSameLastName($pageId);
        if (count($pagesWithSameName) > 0) {

            // Default value
            $bestScore = 0;
            $bestPage = $pagesWithSameName[0];

            // The name of the dokuwiki id
            $missingPageIdNames = explode(':', $pageId);

            // Loop
            foreach ($pagesWithSameName as $pageIdWithSameName => $pageTitle) {

                $targetPageNames = explode(':', $pageIdWithSameName);
                $score = 0;
                foreach($targetPageNames as $targetPageName){
                    if(in_array($targetPageName,$missingPageIdNames)){
                        $score++;
                    }
                }
                if($score>$bestScore){
                    $bestScore = $score;
                    $bestPage = $pageIdWithSameName;
                }

            }

            $result = array(
                $bestPage,
                $bestScore
            );

        }
        return $result;

    }


    /**
     * @param $missingPageId
     * @return array with the best page and the type of redirect
     */
    public static function process($missingPageId): array
    {

        $return = array();

        $minimalScoreForARedirect = PluginUtility::getConfValue(self::CONF_MINIMAL_SCORE_FOR_REDIRECT, self::CONF_MINIMAL_SCORE_FOR_REDIRECT_DEFAULT);

        list($bestPageId, $bestScore) = self::getBestEndPageId($missingPageId);
        if ($bestPageId != null) {
            $redirectType = action_plugin_combo_router::REDIRECT_NOTFOUND_METHOD;
            if ($minimalScoreForARedirect != 0 && $bestScore >= $minimalScoreForARedirect) {
                $page = Page::createPageFromId($bestPageId);
                Aliases::createForPage($page)
                    ->addAlias($missingPageId, AliasType::REDIRECT)
                    ->sendToWriteStore()
                    ->setReadStore(MetadataDbStore::createForPage())
                    ->sendToWriteStore();
                $redirectType = action_plugin_combo_router::REDIRECT_PERMANENT_METHOD;
            }
            $return = array(
                $bestPageId,
                $redirectType
            );
        }
        return $return;

    }
}
