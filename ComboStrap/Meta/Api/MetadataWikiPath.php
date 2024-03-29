<?php


namespace ComboStrap\Meta\Api;

use ComboStrap\DataType;
use ComboStrap\ExceptionBadArgument;
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
     * @return string - the drive from where the path should be created (ie the poth string only is stored)
     */
    public abstract static function getDrive(): string;


    /**
     * @return string
     *
     * We don't extend text because the default wiki path
     * can be an image that is not a simple path but an image
     * in the resources
     *
     * We store still the image path in the store as text
     */
    public static function getDataType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }


    /**
     * @var WikiPath
     */
    protected WikiPath $value;

    /**
     * @param WikiPath|string|null $value
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
        $this->value = WikiPath::createWikiPath($value, $this->getDrive());
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


    public function setFromStoreValueWithoutException($value): Metadata
    {
        if ($value === null || trim($value) === "") {
            return $this;
        }

        if ($value instanceof WikiPath) {
            $this->value = $value;
            return $this;
        }

        $value = WikiPath::toValidAbsolutePath($value);

        $drive = $this->getDrive();
        if ($drive === WikiPath::MARKUP_DRIVE) {
            /**
             * For a Markup drive, a file path should have an extension
             * What fucked up is fucked up
             */
            try {
                $this->value = WikiPath::createMarkupPathFromPath($value);
            } catch (ExceptionBadArgument $e) {
                LogUtility::internalError("This is not a relative path, we should not get this error");
                $this->value = WikiPath::createFromPath($value, $drive);
            }
        } else {
            $this->value = WikiPath::createFromPath($value, $drive);
        }
        return $this;

    }


    /**
     * @return WikiPath
     * @throws ExceptionNotFound
     */
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
