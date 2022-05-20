<?php

use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\Html;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Path;
use ComboStrap\PluginUtility;

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
                 * not found
                 * we don't send an login error, otherwise we get a recursive problem
                 * with the icon created for the log
                 * at {@link \ComboStrap\PluginUtility::getDocumentationHyperLink()}
                 * that uses TagAttributes on test
                 *
                 * As it should never happen, we don't throw any error
                 */
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionRuntime("global ID is mandatory to get an component id", self::CANONICAL, 0, $e);
                }
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
