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
     * Set the {@link Metadata::getValue()} for a {@link Metadata::getResource()}
     * with the name {@link Metadata::getName()}
     * @param Metadata $metadata
     * @throws ExceptionCombo
     */
    public function set(Metadata $metadata);

    /**
     * Return the {@link Metadata::getValue()} for a {@link Metadata::getResource()}
     * and the name {@link Metadata::getName()}
     * @param Metadata $metadata
     * @param null $default - the default value to return if no data is found
     */
    public function get(Metadata $metadata, $default = null);

    public function getResource(): ResourceCombo;

    /**
     * This function permits to get a metadata value without creating a {@link Metadata} class
     *
     * @param string $name -  the {@link Metadata::getName()} of the metadata
     * @param null $default - the default value to return if no data is found
     * @return null|string|array|boolean
     */
    public function getFromPersistentName(string $name, $default = null);

    /**
     * This function permits to set a metadata value without creating a {@link Metadata} class
     * @param string $name - the {@link Metadata::getName()} of the metadata
     * @param null|string|array|boolean - $value
     */
    public function setFromPersistentName(string $name, $value);

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
     * @return bool - true if the data is stored in a array text based format
     * Used to send
     *   * the string `false` and not the false value for instance
     *   * and json in a array format
     */
    public function isHierarchicalTextBased(): bool;

    /**
     * Reset (Delete all data in memory)
     */
    public function reset();

    /**
     * @return string
     */
    public function getCanonical(): string;

    /**
     * @param ResourceCombo $resourceCombo
     * @return MetadataStore
     */
    static function getOrCreateFromResource(ResourceCombo $resourceCombo): MetadataStore;
}
