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
            try {
                $name = $path->getLastNameWithoutExtension();
            } catch (ExceptionNotFound $e) {
                try {
                    $name = $path->getHost();
                } catch (ExceptionNotFound $e) {
                    $name = "Unknown";
                }
            }
            $generatedAlt = str_replace("-", " ", $name);
            return str_replace("_", " ", $generatedAlt);
        }

    }


}
