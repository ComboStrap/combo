<?php


namespace ComboStrap;

/**
 * Class MetadataWikiPath
 * @package ComboStrap
 * A wiki path value where the separator is a {@link DokuPath::PATH_SEPARATOR}
 */
abstract class MetadataWikiPath extends MetadataText
{

    public function setValue(?string $value): MetadataText
    {
        if ($value === "" || $value === ":") {
            // form send empty string
            // for the root `:`, non canonical
            $value = null;
        } else {
            $value = DokuPath::toValidAbsolutePath($value);
        }
        parent::setValue($value);
        return $this;
    }

    /**
     */
    public function buildFromStoreValue($value): Metadata
    {
        if ($value !== null && $value !=="") {
            $value = DokuPath::toValidAbsolutePath($value);
        }
        parent::buildFromStoreValue($value);
        return $this;
    }




}
