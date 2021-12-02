<?php


namespace ComboStrap;


interface Resource
{

    public function getDefaultMetadataStore(): MetadataStore;

    /**
     * @return Path - a generic path system where the content raw resource is stored
     * ie the file system url, the dokuwiki url
     */
    public function getPath(): Path;

}
