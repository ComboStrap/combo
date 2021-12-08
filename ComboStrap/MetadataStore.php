<?php


namespace ComboStrap;

/**
 * Where to store a metadata
 *
 * Not that a metadata may be created even if the file does not exist
 * (when the page is rendered for the first time for instance)
 *
 */
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
     *
     * Flush to disk on a file system or commit in a database
     * @return mixed
     *
     * Resource got an {@link ResourceComboAbs::persist() alias of this function} for easy persisting.
     *
     * Don't persist in the setter function of the metadata object
     *
     * Why ?
     *   - We set normally a lot of metadata at once, we persist at the end of the function
     *   - In a metadata list with a lot of value, it's normal to persist when all values are in the batch
     *   - This is not always needed for instance modification of the frontmatter is just a modification of the persistence value
     *   - For scalar, it means that we need to persist
     *   - Goal is to persist at the end of the HTTP request
     */
    public function persist();

    /**
     * @return bool - true if the data is stored in a text format
     * Used to send the string `false` and not the false value for instance
     */
    public function isTextBased(): bool;

    /**
     * Reset
     */
    public function reset();


}
