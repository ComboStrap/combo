<?php

namespace ComboStrap;

class Icon
{

    public const ICON_CANONICAL_NAME = "icon";

    private TagAttributes $tagAttributes;
    private FetcherSvg $fetchSvg;

    /**
     * @param string $name
     * @param TagAttributes|null $iconAttributes
     * @return Icon
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionCompile
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public static function createFromName(string $name, TagAttributes $iconAttributes = null): Icon
    {
        if ($iconAttributes === null) {
            $iconAttributes = TagAttributes::createEmpty(self::ICON_CANONICAL_NAME);
        }
        $iconAttributes->addComponentAttributeValue(FetcherSvg::NAME_ATTRIBUTE, $name);
        return self::createFromTagAttributes($iconAttributes);
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     * @throws ExceptionCompile
     */
    public static function createFromTagAttributes(TagAttributes $tagAttributes): Icon
    {
        /**
         * The svg
         * Adding the icon type is mandatory if there is no media
         */
        $tagAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, FetcherSvg::ICON_TYPE);

        /**
         * Icon Svg file or Icon Library
         */
        $name = $tagAttributes->getValue(FetcherSvg::NAME_ATTRIBUTE);
        if ($name === null) {
            throw new ExceptionNotFound("A name is mandatory as attribute for an icon. It was not found.", Icon::ICON_CANONICAL_NAME);
        }

        /**
         * If the name have an extension, it's a file from the media directory
         * Otherwise, it's an icon from a library
         */
        $mediaDokuPath = WikiPath::createMediaPathFromId($name);
        try {
            $extension = $mediaDokuPath->getExtension();
            if ($extension !== "svg") {
                throw new ExceptionBadArgument("The extension of the icon ($name) is not `svg`", Icon::ICON_CANONICAL_NAME);
            }
            if (!FileSystems::exists($mediaDokuPath)) {

                // Trying to see if it's not in the template images directory
                $message = "The svg icon file ($mediaDokuPath) does not exists. If you want an icon from an icon library, indicate a name without extension.";
                throw new ExceptionNotExists($message, Icon::ICON_CANONICAL_NAME);

            }

            $tagAttributes->addComponentAttributeValue(FetcherRawLocalPath::$MEDIA_QUERY_PARAMETER, $mediaDokuPath->getWikiId());
            $tagAttributes->setComponentAttributeValue(FetcherSvg::NAME_ATTRIBUTE, $mediaDokuPath->getLastNameWithoutExtension());


        } catch (ExceptionNotFound $e) {

            /**
             * No file extension
             * From an icon library
             */

        }
        $fetcherSvg = FetcherSvg::createFromAttributes($tagAttributes);

        return (new Icon())
            ->setFetcherSvg($fetcherSvg)
            ->setTagAttributes($tagAttributes);
    }

    /**
     */
    public static function createFromComboResource(string $name, TagAttributes $tagAttributes = null): Icon
    {
        $icon = new Icon();
        $path = WikiPath::createComboResource(":$name.svg");
        $fetchSvg = FetcherSvg::createSvgFromPath($path);
        $icon->setFetcherSvg($fetchSvg);
        if ($tagAttributes !== null) {
            $icon->setTagAttributes($tagAttributes);
        }
        return $icon;
    }

    public function setFetcherSvg(FetcherSvg $fetchSvg): Icon
    {
        $this->fetchSvg = $fetchSvg;
        return $this;
    }

    private function setTagAttributes(TagAttributes $tagAttributes): Icon
    {
        $this->tagAttributes = $tagAttributes;
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public function toHtml(): string
    {


        $mediaMarkup = MediaMarkup::createFromFetcher($this->fetchSvg)
            ->setLinking(MediaMarkup::LINKING_NOLINK_VALUE); // no lightbox on icon
        if (isset($this->tagAttributes)) {
            $mediaMarkup->buildFromTagAttributes($this->tagAttributes);
        }

        return SvgImageLink::createFromMediaMarkup($mediaMarkup)
            ->renderMediaTag();


    }

    public function getFetchSvg(): FetcherSvg
    {
        return $this->fetchSvg;
    }
}
