<?php


namespace ComboStrap\Meta\Api;

use ComboStrap\DataType;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LogUtility;
use ComboStrap\WikiPath;

/**
 * Class MetadataWikiPath
 * @package ComboStrap
 * A wiki path value where the separator is a {@link WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT}
 *

 */
abstract class MetadataWikiPath extends Metadata
{

    /**
     * @return string
     *
     * We don't extend text because the default wiki path
     * can be an image that is not a simple path but an image
     * in the resources
     *
     * We store still the image path in the store as text
     */
    public function getDataType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }


    /**
     * @var WikiPath
     */
    protected WikiPath $value;

    /**
     * @param string|null $value
     * @return Metadata
     */
    public function setValue($value): Metadata
    {
        if ($value === null) {
            return $this;
        }

        if ($value instanceof WikiPath) {
            $this->value = $value;
            return $this;
        }

        if ($value === "" || $value === ":") {
            // form send empty string
            // for the root `:`, non canonical
            return $this;
        }
        $value = WikiPath::toValidAbsolutePath($value);
        $this->value = WikiPath::createMediaPathFromPath($value);
        return $this;
    }


    public function toStoreValue()
    {
        try {
            /**
             * {@link self::getValue()} because
             * it may be overwritten by derived metadata
             */
            $actualPath = $this->getValue();
        } catch (ExceptionNotFound $e) {
            return null;
        }
        /**
         * The absolute id with the extension if not a wiki file
         */
        return $actualPath->toAbsoluteId();
    }

    public function toStoreDefaultValue()
    {
        try {
            $defaultValue = $this->getDefaultValue();
            if (!($defaultValue instanceof WikiPath)) {
                LogUtility::internalError("The value ($defaultValue) is not a wiki path");
                return $defaultValue;
            }
            return $defaultValue->toAbsoluteId();
        } catch (ExceptionNotFound $e) {
            return null;
        }
    }


    public function buildFromStoreValue($value): Metadata
    {
        if ($value === null) {
            return $this;
        }

        if ($value instanceof WikiPath) {
            $this->value = $value;
            return $this;
        }

        if ($value !== "") {
            $value = WikiPath::toValidAbsolutePath($value);
        }
        $this->value = WikiPath::createMediaPathFromPath($value);
        return $this;

    }


    public function getValue(): WikiPath
    {
        $this->buildCheck();
        if (isset($this->value)) {
            return $this->value;
        }
        throw new ExceptionNotFound("No value found");
    }

    public function valueIsNotNull(): bool
    {
        return isset($this->value);
    }


    public function getDefaultValue()
    {
        throw new ExceptionNotFound("No default value");
    }

}
