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
     * @throws ExceptionCompile - for any error
     */
    public static function create(Path $path)
    {

        $markup = MarkupPath::createPageFromPathObject($path);
        $pageId = PageId::generateAndStorePageId($markup);
        try {
            DatabasePageRow::createFromPageObject($markup)
                ->upsertAttributes([PageId::getPersistentName() => $pageId]);
        } catch (ExceptionCompile $e) {
             throw new ExceptionCompile("Unable to store the page id in the database. Message:" . $e->getMessage(), self::CANONICAL, $e);
        }
    }

    public static function delete(Path $path)
    {
        $markup = MarkupPath::createPageFromPathObject($path);
        DatabasePageRow::createFromPageObject($markup)
            ->deleteIfExist();
    }
}
