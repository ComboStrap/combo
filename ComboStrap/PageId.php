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
            return parent::buildFromStoreValue($value);
        }


        $resource = $this->getResource();
        if (!($resource instanceof Page)) {
            LogUtility::msg("Page Id is for now only for the page, this is not a page but {$this->getResource()->getType()}");
            return $this;
        }

        // null for non-existing page
        if (!FileSystems::exists($resource->getPath())) {
            if (PluginUtility::isDevOrTest()) {
                global $ACT;
                if ($ACT !== "edit") {
                    LogUtility::msg("Dev/Test message only: You can't ask a `page id` with the action $ACT, the page ({$this->getResource()}) does not exist", LogUtility::LVL_MSG_INFO, $this->getCanonical());
                }
            }
            return parent::buildFromStoreValue($value);
        }


        /**
         * If the store is not the file system store
         * check that it does not exist already on the file system
         * and save it
         */
        $readStore = $this->getReadStore();
        if (!($readStore instanceof MetadataDokuWikiStore)) {
            $metadataFileSystemStore = MetadataDokuWikiStore::getOrCreateFromResource($resource);
            $value = $metadataFileSystemStore->getFromPersistentName(self::getPersistentName());
            if ($value !== null) {
                return parent::buildFromStoreValue($value);
            }
        }

        // The page Id can be into the frontmatter
        // if the instructions are old, render them to parse the frontmatter
        // frontmatter is the first element that is processed during a run
        try {
            $frontmatter = MetadataFrontmatterStore::createFromPage($resource);
            $value = $frontmatter->getFromPersistentName(self::getPersistentName());
            if ($value !== null) {
                return parent::buildFromStoreValue($value);
            }
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while reading the frontmatter");
            return $this;
        }

        // datastore
        if (!($readStore instanceof MetadataDbStore)) {
            $dbStore = MetadataDbStore::getOrCreateFromResource($resource);
            $value = $dbStore->getFromPersistentName(self::getPersistentName());
            if ($value !== null && $value !== "") {

                $pathDbValue = $dbStore->getFromPersistentName(PagePath::getPersistentName());

                /**
                 * If the page in the database does not exist,
                 * We think that the page was moved from the file system
                 * and we return the page id
                 */
                $pageDbValue = Page::createPageFromQualifiedPath($pathDbValue);
                if (!FileSystems::exists($pageDbValue->getPath())) {
                    return parent::buildFromStoreValue($value);
                }

                /**
                 * The page path in the database exists
                 * If they are the same, we return the page id
                 * (because due to duplicate in canonical, the row returned may be from another resource)
                 */
                $resourcePath = $resource->getPath()->toString();
                if ($pathDbValue === $resourcePath) {
                    return parent::buildFromStoreValue($value);
                }
            }
        }

        // Value is still null, not in the the frontmatter, not in the database
        // generate and store
        $actualValue = self::generateUniquePageId();
        parent::buildFromStoreValue($actualValue);
        try {
            // Store the page id on the file system
            MetadataDokuWikiStore::getOrCreateFromResource($resource)
                ->set($this);
            /**
             * Create the row in the database (to allow permanent url redirection {@link PageUrlType})
             */
            (new DatabasePageRow())
                ->setPage($resource)
                ->upsertAttributes([PageId::getPersistentName() => $actualValue]);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Unable to store the page id generated. Message:" . $e->getMessage());
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
     * for the page already {@link DatabasePageRow::replicatePage() replicated}
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
         * creation of the {@link DatabasePageRow::getDatabaseRowFromPage() databasePage object}
         *
         */
        $nanoIdClient = new Client();
        $pageId = ($nanoIdClient)->formattedId(self::PAGE_ID_ALPHABET, self::PAGE_ID_LENGTH);
        while (DatabasePageRow::createFromPageId($pageId)->exists()) {
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
                // this should never happened (exception in test/dev)
                throw new ExceptionComboRuntime("Forcing of the page id should not happen in dev/test", $this->getCanonical());
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


    public function getValueFromStore()
    {
        return $this->getReadStore()->get($this);
    }


}
