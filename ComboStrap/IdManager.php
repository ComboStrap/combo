<?php

namespace ComboStrap;

/**
 * A manager to return an unique id
 * for a component
 */
class IdManager
{

    const CANONICAL = "id-manager";

    /**
     * @var array
     */
    private static $idManagers;

    /**
     * @var array
     */
    private $lastIdByCanonical;

    static function getOrCreate(): IdManager
    {

        $page = Page::createPageFromRequestedPage();
        $cacheKey = $page->getDokuwikiId();
        $idManager = self::$idManagers[$cacheKey];
        if ($idManager === null) {
            // new run, delete all old cache managers
            self::$idManagers = [];
            // create
            $idManager = new IdManager();
            self::$idManagers[$cacheKey] = $idManager;
        }
        return $idManager;
    }

    /**
     * @deprecated as the id manager is scoped to the requested page id
     */
    public static function reset()
    {
    }

    public function generateNewIdForComponent(string $canonical, Path $slotPath = null): string
    {
        if ($slotPath === null) {
            try {
                $slotPath = Page::createPageFromGlobalDokuwikiId()->getPath();
            } catch (ExceptionNotFound $e) {
                /**
                 * Not found
                 *
                 * It can happen in case of ajax call.
                 *
                 */
            }
        }

        if ($slotPath !== null) {
            $slotName = $slotPath->getLastNameWithoutExtension();
            $idScope = "$canonical-$slotName";
        } else {
            $idScope = "$canonical";
        }
        $lastId = $this->lastIdByCanonical[$idScope];
        if ($lastId === null) {
            $lastId = 1;
        } else {
            $lastId = $lastId + 1;
        }
        $this->lastIdByCanonical[$idScope] = $lastId;

        return Html::toHtmlId("$idScope-$lastId");
    }

}
