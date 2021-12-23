<?php /** @noinspection SpellCheckingInspection */


namespace ComboStrap;


use Hidehalo\Nanoid\Client;
use RuntimeException;

class PageId extends MetadataText
{

    public const PROPERTY_NAME = "page_id";

    /**
     * No separator, no uppercase to be consistent on the whole url
     */
    public const PAGE_ID_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Length to get the same probability than uuid v4. Too much ?
     */
    public const PAGE_ID_LENGTH = 21;
    public const PAGE_ID_ABBREV_LENGTH = 7;
    public const PAGE_ID_ABBR_ATTRIBUTE = "page_id_abbr";

    public static function createForPage(ResourceCombo $resource): PageId
    {
        return (new PageId())
            ->setResource($resource);
    }


    /**
     *
     *
     * @param string|null $value
     * @return MetadataText
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
    {
        return $this->setValueWithOrWithoutForce($value);
    }

    /**
     * Page Id cannot be null when build
     *
     * Check how to handle a move id to avoid creating an id for a page that is moving with the
     * move plugin {@link \action_plugin_combo_linkmove::handle_rename_after()}
     *
     * @param $value
     * @return Metadata
     */
    public function buildFromStoreValue($value): Metadata
    {

        if ($value !== null) {
            parent::buildFromStoreValue($value);
            return $this;
        }


        $resource = $this->getResource();
        if (!($resource instanceof Page)) {
            LogUtility::msg("Page Id is for now only for the page, this is not a page but {$this->getResource()->getType()}");
            return $this;
        }

        // no id for non-existing page
        if (!FileSystems::exists($resource->getPath())) {
            if (PluginUtility::isDevOrTest()) {
                LogUtility::msg("You can't ask a `page id` when the page does not exist", LogUtility::LVL_MSG_WARNING, $this->getCanonical());
            }
            parent::buildFromStoreValue($value);
            return $this;
        }

        $metadataFileSystemStore = MetadataDokuWikiStore::createFromResource($resource);

        // The page Id can be into the frontmatter
        // if the instructions are old, render them to parse the frontmatter
        // frontmatter is the first element that is processed during a run
        if (!\action_plugin_combo_parser::isParserRunning()) {
            if ($resource->getInstructionsDocument()->shouldProcess()) {
                $resource->getInstructionsDocument()->process();
                $metadataFileSystemStore->reset(); // the data may have changed
                $value = $metadataFileSystemStore->get($this);
                if ($value !== null) {
                    parent::buildFromStoreValue($value);
                    return $this;
                }
            }
        }

        // Value is still null, generate and store
        $actualValue = self::generateUniquePageId();
        parent::buildFromStoreValue($actualValue);

        try {
            $metadataFileSystemStore->set($this);
        } catch (ExceptionCombo $e) {
            throw new ExceptionComboRuntime("Unable to persist the generated page id", $this->getCanonical(), 0, $e);
        }

        return $this;

    }


    public function getTab(): string
    {
        return MetaManagerForm::TAB_INTEGRATION_VALUE;
    }

    public function getDescription(): string
    {
        return "An unique identifier for the page";
    }

    public function getLabel(): string
    {
        return "Page Id";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return null;
    }

    public function getCanonical(): string
    {
        return $this->getName();
    }


    /**
     * For, there is no real replication between website.
     *
     * Therefore, the source of truth is the value in the {@link syntax_plugin_combo_frontmatter}
     * Therefore, the page id generation should happen after the rendering of the page
     * at the database level
     *
     * Return a page id collision free
     * for the page already {@link DatabasePage::replicatePage() replicated}
     *
     * https://zelark.github.io/nano-id-cc/
     *
     * 1000 id / hour = ~35 years needed, in order to have a 1% probability of at least one collision.
     *
     * We don't rely on a sequence because
     *    - the database may be refreshed
     *    - sqlite does have only auto-increment support
     * https://www.sqlite.org/autoinc.html
     *
     * @return string
     */
    static function generateUniquePageId(): string
    {
        /**
         * Collision detection happens just after the use of this function on the
         * creation of the {@link DatabasePage::getDatabaseRowFromPage() databasePage object}
         *
         */
        $nanoIdClient = new Client();
        $pageId = ($nanoIdClient)->formattedId(self::PAGE_ID_ALPHABET, self::PAGE_ID_LENGTH);
        while (DatabasePage::createFromPageId($pageId)->exists()) {
            $pageId = ($nanoIdClient)->formattedId(self::PAGE_ID_ALPHABET, self::PAGE_ID_LENGTH);
        }
        return $pageId;
    }

    /**
     * Overwrite the page id even if it exists already
     * It should not be possible - used for now in case of conflict in page move
     * @throws ExceptionCombo
     */
    public function setValueForce(?string $value): PageId
    {
        return $this->setValueWithOrWithoutForce($value, true);
    }


    /**
     *
     * @param bool $force - It should not be possible - used for now in case of conflict in page move
     * @throws ExceptionCombo
     */
    private function setValueWithOrWithoutForce(?string $value, bool $force = false): PageId
    {
        if ($value === null) {
            throw new ExceptionCombo("A page id can not be set with a null value (Page: {$this->getResource()})", $this->getCanonical());
        }
        if (!is_string($value) || !preg_match("/[" . self::PAGE_ID_ALPHABET . "]/", $value)) {
            throw new ExceptionCombo("The page id value to set ($value) is not an alphanumeric string (Page: {$this->getResource()})", $this->getCanonical());
        }
        $actualId = $this->getValue();

        if ($force !== true) {
            if ($actualId !== null && $actualId !== $value) {
                throw new ExceptionCombo("The page id cannot be changed, the page ({$this->getResource()}) has already an id ($actualId}) that has not the same value ($value})", $this->getCanonical());
            }
            if ($actualId !== null) {
                throw new ExceptionCombo("The page id cannot be changed, the page ({$this->getResource()}) has already an id ($actualId})", $this->getCanonical());
            }
        } else {
            if (PluginUtility::isDevOrTest()) {
                throw new ExceptionComboRuntime("Forcing of the page id should not happen", $this->getCanonical());
            }
        }
        return parent::setValue($value);
    }

    public function sendToWriteStore(): Metadata
    {
        /**
         * If the data was built with one store
         * and send to another store
         * We prevent the overwriting of a page id
         */
        $actualStoreValue = $this->getReadStore()->get($this);
        $value = $this->getValue();
        if ($actualStoreValue !== null && $actualStoreValue !== $value) {
            throw new ExceptionComboRuntime("The page id can not be modified once generated. The value in the store is $actualStoreValue while the new value is $value");
        }
        parent::sendToWriteStore();
        return $this;

    }

    private function generate(): PageId
    {
        try {

            $actualValue = self::generateUniquePageId();

            /**
             * If the store is not the file system store
             * check that it does not exist already on the file system
             * and save it
             */
            $metadataStore = $this->getReadStore();
            if (!($metadataStore instanceof MetadataDokuWikiStore)) {
                $store = MetadataDokuWikiStore::createFromResource($this->getResource());
                $fsPageId = PageId::createForPage($this->getResource())
                    ->setReadStore($store);
                $value = $fsPageId->getValue();
                if ($value !== null) {
                    throw new ExceptionComboRuntime("The file system metadata store has already the page id ($value) for the page ({$this->getResource()}");
                }
                $fsPageId->setValue($value)
                    ->persist();
            }

            $this->setValue($actualValue)
                ->persist();

        } catch (ExceptionCombo $e) {
            throw new RuntimeException($e);
        }
        return $this;

    }

    public function getValueFromStore()
    {
        return $this->getReadStore()->get($this);
    }


}
