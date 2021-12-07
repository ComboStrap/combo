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

    public function getDefaultMetadataStore(): MetadataStore;

    /**
     * @return Path - a generic path system where the content raw resource is stored
     * ie the file system url, the dokuwiki url
     */
    public function getPath(): Path;

    /**
     * @return mixed - the unique id
     */
    public function getUid(): MetadataScalar;

    function getBuster(): string;

    function getType(): string;

}
