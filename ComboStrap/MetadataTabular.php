<?php


namespace ComboStrap;

/**
 * Class MetadataTabular
 * @package ComboStrap
 * A list of row represented as a list of column
 * ie an entity with a map that has a key
 */
abstract class MetadataTabular extends Metadata
{

    public function getDefaultValue()
    {
        return null;
    }


}
