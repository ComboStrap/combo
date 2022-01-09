<?php


namespace ComboStrap;

/**
 * Interface Path
 * @package ComboStrap
 *
 * An interface that implements path operation
 *
 * The {@link Path::toString()} function is just the path part (no other URI query parameters)
 *
 * TODO: because a path should be able to go to an URI format, it should also allow query parameters
 *  We could then add a `toPath` function to {@link DokuwikiUrl} and delete the tag attributes
 *  as parameter of all {@link MediaLink::createMediaLinkFromPath()} creator function
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

    function toString();

    function toAbsolutePath(): Path;

    /**
     * @return Mime the mime from the extension
     */
    function getMime(): ?Mime;
}
