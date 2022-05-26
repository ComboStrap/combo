<?php


namespace ComboStrap;


abstract class PathAbs implements Path
{


    public function getExtension()
    {
        return pathinfo($this->getLastName(), PATHINFO_EXTENSION);
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

    public function getLastNameWithoutExtension()
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
        return $this->toString();
    }

    /**
     * @throws ExceptionCompile
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
