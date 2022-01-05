<?php

namespace ComboStrap;

/**
 * The manager that handles the redirection metadata
 *
 */
class PageRules
{

    // Name of the column
    // Used also in the HTML form as name
    const ID_NAME = 'ID';
    const PRIORITY_NAME = 'PRIORITY';
    const MATCHER_NAME = 'MATCHER';
    const TARGET_NAME = 'TARGET';
    const TIMESTAMP_NAME = 'TIMESTAMP';


    /**
     * Delete Redirection
     * @param string $ruleId
     */
    function deleteRule(string $ruleId)
    {

        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized('delete from PAGE_RULES where id = ?', $ruleId);
        try {
            $request->execute();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Something went wrong when deleting the redirections. {$e->getMessage()}");
        } finally {
            $request->close();
        }

    }


    /**
     * Is Redirection of a page Id Present
     * @param integer $id
     * @return boolean
     */
    function ruleExists($id): bool
    {
        $id = strtolower($id);


        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized("SELECT count(*) FROM PAGE_RULES where ID = ?", [$id]);
        $count = 0;
        try {
            $count = $request
                ->execute()
                ->getFirstCellValueAsInt();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error during pattern exist statement. {$e->getMessage()}");
            return false;
        } finally {
            $request->close();
        }

        return $count === 1;


    }

    /**
     * Is Redirection of a page Id Present
     * @param string $pattern
     * @return boolean
     */
    function patternExists(string $pattern): bool
    {


        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized("SELECT count(*) FROM PAGE_RULES where MATCHER = ?", [$pattern]);
        $count = 0;
        try {
            $count = $request->execute()
                ->getFirstCellValueAsInt();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error during pattern exists query: {$e->getMessage()}");
            return false;
        } finally {
            $request->close();
        }

        return $count === 1;


    }


    /**
     * @param $sourcePageId
     * @param $targetPageId
     * @param $priority
     * @return int - the rule id
     */
    function addRule($sourcePageId, $targetPageId, $priority)
    {
        $currentDate = date("c");
        return $this->addRuleWithDate($sourcePageId, $targetPageId, $priority, $currentDate);
    }

    /**
     * Add Redirection
     * This function was needed to migrate the date of the file conf store
     * You would use normally the function addRedirection
     * @param string $matcher
     * @param string $target
     * @param $priority
     * @param $creationDate
     * @return int - the last id
     */
    function addRuleWithDate($matcher, $target, $priority, $creationDate): ?int
    {

        $entry = array(
            'target' => $target,
            'timestamp' => $creationDate,
            'matcher' => $matcher,
            'priority' => $priority
        );

        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setTableRow('PAGE_RULES', $entry);
        $lastInsertId = null;
        try {
            $lastInsertId = $request->execute()
                ->getInsertId();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("There was a problem during Pages Rule insertion. " . $e->getMessage());
            return null;
        } finally {
            $request->close();
        }

        return $lastInsertId;

    }

    function updateRule($id, $matcher, $target, $priority)
    {
        $updateDate = date("c");

        $entry = array(
            'matcher' => $matcher,
            'target' => $target,
            'priority' => $priority,
            'timestamp' => $updateDate,
            'íd' => $id
        );

        $statement = 'update PAGE_RULES set matcher = ?, target = ?, priority = ?, timestamp = ? where id = ?';
        $res = $this->sqlite->query($statement, $entry);
        if (!$res) {
            LogUtility::msg("There was a problem during the update");
        }
        $this->sqlite->res_close($res);

    }


    /**
     * Delete all rules
     * Use with caution
     */
    function deleteAll()
    {

        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQuery("delete from PAGE_RULES");
        try {
            $request->execute();
        } catch (ExceptionCombo $e) {
            LogUtility::msg('Errors during delete of all redirections. ' . $e->getMessage());
        } finally {
            $request->close();
        }


    }

    /**
     * Return the number of page rules
     * @return integer
     */
    function count()
    {

        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQuery("select count(1) from PAGE_RULES");

        $count = 0;
        try {
            $count = $request->execute()
                ->getFirstCellValueAsInt();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Page Rules Count. {$e->getMessage()}");
            return 0;
        } finally {
            $request->close();
        }

        return $count;

    }


    /**
     * @return array
     */
    function getRules()
    {

        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQuery("select * from PAGE_RULES order by PRIORITY asc");

        try {
            return $request->execute()
                ->getRows();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Errors during select of all Page rules. {$e->getMessage()}");
            return [];
        } finally {
            $request->close();
        }


    }

    public function getRule($id): array
    {
        $request = Sqlite::createOrGetSqlite()
            ->createRequest()
            ->setQueryParametrized("SELECT * FROM PAGE_RULES where ID = ?", [$id]);
        try {
            return $request->execute()
                ->getFirstRow();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("getRule Error {$e->getMessage()}");
            return [];
        } finally {
            $request->close();
        }

    }


}
