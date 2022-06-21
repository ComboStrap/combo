<?php

namespace ComboStrap;

class Icon
{

    public const ICON_CANONICAL_NAME = "icon";

    private TagAttributes $tagAttributes;
    private FetchSvg $fetchSvg;

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public static function createFromName(string $name, TagAttributes $iconAttributes = null): Icon
    {
        if ($iconAttributes === null) {
            $iconAttributes = TagAttributes::createEmpty(self::ICON_CANONICAL_NAME);
        }
        $iconAttributes->addComponentAttributeValue(FetchSvg::NAME_ATTRIBUTE, $name);
        return self::createFromTagAttributes($iconAttributes);
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public static function createFromTagAttributes(TagAttributes $tagAttributes): Icon
    {
        /**
         * The svg
         * Adding the icon type is mandatory if there is no media
         */
        $tagAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY,FetchSvg::ICON_TYPE);

        /**
         * Icon Svg file or Icon Library
         */
        $name = $tagAttributes->getValue(FetchSvg::NAME_ATTRIBUTE);
        if($name===null){
            throw new ExceptionNotFound("A name is mandatory as attribute for an icon. It was not found.", Icon::ICON_CANONICAL_NAME);
        }

        /**
         * If the name have an extension, it's a file from the media directory
         * Otherwise, it's an icon from a library
         */
        $mediaDokuPath = DokuPath::createMediaPathFromId($name);
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

            $tagAttributes->addComponentAttributeValue(FetchRaw::MEDIA_QUERY_PARAMETER, $mediaDokuPath->getDokuwikiId());
            $tagAttributes->setComponentAttributeValue(FetchSvg::NAME_ATTRIBUTE,$mediaDokuPath->getLastNameWithoutExtension());


        } catch (ExceptionNotFound $e) {

            /**
             * No file extension
             * From an icon library
             */

        }
        $fetchSvg = FetchSvg::createFromAttributes($tagAttributes);

        return (new Icon())
            ->setFetchSvg($fetchSvg)
            ->setTagAttributes($tagAttributes);
    }

    /**
     * @throws ExceptionCompile
     */
    public static function createFromComboResource(string $name, TagAttributes $tagAttributes = null): Icon
    {
        $icon = new Icon();
        $path = DokuPath::createComboResource(":$name.svg");
        $fetchSvg = FetchSvg::createSvgFromPath($path);
        $icon->setFetchSvg($fetchSvg);
        if ($tagAttributes !== null) {
            $icon->setTagAttributes($tagAttributes);
        }
        return $icon;
    }

    public function setFetchSvg(FetchSvg $fetchSvg): Icon
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
            ->setTagAttributes($this->tagAttributes);

        return SvgImageLink::createFromMediaMarkup($mediaMarkup)
            ->renderMediaTag();


    }

    public function getFetchSvg(): FetchSvg
    {
        return $this->fetchSvg;
    }
}
