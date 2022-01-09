<?php


namespace ComboStrap;


/**
 * Interface ComboResource
 * @package ComboStrap
 *
 * Not Resource
 * because
 * https://www.php.net/manual/en/language.types.resource.php
 */
interface ResourceCombo
{

    public function getReadStoreOrDefault(): MetadataStore;

    /**
     * @return Path - a generic path system where the content raw resource is stored
     * ie the file system url, the dokuwiki url
     */
    public function getPath(): Path;

    /**
     * @return mixed - the unique id
     */
    public function getUid(): Metadata;

    /**
     * A buster value used in URL
     * to avoid cache (cache bursting)
     *
     * It should be unique for each version of the resource
     *
     * @return string
     */
    function getBuster(): string;

    /**
     * @return string - the resource type/name
     * Example for page: page
     * Used to locate the data in a datastore
     * The table name for instance
     */
    function getType(): string;

    /**
     * @return string - the resource name
     * (ie {@link ResourceName}
     */
    function getName(): ?string;

    /**
     * @return string - the name but not null
     */
    function getNameOrDefault(): string;

    /**
     * @return Metadata
     */
    public function getUidObject();


}
