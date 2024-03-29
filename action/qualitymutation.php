<?php

use ComboStrap\CacheLog;
use ComboStrap\DataType;
use ComboStrap\Event;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionNotExists;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityCalculatedIndicator;
use ComboStrap\LowQualityPageOverwrite;
use ComboStrap\MarkupPath;
use ComboStrap\MetadataMutation;
use ComboStrap\PagePath;
use ComboStrap\Site;


/**
 * Delete the backlinks when there is a page quality mutation
 */
class action_plugin_combo_qualitymutation extends DokuWiki_Action_Plugin
{


    public const QUALITY_MUTATION_EVENT_NAME = 'quality_mutation';
    const CANONICAL = "low_quality";
    const DESC = "desc";

    public static function getQualityMetas(): array
    {
        return [
            LowQualityCalculatedIndicator::getPersistentName(),
            LowQualityPageOverwrite::getPersistentName()
        ];
    }


    public function register(Doku_Event_Handler $controller)
    {


        /**
         * create the async event
         */
        $controller->register_hook(MetadataMutation::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'create_quality_mutation', array());

        /**
         * process the Async event
         */
        $controller->register_hook(self::QUALITY_MUTATION_EVENT_NAME, 'AFTER', $this, 'handle_quality_mutation');


    }


    public function handle_quality_mutation(Doku_Event $event, $param)
    {


        $data = $event->data;
        $path = $data[PagePath::getPersistentName()];
        $page = MarkupPath::createPageFromAbsoluteId($path);

        if (!$page->getCanBeOfLowQuality()) {
            return;
        }

        /**
         * Delete the html document cache to rewrite the links
         *
         */
        foreach ($page->getBacklinks() as $backlink) {
            try {
                $htmlDocument = $backlink->createHtmlFetcherWithItselfAsContextPath();
            } catch (ExceptionNotExists $e) {
                continue;
            }
            try {
                $wikiId = $backlink->getWikiId();
            } catch (ExceptionBadArgument $e) {
                LogUtility::internalError("Backlink should be only for wiki path");
                continue;
            }
            $desc = $data[self::DESC];
            CacheLog::deleteCacheIfExistsAndLog(
                $htmlDocument,
                self::QUALITY_MUTATION_EVENT_NAME,
                "The {$wikiId} of {$path} had its HTML cache deleted ($desc)."
            );

        }
    }


    /**
     */
    function create_quality_mutation(Doku_Event $event, $params): void
    {

        if (!Site::isLowQualityProtectionEnable()) {
            return;
        }

        /**
         * If this is not a mutation on references we return.
         */
        $data = $event->data;
        $variableName = $data["name"];
        if (!(in_array($variableName, self::getQualityMetas()))) {
            return;
        }

        $newValue = DataType::toString($data[MetadataMutation::NEW_VALUE_ATTRIBUTE]);
        $oldValue = DataType::toString($data[MetadataMutation::OLD_VALUE_ATTRIBUTE]);
        $path = $data[PagePath::getPersistentName()];
        Event::createEvent(
            self::QUALITY_MUTATION_EVENT_NAME,
            [
                PagePath::getPersistentName() => $path,
                self::DESC => "The variable ($variableName) has the new value ($newValue) overriding ($oldValue)"
            ]
        );


    }

}



