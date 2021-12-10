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
        $this->sourceStore =  $sourceStore;
        return $this;
    }

    public function toStore(MetadataStore $targetStore): MetadataStoreTransfer
    {
        $this->targetStore =  $targetStore;
        return $this;
    }

    public function process(array $data): MetadataStoreTransfer
    {
        $messages = [];
        /**
         * We build a new frontmatter because the
         * old key should be replace by the new one
         * (ie {@link \ComboStrap\PagePublicationDate::OLD_META_KEY}
         * by {@link \ComboStrap\PagePublicationDate::DATE_PUBLISHED}
         */
        $dataForRenderer = [];
        foreach ($data as $name => $value) {

            /**
             * Not modifiable meta check
             */
            if (in_array(strtolower($name), Metadata::NOT_MODIFIABLE_METAS)) {
                $messages[] = Message::createWarningMessage("The metadata ($name) is a protected metadata and cannot be modified")
                    ->setCanonical(Metadata::CANONICAL_PROPERTY);
                continue;
            }

            $metadata = Metadata::getForName($name);

            /**
             * Unknown meta
             */
            if ($metadata === null) {
                $dataForRenderer[$name] = $value;
                $this->targetStore->setFromResourceAndName($this->page, $name, $value);
                continue;
            }
            $dataForRenderer[$metadata->getName()] = $value;

            /**
             * Persistent ?
             */
            if ($metadata->getPersistenceType() !== Metadata::PERSISTENT_METADATA) {
                $messages[] = Message::createWarningMessage("The metadata ($name) is not persistent and cannot be modified")
                    ->setCanonical($metadata->getCanonical());
                continue;
            }

            /**
             * Sync
             */
            try {
                $metadata
                    ->setResource($this->page)
                    ->setStore($this->sourceStore)
                    ->buildFromStore()
                    ->setStore($this->targetStore)
                    ->sendToStore();
            } catch (ExceptionCombo $e) {
                $messages[] = Message::createErrorMessage("Error while replicating the meta ($metadata) from the store ($this->sourceStore) to the store ($this->targetStore). Message: " . $e->getMessage())
                    ->setCanonical($metadata->getCanonical());
            }
        }
        $this->targetStore->persist();

        $this->normalizedData = $dataForRenderer;
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
