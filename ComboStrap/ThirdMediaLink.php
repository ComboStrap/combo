<?php


namespace ComboStrap;

/**
 * Class ThirdMediaLink
 * @package ComboStrap
 * Not yet implemented but used to
 * returns a media link object and not null
 * otherwise, we get an error
 */
class ThirdMediaLink extends MediaLink
{


    public function renderMediaTag(): string
    {

        $mediaMarkup = $this->mediaMarkup;
        $url = $mediaMarkup->toFetchUrl();
        try {
            $label = $mediaMarkup->getLabel();
        } catch (ExceptionNotFound $e) {
            $label = $url->toString();
        }

        return "<a href=\"{$url->toAbsoluteUrlString()}\" title=\"{$label}\">{$label}</a>";

    }

    public function renderMediaTagWithLink(): string
    {
        return $this->renderMediaTag();
    }


}
