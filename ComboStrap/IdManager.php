<?php

namespace ComboStrap;

/**
 * A manager to return an unique id for a node
 *
 * Example: if you create multiple {@link PageExplorerTag}
 * you may have several tag for the same page/namespace
 * but the node id should be unique as it's used for the collapsing
 */
class IdManager
{


    const CANONICAL = "id-manager";

    /**
     * @var array
     */
    private array $lastIdByScope = [];
    private ExecutionContext $executionContext;

    /**
     * @param ExecutionContext $executionContext
     */
    public function __construct(ExecutionContext $executionContext)
    {
        $this->executionContext = $executionContext;
    }

    /**
     * @return IdManager
     * @deprecated use {@link ExecutionContext::getIdManager()} instead
     * via {@link ExecutionContext::getExecutingMarkupHandler()}
     */
    static function getOrCreate(): IdManager
    {

        return ExecutionContext::getActualOrCreateFromEnv()->getIdManager();


    }


    public function generateNewHtmlIdForComponent(string $componentId, Path $executingPath = null): string
    {

        if ($executingPath === null) {

            try {
                $executingPath = $this->executionContext
                    ->getExecutingMarkupHandler()
                    ->getExecutingPathOrNull();
            } catch (ExceptionNotFound $e) {
                // ok, dynamic, markup string run ?
            }

        }

        $idScope = $componentId;
        if ($executingPath !== null) {
            try {
                $slotName = $executingPath->getLastNameWithoutExtension();
                $idScope = "$idScope-$slotName";
            } catch (ExceptionNotFound $e) {
                // no name (ie root)
            }
        }
        $lastId = self::generateAndGetNewSequenceValueForScope($idScope);

        return Html::toHtmlId("$idScope-$lastId");
    }

    private function generateAndGetNewSequenceValueForScope(string $scope)
    {

        $lastId = $this->lastIdByScope[$scope] ?? null;
        if ($lastId === null) {
            $lastId = 1;
        } else {
            $lastId = $lastId + 1;
        }
        $this->lastIdByScope[$scope] = $lastId;
        return $lastId;

    }

}
