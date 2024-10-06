<?php


namespace ComboStrap;

/**
 * Class Event
 * @package ComboStrap
 * Asynchronous pub/sub system
 *
 * Dokuwiki allows event but they are synchronous
 * because php does not live in multiple thread
 *
 * With the help of Sqlite, we make them asynchronous
 */
class Event
{

    const EVENT_TABLE_NAME = "EVENTS_QUEUE";

    const CANONICAL = "event";

    /**
     * Uppercase mandatory (the column is uppercased when returnd from a *)
     */
    const EVENT_NAME_ATTRIBUTE = "NAME";

    /**
     * Uppercase mandatory (the column is uppercased when returnd from a *)
     */
    const EVENT_DATA_ATTRIBUTE = "DATA";
    /**
     * Uppercase mandatory (the column is uppercased when returnd from a *)
     */
    const TIMESTAMP_ATTRIBUTE = "TIMESTAMP";

    /**
     * process all replication request, created with {@link Event::createEvent()}
     *
     * by default, there is 5 pages in a default dokuwiki installation in the wiki namespace)
     *
     * @param int $maxEvent In case of a start or if there is a recursive bug. We don't want to take all the resources
     *
     */
    public static function dispatchEvent(int $maxEvent = 10)
    {
        $comboFunctionName = 'ComboDispatchEvent';
        print "$comboFunctionName(): Trying to get a lock" . NL;
        $lock = self::getLock();
        try {
            $lock->acquire();
        } catch (ExceptionTimeOut $e) {
            // process running
            return;
        }
        print "$comboFunctionName(): Locked" . NL;

        try {
            try {
                $sqlite = Sqlite::createOrGetBackendSqlite();
            } catch (ExceptionSqliteNotAvailable $e) {
                LogUtility::error("Sqlite is mandatory for asynchronous event", self::CANONICAL, $e);
                return;
            }


            $rows = [];
            /**
             * Returning clause
             * does not work
             */
            $version = $sqlite->getVersion();
            if ($version > "3.35.0") {

                // returning clause is available since 3.35 on delete
                // https://www.sqlite.org/lang_returning.html

                $eventTableName = self::EVENT_TABLE_NAME;
                $statement = "delete from {$eventTableName} returning *";
                // https://www.sqlite.org/lang_delete.html#optional_limit_and_order_by_clauses
                if ($sqlite->hasOption("SQLITE_ENABLE_UPDATE_DELETE_LIMIT")) {
                    $statement .= "order by timestamp limit {$maxEvent}";
                }
                $request = $sqlite->createRequest()
                    ->setStatement($statement);
                try {
                    $rows = $request->execute()
                        ->getRows();
                    if (sizeof($rows) === 0) {
                        return;
                    }
                } catch (ExceptionCompile $e) {
                    LogUtility::error($e->getMessage(), $e->getCanonical(), $e);
                } finally {
                    $request->close();
                }

            }

            /**
             * Error in the block before or not the good version
             * We try to get the records with a select/delete
             */
            if (sizeof($rows) === 0) {


                // technically the lock system of dokuwiki does not allow two process to run on
                // the indexer, we trust it
                $attributes = [self::EVENT_NAME_ATTRIBUTE, self::EVENT_DATA_ATTRIBUTE, DatabasePageRow::ROWID];
                $select = Sqlite::createSelectFromTableAndColumns(self::EVENT_TABLE_NAME, $attributes);
                $select .= " order by " . self::TIMESTAMP_ATTRIBUTE . " limit {$maxEvent}";
                $request = $sqlite->createRequest()
                    ->setQuery($select);

                $rowsSelected = [];
                try {
                    $rowsSelected = $request->execute()
                        ->getRows();
                    if (sizeof($rowsSelected) === 0) {
                        return;
                    }
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("Error while retrieving the event {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, $e->getCanonical());
                    return;
                } finally {
                    $request->close();
                }

                $eventTableName = self::EVENT_TABLE_NAME;
                $rows = [];
                foreach ($rowsSelected as $row) {
                    $request = $sqlite->createRequest()
                        ->setQueryParametrized("delete from $eventTableName where rowid = ? ", [$row[DatabasePageRow::ROWID]]);
                    try {
                        $changeCount = $request->execute()->getChangeCount();
                        if ($changeCount !== 1) {
                            LogUtility::msg("The delete of the event was not successful or it was deleted by another process", LogUtility::LVL_MSG_ERROR);
                        } else {
                            $rows[] = $row;
                        }
                    } catch (ExceptionCompile $e) {
                        LogUtility::msg("Error while deleting the event. Message {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, $e->getCanonical());
                        return;
                    } finally {
                        $request->close();
                    }
                }


            }


            $eventCounter = 0;
            foreach ($rows as $row) {
                $eventCounter++;
                $eventName = $row[self::EVENT_NAME_ATTRIBUTE];
                $eventData = [];
                $eventDataJson = $row[self::EVENT_DATA_ATTRIBUTE];
                if ($eventDataJson !== null) {
                    try {
                        $eventData = Json::createFromString($eventDataJson)->toArray();
                    } catch (ExceptionCompile $e) {
                        LogUtility::msg("The stored data for the event $eventName was not in the json format");
                        continue;
                    }
                }
                \dokuwiki\Extension\Event::createAndTrigger($eventName, $eventData);

                if ($eventCounter >= $maxEvent) {
                    break;
                }

            }
        } catch (\Exception $e) {
            LogUtility::internalError("An internal error has runned on event. " . $e->getMessage(), self::CANONICAL, $e);
        } finally {
            $lock->release();
            print "$comboFunctionName(): Lock Released" . NL;
        }

    }

    /**
     * Ask a replication in the background
     * @param string $name - a string with the reason
     * @param array $data
     */
    public static
    function createEvent(string $name, array $data)
    {

        try {
            $sqlite = Sqlite::createOrGetBackendSqlite();
        } catch (ExceptionSqliteNotAvailable $e) {
            LogUtility::error("Unable to create the event $name. Sqlite is not available");
            return;
        }

        /**
         * If not present
         */
        $entry = array(
            "name" => $name,
            "timestamp" => Iso8601Date::createFromNow()->toString()
        );


        $entry["data"] = Json::createFromArray($data)->toPrettyJsonString();
        $entry["data_hash"] = md5($entry["data"]);

        /**
         * Execute
         */
        $request = $sqlite->createRequest()
            ->setTableRow(self::EVENT_TABLE_NAME, $entry);
        try {
            $request->execute();
        } catch (ExceptionCompile $e) {
            LogUtility::error("Unable to create the event $name. Error:" . $e->getMessage(), self::CANONICAL, $e);
        } finally {
            $request->close();
        }


    }

    /**
     * @param $pageId
     *
     * This is equivalent to {@link TaskRunner}
     *
     * lib/exe/taskrunner.php?id='.rawurlencode($ID)
     * $taskRunner = new \dokuwiki\TaskRunner();
     * $taskRunner->run();
     *
     */
    public static function startTaskRunnerForPage($pageId)
    {
        $tmp = []; // No event data
        $tmp['page'] = $pageId;
        $evt = new \dokuwiki\Extension\Event('INDEXER_TASKS_RUN', $tmp);
        $evt->advise_before();
        $evt->advise_after();
    }


    public static function getQueue(string $eventName = null): array
    {
        try {
            $sqlite = Sqlite::createOrGetBackendSqlite();
        } catch (ExceptionSqliteNotAvailable $e) {
            LogUtility::internalError("Sqlite is not available, no events was returned", self::CANONICAL);
            return [];
        }

        /**
         * Execute
         */
        $attributes = [self::EVENT_NAME_ATTRIBUTE, self::EVENT_DATA_ATTRIBUTE, DatabasePageRow::ROWID];
        $select = Sqlite::createSelectFromTableAndColumns(self::EVENT_TABLE_NAME, $attributes);
        $request = $sqlite->createRequest();
        if (empty($eventName)) {
            $request->setQuery($select);
        } else {
            $request->setQueryParametrized($select . " where " . self::EVENT_NAME_ATTRIBUTE . " = ?", [$eventName]);
        }
        try {
            return $request->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            LogUtility::internalError("Unable to get the queue. Error:" . $e->getMessage(), self::CANONICAL, $e);
            return [];
        } finally {
            $request->close();
        }

    }

    /**
     * @throws ExceptionCompile
     */
    public static function purgeQueue(): int
    {
        $sqlite = Sqlite::createOrGetBackendSqlite();
        if ($sqlite === null) {
            throw new ExceptionCompile("Sqlite is not available");
        }


        /**
         * Execute
         */
        /** @noinspection SqlWithoutWhere */
        $request = $sqlite->createRequest()
            ->setQuery("delete from " . self::EVENT_TABLE_NAME);
        try {
            return $request->execute()
                ->getChangeCount();
        } catch (ExceptionCompile $e) {
            throw new ExceptionCompile("Unable to count the number of event in the queue. Error:" . $e->getMessage(), self::CANONICAL, 0, $e);
        } finally {
            $request->close();
        }
    }

    /**
     * @throws ExceptionCompile
     */
    public static function getEvents(string $eventName): array
    {
        return Event::getQueue($eventName);
    }

    public static function getLock(): Lock
    {
        return Lock::create("combo-event");
    }


}
