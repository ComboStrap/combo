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

        $page = PageFragment::createFromRequestedPage();
        $cacheKey = $page->getWikiId();
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

    public function generateNewHtmlIdForComponent(string $canonical, Path $slotPath = null): string
    {

        if ($slotPath === null) {
            try {
                $slotPath = PageFragment::createPageFromGlobalWikiId()->getPath();
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
        $lastId = self::generateAndGetNewSequenceValueForScope($idScope);

        return Html::toHtmlId("$idScope-$lastId");
    }

    public function generateAndGetNewSequenceValueForScope(string $scope)
    {

        $lastId = $this->lastIdByCanonical[$scope];
        if ($lastId === null) {
            $lastId = 1;
        } else {
            $lastId = $lastId + 1;
        }
        $this->lastIdByCanonical[$scope] = $lastId;
        return $lastId;

    }

}
