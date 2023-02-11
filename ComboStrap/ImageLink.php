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
            return $this->mediaMarkup->getFetcher()->getLabel();
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
        $tagAttributes = $this->mediaMarkup->getExtraMediaTagAttributes()
            ->setLogicalTag("img-link");
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
        $fetcher = $this->mediaMarkup->getFetcher();
        if (!($fetcher instanceof IFetcherSource)) {
            // not an internal image
            return $htmlMediaMarkup;
        }

        $isImage = $fetcher->getMime()->isImage();
        if (!$isImage) {
            return $htmlMediaMarkup;
        }

        $dokuPath = $fetcher->getSourcePath();
        try {
            $linking = $this->mediaMarkup->getLinking();
        } catch (ExceptionNotFound $e) {
            return $htmlMediaMarkup;
        }
        switch ($linking) {
            case MediaMarkup::LINKING_LINKONLY_VALUE:
                // show only a url, no image
                $href = FetcherRawLocalPath::createFromPath($dokuPath)->getFetchUrl()->toString();
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
                $href = FetcherRawLocalPath::createFromPath($dokuPath)->getFetchUrl()->toString();
                $tagAttributes->addOutputAttributeValue("href", $href);
                $snippetId = "lightbox";
                $tagAttributes->addClassName(StyleUtility::addComboStrapSuffix($snippetId));
                $linkingClass = $this->mediaMarkup->getLinkingClass();
                if ($linkingClass !== null) {
                    $tagAttributes->addClassName($linkingClass);
                }
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->attachJavascriptComboLibrary();
                $snippetManager->attachJavascriptFromComponentId($snippetId);
                $snippetManager->attachCssInternalStyleSheet($snippetId);
                return $tagAttributes->toHtmlEnterTag("a") . $htmlMediaMarkup . "</a>";

            case MediaMarkup::LINKING_DETAILS_VALUE:
                //go to the details media viewer
                $url = UrlEndpoint::createDetailUrl()
                    ->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $dokuPath->getWikiId())
                    ->addQueryParameter(WikiPath::REV_ATTRIBUTE, $dokuPath->getRevision());
                $tagAttributes->addOutputAttributeValue("href", $url->toString());
                return $tagAttributes->toHtmlEnterTag("a") . $htmlMediaMarkup . "</a>";

        }


    }

}
