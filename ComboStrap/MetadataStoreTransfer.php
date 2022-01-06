<?php


namespace ComboStrap;


class MetadataStoreTransfer
{

    /**
     * @var MetadataStore
     */
    private $sourceStore;
    /**
     * @var MetadataStore
     */
    private $targetStore;

    /**
     * @var Page
     */
    private $page;
    /**
     * @var array - the normalized data after processing
     * Name of metadata may be deprecated
     * After processing, this array will have the new keys
     * Use in a frontmatter to send correct data to the rendering metadata phase
     */
    private $normalizedData;
    /**
     * @var array - the processing messages
     */
    private $messages;

    /**
     * MetadataStoreTransfer constructor.
     * @param Page $resource
     */
    public function __construct(ResourceCombo $resource)
    {
        $this->page = $resource;
    }


    public static function createForPage($page): MetadataStoreTransfer
    {
        return new MetadataStoreTransfer($page);
    }

    public function fromStore(MetadataStore $sourceStore): MetadataStoreTransfer
    {
        $this->sourceStore = $sourceStore;
        return $this;
    }

    public function toStore(MetadataStore $targetStore): MetadataStoreTransfer
    {
        $this->targetStore = $targetStore;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function process(array $data): MetadataStoreTransfer
    {
        $messages = [];

        /**
         * Pre-processing
         * Check/ validity and list of metadata building
         */
        $metadatas = [];
        foreach ($data as $persistentName => $value) {


            $metadata = Metadata::getForName($persistentName);

            /**
             * Take old name or renaming into account
             *
             * ie The old key should be replace by the new one
             * (ie {@link \ComboStrap\PagePublicationDate::OLD_META_KEY}
             * by {@link \ComboStrap\PagePublicationDate::PROPERTY_NAME}
             */
            $name = $persistentName;
            if ($metadata !== null) {
                $name = $metadata::getName();
                $persistentName = $metadata::getPersistentName();
            }
            $this->normalizedData[$persistentName] = $value;

            /**
             * Not modifiable meta check
             */
            if (in_array($name, Metadata::NOT_MODIFIABLE_METAS)) {
                $messages[] = Message::createWarningMessage("The metadata ($name) is a protected metadata and cannot be modified")
                    ->setCanonical(Metadata::CANONICAL);
                continue;
            }

            /**
             * Unknown meta
             */
            if ($metadata === null) {
                $this->targetStore->setFromPersistentName($persistentName, $value);
                continue;
            }

            /**
             * Valid meta to proceed in the next phase
             */
            $metadatas[$name] = $metadata;

        }


        foreach ($metadatas as $metadata) {


            /**
             * Persistent ?
             */
            if ($metadata->getPersistenceType() !== Metadata::PERSISTENT_METADATA) {
                $messages[] = Message::createWarningMessage("The metadata ({$metadata->getName()}) is not persistent and cannot be modified")
                    ->setCanonical($metadata->getCanonical());
                continue;
            }

            /**
             * Sync
             */
            try {
                $metadata
                    ->setResource($this->page)
                    ->setReadStore($this->sourceStore)
                    ->buildFromReadStore()
                    ->setWriteStore($this->targetStore)
                    ->sendToWriteStore();
            } catch (ExceptionCombo $e) {
                $messages[] = Message::createErrorMessage("Error while replicating the meta ($metadata) from the store ($this->sourceStore) to the store ($this->targetStore). Message: " . $e->getMessage())
                    ->setCanonical($metadata->getCanonical());
            }
        }
        $this->targetStore->persist();

        $this->messages = $messages;
        return $this;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getNormalizedDataArray(): array
    {
        return $this->normalizedData;
    }


}
