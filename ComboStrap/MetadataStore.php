<?php


namespace ComboStrap;


interface MetadataStore
{

    /**
     * @param Metadata $metadata
     * @return mixed
     * @throws ExceptionCombo
     */
    public function set(Metadata $metadata);

    public function get(Metadata $metadata, $default = null);

    /**
     * @return mixed
     *
     * Don't persist the setter function of the metadata object
     *
     * Why ?
     *   - We set normally a lot of metadata at once, we persist at the end of the function
     *   - In a metadata list with a lot of value, it's normal to persist when all values are in the batch
     *   - This is not always needed for instance modification of the frontmatter is just a modification of the persistence value
     *   - For scalar, it means that we need to persist
     *   - Goal is to persist at the end of the HTTP request
     */
    public function persist();


}
