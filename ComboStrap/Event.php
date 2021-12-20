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

    const CANONICAL = "support";
    const EVENT_NAME_ATTRIBUTE = "name";
    const EVENT_DATA_ATTRIBUTE = "data";

    /**
     * process all replication request, created with {@link Event::createEvent()}
     *
     * by default, there is 5 pages in a default dokuwiki installation in the wiki namespace)
     */
    public static function dispatchEvent($maxEvent = 10)
    {

        $sqlite = Sqlite::createOrGetBackendSqlite();
        $tableName = self::EVENT_TABLE_NAME;
        $request = $sqlite->createRequest()
            ->setQuery("SELECT ID FROM $tableName");

        $rows = null;
        try {
            $result = $request->execute();
            $rows = $result->getRows();
            if (sizeof($rows) === 0) {
                return;
            }
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        } finally {
            $request->close();
        }

        /**
         * In case of a start or if there is a recursive bug
         * We don't want to take all the resources
         */
        $maxBackgroundEventLow = 2;
        $events = sizeof($rows);
        if ($events > $maxEvent) {
            $table = self::EVENT_TABLE_NAME;
            LogUtility::msg("There is {$events} background event in the queue (table `{$table}`). This is more than {$maxEvent} pages. Batch event background was reduced to {$maxBackgroundEventLow} to not hit the computer resources.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            $maxEvent = $maxBackgroundEventLow;
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
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("The stored data for the event $eventName was not in the json format");
                    continue;
                }
            }
            \dokuwiki\Extension\Event::createAndTrigger($eventName, $eventData);

            if ($eventCounter >= $maxEvent) {
                break;
            }

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
        $sqlite = Sqlite::createOrGetBackendSqlite();
        if ($sqlite === null) {
            LogUtility::msg("Unable to create the event $name. Sqlite is not available");
            return;
        }


        /**
         * If not present
         */
        $entry = array(
            "event" => $name,
            "timestamp" => Iso8601Date::createFromNow()->toString()
        );

        if ($data !== null) {
            $entry["data"] = Json::createFromArray($data)->toPrettyJsonString();
        }

        /**
         * Execute
         */
        $request = $sqlite->createRequest()
            ->storeIntoTable(self::EVENT_TABLE_NAME, $entry);
        try {
            $request->execute();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Unable to create the event $name. Error:" . $e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        } finally {
            $request->close();
        }


    }
}
