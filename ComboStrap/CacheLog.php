<?php


namespace ComboStrap;


class CacheLog
{


    const TIMESTAMP_ATT = "timestamp";
    const EVENT_ATT = "event";
    const PATH_ATT = "path";
    const EXTENSION_ATT = "extension";
    const OPERATION_ATT = "operation";
    const MESSAGE_ATT = "message";
    const CACHE_LOG_TABLE = 'cache_log';
    const CACHE_LOG_ATTRIBUTES = [
        self::TIMESTAMP_ATT,
        self::EVENT_ATT,
        self::PATH_ATT,
        self::EXTENSION_ATT,
        self::OPERATION_ATT,
        self::MESSAGE_ATT
        ];

    public static function deleteCacheIfExistsAndLog(PageCompilerDocument $outputDocument, string $event, string $message)
    {
        $instructionsFile = $outputDocument->getCachePath();
        if (FileSystems::exists($instructionsFile)) {
            FileSystems::delete($instructionsFile);
            try {
                CacheLog::logCacheEvent(
                    $event,
                    $outputDocument->getPage()->getPath()->toString(),
                    $outputDocument->getExtension(),
                    CacheManager::CACHE_DELETION,
                    $message
                );
            } catch (ExceptionCombo $e) {
                // should not fired
                LogUtility::log2file("Error while logging cache event. Error: {$e->getMessage()}");
            }
        }
    }

    public static function renderCacheAndLog(PageCompilerDocument $outputDocument, string $event, string $message)
    {
        $outputDocument->process();
        try {
            CacheLog::logCacheEvent(
                $event,
                $outputDocument->getPage()->getPath()->toString(),
                $outputDocument->getExtension(),
                CacheManager::CACHE_CREATION,
                $message
            );
        } catch (ExceptionCombo $e) {
            // should not fired
            LogUtility::log2file("Error while logging cache event. Error: {$e->getMessage()}");
        }
    }

    /**
     * @throws ExceptionCombo
     */
    public static function logCacheEvent(string $event, string $path, string $format, string $operation, string $message)
    {


        $row = array(
            self::TIMESTAMP_ATT => date("c"),
            self::EVENT_ATT => $event,
            self::PATH_ATT => $path,
            self::EXTENSION_ATT => $format,
            self::OPERATION_ATT => $operation,
            self::MESSAGE_ATT => $message
        );
        $request = Sqlite::createOrGetBackendSqlite()
            ->createRequest()
            ->setTableRow(self::CACHE_LOG_TABLE, $row);
        try {
            $request
                ->execute();
        } finally {
            $request->close();
        }


    }

    /**
     * @throws ExceptionCombo
     */
    public static function getCacheLog(): array
    {
        $sqlite = Sqlite::createOrGetBackendSqlite();
        if ($sqlite === null) {
            throw new ExceptionCombo("Sqlite is not available");
        }


        /**
         * Execute
         */
        $attributes[] = DatabasePageRow::ROWID;
        $attributes = array_merge($attributes,self::CACHE_LOG_ATTRIBUTES);
        $select = Sqlite::createSelectFromTableAndColumns(self::CACHE_LOG_TABLE, $attributes);
        $request = $sqlite->createRequest()
            ->setQuery($select);
        try {
            return $request->execute()
                ->getRows();
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("Unable to get the cache log. Error:" . $e->getMessage(),self::CANONICAL,0,$e);
        } finally {
            $request->close();
        }

    }

}
