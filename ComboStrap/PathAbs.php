<?php


namespace ComboStrap;


abstract class PathAbs implements Path
{


    public function getExtension()
    {
        return pathinfo($this->getLastName(), PATHINFO_EXTENSION);
    }

    /**
     *
     * @return Mime based on the {@link PathAbs::getExtension()}
     */
    public function getMime(): Mime
    {
        switch ($this->getExtension()) {
            case ImageSvg::EXTENSION:
                /**
                 * Svg is authorized when viewing but is not part
                 * of the {@link File::getKnownMime()}
                 */
                return new Mime(Mime::SVG);
            case JavascriptLibrary::EXTENSION:
                return new Mime(Mime::JAVASCRIPT);
            case Json::EXTENSION:
                return new Mime(Mime::JSON);
            default:
                return new Mime(mimetype($this->getLastName(), false)[1]);
        }
    }

    public function getLastNameWithoutExtension()
    {
        return pathinfo($this->getLastName(), PATHINFO_FILENAME);
    }

    public function __toString()
    {
        return $this->toString();
    }


}
