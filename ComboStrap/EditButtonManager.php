<?php

namespace ComboStrap;

/**
 *
 *
 */
class EditButtonManager
{


    /**
     * @var array Hold the actual edit button manager
     */
    private static $editButtonManagers;

    /**
     * @var EditButton[]
     */
    private $editButtonStack;

    static function getOrCreate(): EditButtonManager
    {

        $page = Markup::createFromRequestedPage();
        $cacheKey = $page->getWikiId();
        $editButtonManager = self::$editButtonManagers[$cacheKey];
        if ($editButtonManager === null) {
            // new run, delete all old cache managers
            self::$editButtonManagers = [];
            // create
            $editButtonManager = new EditButtonManager();
            self::$editButtonManagers[$cacheKey] = $editButtonManager;
        }
        return $editButtonManager;
    }

    /**
     * @param $name
     * @param $startPosition
     * @return EditButton
     */
    public function createAndAddEditButtonToStack($name, $startPosition): EditButton
    {


        if (empty($startPosition)) {
            LogUtility::msg("The position for a start section should not be empty", LogUtility::LVL_MSG_ERROR, "support");
        }
        if (empty($name)) {
            LogUtility::msg("The name for a start section should not be empty", LogUtility::LVL_MSG_ERROR, "support");
        }

        $editButton = EditButton::create($name)
            ->setStartPosition($startPosition);
        $this->editButtonStack[] = $editButton;
        return $editButton;

    }

    public function popEditButtonFromStack($endPosition): ?EditButton
    {
        $editButton = array_pop($this->editButtonStack);
        $editButton->setEndPosition($endPosition);
        return $editButton;
    }

}
