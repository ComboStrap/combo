<?php


namespace ComboStrap;


class MetadataStoreTransfer
{
    const CANONICAL = "meta-store-transfer";

    /**
     * @var MetadataStore
     */
    private $sourceStore;
    /**
     * @var MetadataStore
     */
    private $targetStore;

    /**
     * @var MarkupPath
     */
    private $page;


    /**
     * @var array - the processing messages
     */
    private array $messages = [];

    /**
     * @var array - the validated metadata (that may be stored)
     */
    private array $metadatasThatMayBeStored;

    /**
     * @var Metadata[] - the original metas (no default as we check if they were already set)
     */
    private array $originalMetadatas;

    /**
     * MetadataStoreTransfer constructor.
     * @param MarkupPath $resource
     */
    public function __construct(ResourceCombo $resource)
    {
        $this->page = $resource;
    }


    public static function createForPage($page): MetadataStoreTransfer
    {
        return new MetadataStoreTransfer($page);
    }

    /**
     * @return $this - validate the transfer (ie the metadatas). The metadata that can be retrieved via {@link self::getValidatedMetadatas()}
     * and the bad validation messages if any via {@link self::getMessages()}
     */
    public function validate(): MetadataStoreTransfer
    {

        if (isset($this->metadatasThatMayBeStored)) {
            return $this;
        }
        if (!isset($this->originalMetadatas)) {
            throw new ExceptionRuntimeInternal("The original metadata should be defined");
        }

        $this->metadatasThatMayBeStored = [];
        foreach ($this->originalMetadatas as $originalMetaKey => $originalMetaValue) {


            try {
                $metadataObject = Metadata::getForName($originalMetaKey);
            } catch (ExceptionNotFound $e) {
                LogUtility::error("The meta ($originalMetaKey) was not found", self::CANONICAL, $e);
                continue;
            }

            /**
             * Take old name or renaming into account
             *
             * ie The old key should be replace by the new one
             * (ie {@link \ComboStrap\PagePublicationDate::OLD_META_KEY}
             * by {@link \ComboStrap\PagePublicationDate::PROPERTY_NAME}
             */
            $name = $originalMetaKey;
            if ($metadataObject !== null) {
                $name = $metadataObject::getName();
                $originalMetaKey = $metadataObject::getPersistentName();
            }

            /**
             * Not modifiable meta check
             */
            if (in_array($name, Metadata::NOT_MODIFIABLE_METAS)) {
                $this->messages[] = Message::createWarningMessage("The metadata ($name) is a protected metadata and cannot be modified")
                    ->setCanonical(Metadata::CANONICAL);
                continue;
            }

            /**
             * Unknown meta
             */
            if ($metadataObject === null) {
                $this->messages[] = Message::createWarningMessage("The metadata ($originalMetaKey) is unknown and was not persisted")->setCanonical(Metadata::CANONICAL);
                continue;
            }


            /**
             * We build and not set with {@link Metadata::setFromStoreValue()}
             * because tabular data in forms have several key by columns (ie on key is a column
             * that may have several values)
             * Therefore tabular data needs to get access to the whole source store
             * to be build
             */
            $metadataObject
                ->setResource($this->page)
                ->setReadStore($this->sourceStore)
                ->setWriteStore($this->targetStore)
                ->buildFromReadStore();

            $this->metadatasThatMayBeStored[$name] = $metadataObject;

        }
        return $this;
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
     * @param Metadata[]|null $data - the metadadata (@deprecate for {@link self::setMetadatas()}
     * @return $this
     */
    public function process(array $data = null): MetadataStoreTransfer
    {

        /**
         * We may use this object to validate, setting before the processing
         */
        if (isset($this->originalMetadatas) && $data !== null) {
            throw new ExceptionRuntimeInternal("The metadata to process were already set");
        } else {
            $this->originalMetadatas = $data;
        }
        $messages = [];

        /**
         * Pre-processing
         * Check/ validity and list of metadata building
         */
        $validatedMetadata = $this->validate()->getValidatedMetadatas();

        foreach ($validatedMetadata as $metadata) {

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
                $metadata->sendToWriteStore();
            } catch (ExceptionCompile $e) {
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


    /**
     * @return Metadata[] - an array where the key is the name and the value a {@link Metadata} object
     */
    public function getValidatedMetadatas(): array
    {
        if (!isset($this->metadatasThatMayBeStored)) {
            $this->validate();
        }
        return $this->metadatasThatMayBeStored;
    }

    /**
     * @param Metadata[] $originalMetadatas
     * @return $this
     */
    public function setMetadatas(array $originalMetadatas): MetadataStoreTransfer
    {
        $this->originalMetadatas = $originalMetadatas;
        return $this;
    }


}
