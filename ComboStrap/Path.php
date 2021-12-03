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

    public const PATH_ATTRIBUTE = "path";
    public const DOKUWIKI_ID_ATTRIBUTE = "id";

    function getScheme();

    /**
     * The last name of the path without the extension
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

    /**
     * To the local file system path
     *
     * @return mixed
     */
    function toLocalPath(): LocalPath;

    function toString();

    function toAbsolutePath(): Path;

}
