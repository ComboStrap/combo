<?php

namespace ComboStrap;

use action_plugin_combo_metaprocessing;
use dokuwiki\Extension\Event;

class MetadataMutation
{
    /**
     * When the value of a metadata has changed, an event is created
     */
    public const PAGE_METADATA_MUTATION_EVENT = "PAGE_METADATA_MUTATION_EVENT";
    public const NEW_VALUE_ATTRIBUTE = "new_value";
    const OLD_VALUE_ATTRIBUTE = "old_value";
    const NAME_ATTRIBUTE = "name";
    const PATH_ATTRIBUTE = PagePath::PROPERTY_NAME;

    /**
     *
     * Metadata modification can happen:
     * * on the whole set (ie after rendering the meta on references for instance)
     * * or for scalar mutation
     *
     * This function is then used in tow places.
     *
     * @param string $attribute
     * @param $valueBefore
     * @param $valueAfter
     * @param Path $wikiPath
     * @return void
     *
     * TODO: The data is now store dependent
     *   * Can we also pass the store to decode
     *   * or do we pass just the objects
     */
    public static function notifyMetadataMutation(string $attribute, $valueBefore, $valueAfter, Path $wikiPath)
    {
        if ($valueAfter !== $valueBefore) {
            /**
             * Event
             */
            $eventData = [
                self::NAME_ATTRIBUTE => $attribute,
                self::NEW_VALUE_ATTRIBUTE => $valueAfter,
                self::OLD_VALUE_ATTRIBUTE => $valueBefore,
                self::PATH_ATTRIBUTE => $wikiPath->toAbsoluteString()
            ];
            Event::createAndTrigger(self::PAGE_METADATA_MUTATION_EVENT, $eventData);
        }
    }
}
