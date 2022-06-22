<?php


namespace ComboStrap;


/**
 * Class ImageLink
 * @package ComboStrap
 *
 * A media of image type
 */
abstract class ImageLink extends MediaLink
{


    /**
     * This is mandatory for HTML
     * The alternate text (the title in Dokuwiki media term)
     *
     *
     * TODO: Try to extract it from the metadata file ?
     *
     * An img element must have an alt attribute, except under certain conditions.
     * For details, consult guidance on providing text alternatives for images.
     * https://www.w3.org/WAI/tutorials/images/
     */
    public function getAltNotEmpty(): string
    {
        try {
            return $this->mediaMarkup->getLabel();
        } catch (ExceptionNotFound $e) {
            $path = $this->mediaMarkup->getPath();
            return ResourceName::getFromPath($path);
        }

    }

    /**
     * @return string - the HTML of the image inside a link if asked
     * @throws ExceptionNotFound
     */
    public
    function wrapMediaMarkupWithLink(string $htmlMediaMarkup): string
    {

        /**
         * Link to the media
         *
         */
        $tagAttributes = $this->mediaMarkup->getTagAttributes();
        // https://www.dokuwiki.org/config:target
        global $conf;
        $target = $conf['target']['media'];
        $tagAttributes->addOutputAttributeValueIfNotEmpty("target", $target);
        if (!empty($target)) {
            $tagAttributes->addOutputAttributeValue("rel", 'noopener');
        }

        /**
         * Do we add a link to the image ?
         */
        $dokuPath = $this->mediaMarkup->getPath();
        if (!($dokuPath instanceof DokuPath)) {
            // not an internal image
            return $htmlMediaMarkup;
        }
        try {
            $isImage = FileSystems::getMime($dokuPath)->isImage();
            if (!$isImage) {
                return $htmlMediaMarkup;
            }
        } catch (ExceptionNotFound $e) {
            LogUtility::warning("A media link could not be added. Error:{$e->getMessage()}");
            return $htmlMediaMarkup;
        }


        try {
            $linking = $this->mediaMarkup->getLinking();
        } catch (ExceptionNotFound $e) {
            return $htmlMediaMarkup;
        }
        switch ($linking) {
            case MediaMarkup::LINKING_LINKONLY_VALUE:
                // show only a url, no image
                $href = FetcherLocalPath::createFromPath($dokuPath)->getFetchUrl()->toString();
                $tagAttributes->addOutputAttributeValue("href", $href);
                try {
                    $title = $this->mediaMarkup->getLabel();
                } catch (ExceptionNotFound $e) {
                    $title = $dokuPath->getLastName();
                }
                return $tagAttributes->toHtmlEnterTag("a") . $title . "</a>";
            case MediaMarkup::LINKING_NOLINK_VALUE:
                // show only a the image
                return $htmlMediaMarkup;
            default:
            case MediaMarkup::LINKING_DIRECT_VALUE:
                //directly to the image
                $href = FetcherLocalPath::createFromPath($dokuPath)->getFetchUrl()->toString();
                $tagAttributes->addOutputAttributeValue("href", $href);
                $snippetId = "lightbox";
                $tagAttributes->addClassName(StyleUtility::getStylingClassForTag($snippetId));
                $linkingClass = $this->mediaMarkup->getLinkingClass();
                if ($linkingClass !== null) {
                    $tagAttributes->addClassName($linkingClass);
                }
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->attachJavascriptComboLibrary();
                $snippetManager->attachInternalJavascriptForSlot($snippetId);
                $snippetManager->attachCssInternalStyleSheetForSlot($snippetId);
                return $tagAttributes->toHtmlEnterTag("a") . $htmlMediaMarkup . "</a>";

            case MediaMarkup::LINKING_DETAILS_VALUE:
                //go to the details media viewer
                $url = UrlEndpoint::createDetailUrl()
                    ->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $dokuPath->getDokuwikiId())
                    ->addQueryParameter(DokuPath::REV_ATTRIBUTE, $dokuPath->getRevision());
                $tagAttributes->addOutputAttributeValue("href", $url->toString());
                return $tagAttributes->toHtmlEnterTag("a") . $htmlMediaMarkup . "</a>";

        }


    }

}
