<?php


namespace ComboStrap;


abstract class PathAbs implements Path
{


    /**
     * @throws ExceptionNotFound
     */
    public function getExtension(): string
    {
        $extension = pathinfo($this->getLastName(), PATHINFO_EXTENSION);
        if ($extension === "") {
            throw new ExceptionNotFound("No extension found");
        }
        return $extension;

    }

    /**
     * @return Mime based on the {@link PathAbs::getExtension()}
     * @deprecated see {@link FileSystems::getMime()}
     */
    public function getMime(): ?Mime
    {
        try {
            return FileSystems::getMime($this);
        } catch (ExceptionNotFound $e) {
            return null;
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getLastNameWithoutExtension(): string
    {
        $lastName = $this->getLastName();
        return pathinfo($lastName, PATHINFO_FILENAME);
    }

    /**
     *
     */
    public function getNamesWithoutExtension()
    {
        $names = $this->getNames();
        $sizeof = sizeof($names);
        if ($sizeof == 0) {
            return $names;
        }
        $lastName = $names[$sizeof - 1];
        $index = strrpos($lastName, ".");
        if ($index === false) {
            return $names;
        }
        $names[$sizeof - 1] = substr($lastName, 0, $index);
        return $names;
    }

    public function __toString()
    {
        return $this->toUriString();
    }


    public function toUriString(): string
    {
        return $this->toQualifiedId();
    }

    /**
     * @throws ExceptionCast when
     * Utility {@link WikiPath::createFromPathObject()}
     */
    function toWikiPath(): WikiPath
    {
        if ($this instanceof WikiPath) {
            return $this;
        }
        if ($this instanceof LocalPath) {
            try {
                return $this->toWikiPath();
            } catch (ExceptionBadArgument|ExceptionCast $e) {
                throw new ExceptionCast($e);
            }
        }
        if ($this instanceof MarkupPath) {
            try {
                return $this->getPathObject()->toWikiPath();
            } catch (ExceptionCast $e) {
                throw new ExceptionCast($e);
            }
        }
        throw new ExceptionCast("This is not a wiki path or local path");
    }

    /**
     * @throws ExceptionCast when
     */
    function toLocalPath(): LocalPath
    {
        if ($this instanceof LocalPath) {
            return $this;
        }
        if ($this instanceof WikiPath) {
            return $this->toLocalPath();
        }
        throw new ExceptionCast("Unable to cast to LocalPath as this path is not a wiki path or a local path but a " . get_class($this));
    }


}
