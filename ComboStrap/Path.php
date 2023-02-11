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
 * The {@link Path::toQualifiedId()} function is just the path part (no other URI query parameters)
 *
 * A lot of overlap with {@link Url}
 */
interface Path
{


    function getExtension();

    /**
     * @return string
     * @throws ExceptionNotFound - if the path does not have any last name
     */
    function getLastNameWithoutExtension(): string;

    function getScheme();

    /**
     * The last name of the path with or without an extension
     *
     * The Path class does not have a notion of "extension"
     * because the file does not have one but we provide the
     * {@link PathAbs::getExtension()} as utility
     *
     * @return string
     * @throws ExceptionNotFound - if the path does not have any last name
     */
    function getLastName(): string;

    /**
     * @return mixed - the names are the words between the separator
     */
    function getNames();

    /**
     * @return mixed - the names but without the extension
     */
    function getNamesWithoutExtension();

    /**
     * @return Path
     * @throws ExceptionNotFound - for the root
     */
    function getParent(): Path;

    /**
     * @return string only the string representation of the path
     * This is:
     * * the {@link WikiPath::getWikiId()} with the root for a WikiPath
     * * the path element for all others
     *
     * It's used mostly as common identifier that can be used with any path
     * (such as {@link LocalPath} or {@link WikiPath} path
     */
    function toQualifiedId(): string;

    /**
     * @return string the uri string representation of this path (with all information, scheme, drive, attributes)
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
     * @return Url - the local URL
     * For external path (ie {@link Url}, there is no {@link IFetcher} implementation
     * To create a {@link ThirdMediaLink}, we use therefore this url
     */
    function getUrl(): Url;

    /**
     * Needed for the file protocol URI {@link LocalPath}
     * @return string
     */
    function getHost(): string;

}
