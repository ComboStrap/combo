<?php


namespace ComboStrap;

/**
 * Interface Path
 * @package ComboStrap
 * A generic path for a generic file system
 *
 * The string is just the path
 * (no other URI parameters)
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
     * The absolute path without root separator
     * Heavily used inside Dokuwiki
     * @return mixed
     */
    function getDokuwikiId();

    function toString();

    function toAbsolutePath(): Path;

    /**
     * @return Mime the mime from the extension
     */
    function getMime(): ?Mime;
}
