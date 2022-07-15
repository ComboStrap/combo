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

        $page = Markup::createFromRequestedPage();
        $cacheKey = $page->getWikiId();
        $idManager = self::$idManagers[$cacheKey];
        if ($idManager === null) {
            // new run, delete all old cache managers
            self::reset();
            // create
            $idManager = new IdManager();
            self::$idManagers[$cacheKey] = $idManager;
        }
        return $idManager;
    }

    /**
     * We may test two run with the same id
     * Even if the id manager is scoped to the requested page id, we need to have a reset
     */
    public static function reset()
    {
        self::$idManagers = [];
    }

    public function generateNewHtmlIdForComponent(string $canonical, Path $slotPath = null): string
    {

        if ($slotPath === null) {

            $slotPath = Markup::createPageFromGlobalWikiId()->getPathObject();

        }

        $idScope = $canonical;
        if ($slotPath !== null) {
            try {
                $slotName = $slotPath->getLastNameWithoutExtension();
                $idScope = "$idScope-$slotName";
            } catch (ExceptionNotFound $e) {
                // no name (ie root)
            }
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
