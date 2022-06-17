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

    public function __toString()
    {
        return $this->toUriString();
    }


    public function toUriString(): string
    {
        return $this->toPathString();
    }

    /**
     * @throws ExceptionCompile
     * @deprecated for {@link DokuPath::createFromPath()}
     */
    function toDokuPath(): DokuPath
    {
        if ($this instanceof DokuPath) {
            return $this;
        }
        if ($this instanceof LocalPath) {
            return $this->toDokuPath();
        }
        throw new ExceptionCompile("This is not a doku path or local path");
    }


}
