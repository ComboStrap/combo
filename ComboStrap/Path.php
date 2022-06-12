<?php


namespace ComboStrap;

/**
 * Interface Path
 * @package ComboStrap
 *
 * An interface that represents a path.
 *
 * For the path operations, see {@link FileSystems}
 *
 * The {@link Path::toPathString()} function is just the path part (no other URI query parameters)
 *
 */
interface Path
{

    function getExtension();

    function getLastNameWithoutExtension();

    function getScheme();

    /**
     * The last name of the path with or without the extension
     *
     * The Path class does not have a notion of "extension"
     * because the file does not have one but we provide the
     * {@link PathAbs::getExtension()} as utility
     *
     * @return mixed
     */
    function getLastName();

    function getNames();

    function getParent(): ?Path;

    /**
     * @return string only the string representation of the path
     */
    function toPathString(): string;

    /**
     * @return string the uri string representation of this path (with all information, drive, attributes)
     */
    function toUriString(): string;

    function toAbsolutePath(): Path;

    /**
     * @return Mime the mime from the extension
     * @deprecated Uses {@link FileSystems::getMime()} instead
     */
    function getMime(): ?Mime;

    function resolve(string $name);

    /**
     * @return string domain
     */
    function getHost(): string;

}
