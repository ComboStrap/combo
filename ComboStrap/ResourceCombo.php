<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataStore;

/**
 * Interface ComboResource
 * @package ComboStrap
 *
 * It's called ResourceCombo and not Resource because of
 * https://www.php.net/manual/en/language.types.resource.php
 *
 * A resource is a just a wrapper around path that adds metadata functionalities
 *
 * @deprecated it's just a {@link Path} with a {@link PageId} that can be accessed with the web server, therefore it should be a {@link WikiPath}
 */
interface ResourceCombo
{


    public function getReadStoreOrDefault(): MetadataStore;

    /**
     * @return Path - a generic path system where the content raw resource is stored
     * ie the file system url, the dokuwiki url
     */
    public function getPathObject(): Path;

    /**
     * @return Metadata - the global unique id
     */
    public function getUid(): Metadata;


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
    public function getUidObject(): Metadata;


}
