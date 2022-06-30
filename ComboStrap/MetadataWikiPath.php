<?php


namespace ComboStrap;

/**
 * Class MetadataWikiPath
 * @package ComboStrap
 * A wiki path value where the separator is a {@link WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT}
 */
abstract class MetadataWikiPath extends MetadataText
{

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionCompile
     */
    public function setValue($value): Metadata
    {
        if ($value === null) {
            parent::setValue($value);
            return $this;
        }
        if ($value === "" || $value === ":") {
            // form send empty string
            // for the root `:`, non canonical
            return $this;
        }

        $value = WikiPath::toValidAbsolutePath($value);
        parent::setValue($value);
        return $this;
    }

    /**
     */
    public function buildFromStoreValue($value): Metadata
    {
        if ($value !== null && $value !== "") {
            $value = WikiPath::toValidAbsolutePath($value);
        }
        parent::buildFromStoreValue($value);
        return $this;
    }


}
