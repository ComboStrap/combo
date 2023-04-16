<?php

namespace ComboStrap;

/**
 * First class to migrate to a combo file system implementation
 * in the database
 *
 * The function are here to locate where the code should go.
 */
class ComboFs
{

    const CANONICAL = "combo-file-system";

    /**
     * @param Path $path - the path (no more markup, ultimately, it should be a CombPath)
     * @return void
     */
    public static function createIfNotExists(Path $path)
    {

        $markup = MarkupPath::createPageFromPathObject($path);
        try {
            $databasePage = new DatabasePageRow();
            try {
                 $databasePage->getDatabaseRowFromPage($markup);
            } catch (ExceptionNotExists $e) {
                $pageId = PageId::generateAndStorePageId($markup);
                $databasePage->upsertAttributes([PageId::getPersistentName() => $pageId]);
            }
        } catch (ExceptionCompile $e) {
            throw new ExceptionRuntimeInternal("Unable to store the page id in the database. Message:" . $e->getMessage(), self::CANONICAL, 1, $e);
        }
    }

    public static function delete(Path $path)
    {
        $markup = MarkupPath::createPageFromPathObject($path);
        try {
            DatabasePageRow::getOrCreateFromPageObject($markup)
                ->deleteIfExist();
        } catch (ExceptionSqliteNotAvailable $e) {
            //
        }
    }
}
