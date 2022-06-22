<?php


namespace ComboStrap;

/**
 * Class ThirdMediaLink
 * @package ComboStrap
 *
 */
class ThirdMediaLink extends MediaLink
{


    public function renderMediaTag(): string
    {

        $mediaMarkup = $this->mediaMarkup;
        $tagAttributes = $this->mediaMarkup->getTagAttributes();

        $urlString = $mediaMarkup->getFetchUrl()->toString();
        $path = $mediaMarkup->getPath();
        $tagAttributes->addOutputAttributeValue("href", $urlString);

        try {
            $label = $mediaMarkup->getLabel();
        } catch (ExceptionNotFound $e) {
            $label = $path->getLastName();
        }
        $tagAttributes->addOutputAttributeValue("title", $label);

        // dokuwiki class
        $tagAttributes
            ->addClassName("media")
            ->addClassName("mediafile")
            ->addClassName("wikilink2");
        try {
            // dokuwiki icon
            $extension = FileSystems::getMime($path);
            $tagAttributes->addClassName("mf_$extension");
        } catch (ExceptionNotFound $e) {
            LogUtility::warning("No icon could be added to the media link. Error: {$e->getMessage()}");
        }

        if (!FileSystems::exists($path)) {
            $tagAttributes->addClassName(LinkMarkup::getHtmlClassNotExist());
        }

        return $tagAttributes->toHtmlEnterTag("a") . $label . "</a>";

    }


    /**
     */
    public function getFetchUrl(): Url
    {


        $path = $this->mediaMarkup->getPath();
        if(!$path instanceof DokuPath){
            return $this->mediaMarkup->getFetchUrl();
        }

        try {
            $mime = FileSystems::getMime($path);
        } catch (ExceptionNotFound $e) {
            return parent::getFetchUrl();
        }

        switch ($mime->toString()) {
            case Mime::PDF:
                try {
                    return (new FetcherPdf())
                        ->buildFromUrl($this->mediaMarkup->getFetchUrl())
                        ->getFetchUrl();
                } catch (ExceptionBadArgument $e) {
                    LogUtility::internalError($e->getMessage());
                    return FetcherLocalPath::createFromPath($path)
                        ->getFetchUrl();
                }
            default:
                return FetcherLocalPath::createFromPath($path)
                    ->getFetchUrl();
        }


    }


}
