<?php

use ComboStrap\ExceptionNotFound;
use ComboStrap\Html;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Path;

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

    public static function reset()
    {
        self::$idManagers = null;
    }

    public function generateNewIdForComponent(string $canonical, Path $slotPath = null): string
    {
        if(empty($slotPath)) {
            try {
                $slotName = Page::createPageFromGlobalDokuwikiId()->getPath()->getLastName();
            } catch (ExceptionNotFound $e) {
                LogUtility::warning(self::CANONICAL . " - The global ID was not found, the slot seen was set to unknown");
                $slotName = "unknown-slot";
            }
        } else {
            $slotName = $slotPath->getLastName();
        }

        if($slotName!==null) {
            // root
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
