<?php /** @noinspection SpellCheckingInspection */


namespace ComboStrap;


use Hidehalo\Nanoid\Client;


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
    /**
     *
     * The page id abbreviation is used in the url to make them unique.
     *
     * A website is not git but an abbreviation of 7
     * is enough for a website.
     *
     * 7 is also the initial length of the git has abbreviation
     *
     * It gives a probability of collision of 1 percent
     * for 24 pages creation by day over a period of 100 year
     * (You need to create 876k pages).
     *  with the 36 alphabet
     * Furthermore, we test on creation the uniqueness on the 7 page id abbreviation
     *
     * more ... https://datacadamia.com/crypto/hash/collision
     */
    public const PAGE_ID_ABBREV_LENGTH = 7;
    public const PAGE_ID_ABBR_ATTRIBUTE = "page_id_abbr";

    public static function createForPage(ResourceCombo $resource): PageId
    {
        return (new PageId())
            ->setResource($resource);
    }

    public static function getAbbreviated(string $pageId)
    {
        return substr($pageId, 0, PageId::PAGE_ID_ABBREV_LENGTH);
    }

    /**
     * Generate and store
     * Store the page id on the file system
     */
    public static function generateAndStorePageId(MarkupPath $markupPath): string
    {
        $pageId = self::generateUniquePageId();
        MetadataDokuWikiStore::getOrCreateFromResource($markupPath)
            ->setFromPersistentName(PageId::getPersistentName(), $pageId);
        return $pageId;
    }


    /**
     *
     *
     * @param string|null $value
     * @return MetadataText
     * @throws ExceptionCompile
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
        if (!($resource instanceof MarkupPath)) {
            LogUtility::msg("Page Id is for now only for the page, this is not a page but {$this->getResource()->getType()}");
            return $this;
        }

        // null for non-existing page
        if (!FileSystems::exists($resource->getPathObject())) {
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
        } catch (ExceptionCompile $e) {
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
                $pageDbValue = MarkupPath::createPageFromQualifiedId($pathDbValue);
                if (!FileSystems::exists($pageDbValue->getPathObject())) {
                    return parent::buildFromStoreValue($value);
                }

                /**
                 * The page path in the database exists
                 * If they are the same, we return the page id
                 * (because due to duplicate in canonical, the row returned may be from another resource)
                 */
                $resourcePath = $resource->getPathObject()->toQualifiedPath();
                if ($pathDbValue === $resourcePath) {
                    return parent::buildFromStoreValue($value);
                }
            }
        }

        // null ?
        return parent::buildFromStoreValue($value);

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
        /**
         * The real id is the abbreviated one
         * Test if there is not yet a page with this value
         */
        while (
        DatabasePageRow::createFromPageIdAbbr(self::getAbbreviated($pageId))->exists()
        ) {
            $pageId = ($nanoIdClient)->formattedId(self::PAGE_ID_ALPHABET, self::PAGE_ID_LENGTH);
        }

        return $pageId;
    }

    /**
     * Overwrite the page id even if it exists already
     * It should not be possible - used for now in case of conflict in page move
     * @throws ExceptionCompile
     */
    public function setValueForce(?string $value): PageId
    {
        return $this->setValueWithOrWithoutForce($value, true);
    }


    /**
     *
     * @param bool $force - It should not be possible - used for now in case of conflict in page move
     * @throws ExceptionCompile
     */
    private function setValueWithOrWithoutForce(?string $value, bool $force = false): PageId
    {
        if ($value === null) {
            throw new ExceptionCompile("A page id can not be set with a null value (Page: {$this->getResource()})", $this->getCanonical());
        }
        if (!is_string($value) || !preg_match("/[" . self::PAGE_ID_ALPHABET . "]/", $value)) {
            throw new ExceptionCompile("The page id value to set ($value) is not an alphanumeric string (Page: {$this->getResource()})", $this->getCanonical());
        }
        $actualId = $this->getValue();

        if ($force !== true) {
            if ($actualId !== null && $actualId !== $value) {
                throw new ExceptionCompile("The page id cannot be changed, the page ({$this->getResource()}) has already an id ($actualId}) that has not the same value ($value})", $this->getCanonical());
            }
            if ($actualId !== null) {
                throw new ExceptionCompile("The page id cannot be changed, the page ({$this->getResource()}) has already an id ($actualId})", $this->getCanonical());
            }
        } else {
            if (PluginUtility::isDevOrTest()) {
                // this should never happened (exception in test/dev)
                throw new ExceptionRuntime("Forcing of the page id should not happen in dev/test", $this->getCanonical());
            }
        }
        return parent::setValue($value);
    }

    /**
     * @throws ExceptionBadArgument
     *
     */
    public function sendToWriteStore(): Metadata
    {
        /**
         * If the data was built with one store
         * and send to another store
         * We prevent the overwriting of a page id
         */
        $actualStoreValue = $this->getReadStore()->get($this);
        try {
            $value = $this->getValue();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadArgument("No value to store");
        }
        if ($actualStoreValue !== null && $actualStoreValue !== $value) {
            throw new ExceptionBadArgument("The page id can not be modified once generated. The value in the store is $actualStoreValue while the new value is $value");
        }
        parent::sendToWriteStore();
        return $this;

    }


    public function getValueFromStore()
    {
        return $this->getReadStore()->get($this);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getValue(): string
    {

        return parent::getValue();

    }


}
