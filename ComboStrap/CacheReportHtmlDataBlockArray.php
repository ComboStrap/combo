<?php


namespace ComboStrap;


class CacheReportHtmlDataBlockArray
{

    const RESULT_STATUS = 'result';
    const DATE_MODIFIED = 'mtime';
    const CACHE_FILE = "file";

    /**
     * Used when the cache data report
     * are injected in the page in a json format
     */
    public const APPLICATION_COMBO_CACHE_JSON = "application/combo+cache+json";
    const DEPENDENCY_ATT = "dependency";


    /**
     * @return array - a array that will be transformed as json HTML data block
     * to be included in a HTML page in order to insert cache results in the html page
     */
    public static function getFromRuntime(): array
    {
        $cacheManager = CacheManager::getOrCreate();
        $cacheReporters = $cacheManager->getCacheResults();
        if ($cacheReporters === null) {
            return [];
        }
        $htmlDataBlock = [];
        foreach ($cacheReporters as $cacheReporter) {

            foreach ($cacheReporter->getResults() as $result) {

                $modifiedDate = "";
                if ($result->getPath() !== null) {
                    $modifiedTime = FileSystems::getModifiedTime($result->getPath());
                    if ($modifiedTime !== null) {
                        // the file exists
                        $modifiedDate = $modifiedTime->format(Iso8601Date::getFormat());
                    }
                }
                $mode = $result->getMode();
                $slotId = $result->getSlotId();

                $cacheFile = null;
                try {
                    $dokuPath = $result->getPath()->toDokuPath();
                    $cacheFile = $dokuPath->getDokuwikiId();
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("The path ({$result->getPath()}) could not be transformed as wiki path. Error:{$e->getMessage()}");
                }
                $data = [
                    self::RESULT_STATUS => $result->getResult(),
                    self::DATE_MODIFIED => $modifiedDate,
                    self::CACHE_FILE => $cacheFile
                ];

                if ($mode === HtmlDocument::mode) {
                    $dependencies = $cacheManager
                        ->getCacheDependenciesForSlot($slotId)
                        ->getDependencies();
                    if ($dependencies !== null) {
                        $data[self::DEPENDENCY_ATT] = $dependencies;
                    }
                }

                $htmlDataBlock[$slotId][$mode] = $data;

            }

        }
        return $htmlDataBlock;
    }


    /**
     * An utility function to extract the cache data block from test responses
     * @param \TestResponse $response
     * @return mixed
     * @throws ExceptionCombo
     */
    public static function extractFromResponse(\TestResponse $response)
    {
        $metaCacheMain = $response->queryHTML('script[type="' . CacheReportHtmlDataBlockArray::APPLICATION_COMBO_CACHE_JSON . '"]');
        if ($metaCacheMain->count() != 1) {
            throw new ExceptionCombo("The data cache was not found");
        }
        $cacheJsonTextValue = $metaCacheMain->elements[0]->childNodes->item(0)->textContent;
        return json_decode(XmlUtility::extractTextWithoutCdata($cacheJsonTextValue), true);
    }
}
