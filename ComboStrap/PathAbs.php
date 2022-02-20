<?php


namespace ComboStrap;


use renderer_plugin_combo_analytics;

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
    public function getMime(): ?Mime
    {
        $extension = $this->getExtension();
        switch ($extension) {
            case ImageSvg::EXTENSION:
                /**
                 * Svg is authorized when viewing but is not part
                 * of the {@link File::getKnownMime()}
                 */
                return new Mime(Mime::SVG);
            case JavascriptLibrary::EXTENSION:
                return new Mime(Mime::JAVASCRIPT);
            case renderer_plugin_combo_analytics::RENDERER_NAME_MODE:
            case Json::EXTENSION:
                return new Mime(Mime::JSON);
            case "txt":
                return new Mime(Mime::PLAIN_TEXT);
            case "xhtml":
            case "html":
                return new Mime(Mime::HTML);
            case "png":
                return new Mime(Mime::PNG);
            case "css":
                return new Mime(Mime::CSS);
            default:
                $mime = mimetype($this->getLastName(), true)[1];
                if ($mime === null || $mime === false) {
                    return null;
                }
                return new Mime($mime);
        }
    }

    public function getLastNameWithoutExtension()
    {
        return pathinfo($this->getLastName(), PATHINFO_FILENAME);
    }

    public function __toString()
    {
        return $this->toUriString();
    }


    public function toUriString(): string
    {
        return $this->toString();
    }

    /**
     * @throws ExceptionCombo
     */
    function toDokuPath(): DokuPath
    {
        if($this instanceof DokuPath){
            return $this;
        }
        if($this instanceof LocalPath){
            return $this->toDokuPath();
        }
        throw new ExceptionCombo("This is not a doku path or local path");
    }


}
