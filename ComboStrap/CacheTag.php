<?php

namespace ComboStrap;

/**
 * @deprecated - use the metadata/page properties instead
 */
class CacheTag
{

    public const MARKUP = "cache";
    public const EXPIRATION_ATTRIBUTE = "expiration";
    public const PARSING_STATE_UNSUCCESSFUL = "unsuccessful";
    public const PARSING_STATUS = "status";
    public const PARSING_STATE_SUCCESSFUL = "successful";


    public static function handle(TagAttributes $attributes): array
    {

        $cronExpression = $attributes->getValue(self::EXPIRATION_ATTRIBUTE);
        $returnedArray = array(
            PluginUtility::PAYLOAD => $cronExpression
        );

        try {
            $requestPage = MarkupPath::createPageFromPathObject(
                ExecutionContext::getActualOrCreateFromEnv()->getExecutingWikiPath()
            );
        } catch (ExceptionNotFound $e) {
            $message = "No markup executing. Cache expiration date could not be set";
            LogUtility::error($message, self::MARKUP);
            return array(
                PluginUtility::EXIT_CODE => self::PARSING_STATE_UNSUCCESSFUL,
                PluginUtility::EXIT_MESSAGE => $message
            );
        }

        try {
            CacheExpirationFrequency::createForPage($requestPage)
                ->setValue($cronExpression)
                ->sendToWriteStore();
        } catch (ExceptionBadSyntax $e) {
            $returnedArray[PluginUtility::EXIT_CODE] = self::PARSING_STATE_UNSUCCESSFUL;
            $returnedArray[PluginUtility::EXIT_MESSAGE] = "The expression ($cronExpression) is not a valid expression";
            return $returnedArray;
        } catch (ExceptionBadArgument $e) {
            // It should not happen
            $message = "Internal Error: The cache expiration date could not be stored: {$e->getMessage()}";
            LogUtility::error($message, self::MARKUP, $e);
            $returnedArray[PluginUtility::EXIT_CODE] = self::PARSING_STATE_UNSUCCESSFUL;
            $returnedArray[PluginUtility::EXIT_MESSAGE] = $message;
            return $returnedArray;
        }

        LogUtility::warning("The cache syntax component has been deprecated for the cache frequency metadata", CacheExpirationFrequency::PROPERTY_NAME);

        $returnedArray[PluginUtility::EXIT_CODE] = self::PARSING_STATE_SUCCESSFUL;
        $returnedArray[PluginUtility::PAYLOAD] = $cronExpression;
        return $returnedArray;

    }

    public static function renderXhtml(array $data): string
    {

        if ($data[PluginUtility::EXIT_CODE] !== CacheTag::PARSING_STATE_SUCCESSFUL) {
            $message = $data[PluginUtility::EXIT_MESSAGE];
            LogUtility::error($message, CacheExpirationFrequency::PROPERTY_NAME);
        }
        return "";

    }

    public static function metadata($data)
    {

        if ($data[PluginUtility::EXIT_CODE] === CacheTag::PARSING_STATE_SUCCESSFUL) {
            $cronExpression = $data[PluginUtility::PAYLOAD];
            try {
                $requestPage = MarkupPath::createFromRequestedPage();
            } catch (ExceptionNotFound $e) {
                LogUtility::error("Unable to store the cache expiration date because no requested page", self::MARKUP, $e);
                return;
            }
            try {
                CacheExpirationFrequency::createForPage($requestPage)
                    ->setValue($cronExpression)
                    ->sendToWriteStore();
            } catch (ExceptionCompile $e) {
                // should not happen as we have test for its validity
            }

        }

    }
}
