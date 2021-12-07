<?php /** @noinspection SpellCheckingInspection */


namespace ComboStrap;


use action_plugin_combo_metamanager;
use Hidehalo\Nanoid\Client;
use RuntimeException;

class PageId extends MetadataText
{

    public const PAGE_ID_ATTRIBUTE = "page_id";

    /**
     * No separator, no uppercase to be consistent on the whole url
     */
    public const PAGE_ID_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Length to get the same probability than uuid v4. Too much ?
     */
    public const PAGE_ID_LENGTH = 21;

    public static function createForPage(Page $page): PageId
    {
        return (new PageId())
            ->setResource($page);
    }


    /**
     *
     *
     * @param string|null $value
     * @return MetadataText
     * @throws ExceptionCombo
     */
    public function setValue(?string $value): MetadataText
    {
        return $this->setValueWithOrWithoutForce($value);
    }



    public function getTab(): string
    {
        return action_plugin_combo_metamanager::TAB_INTEGRATION_VALUE;
    }

    public function getDescription(): string
    {
        return "An unique identifier for the page";
    }

    public function getLabel(): string
    {
        return "Page Id";
    }

    public function getName(): string
    {
        return self::PAGE_ID_ATTRIBUTE;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function getCanonical(): string
    {
        return $this->getName();
    }

    /**
     * This function should be:
     *   * used only in an replication process between internal system (mostly the database) to create a page id the most later after a page creation
     *   * not be used in rendering (just don't render temporarily)
     *
     * It's to avoid creating an id for a page that is moving with the
     * move plugin {@link \action_plugin_combo_linkmove::handle_rename_after()}
     *
     *
     * @return string get the page id or generate id if needed
     */
    public function getPageIdOrGenerate(): ?string
    {
        $actualValue = $this->getValue();
        if ($actualValue === null) {
            try {
                $actualValue = self::generateUniquePageId();
                $this->setValue($actualValue)
                    ->sendToStore();
            } catch (ExceptionCombo $e) {
                throw new RuntimeException($e);
            }
        }
        return $actualValue;
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

    public function sendToStore(): Metadata
    {
        /**
         * If the data was built with one store
         * and send to another store
         * We prevent the overwriting of a page id
         */
        $actualStoreValue = $this->getStore()->get($this);
        $value = $this->getValue();
        if ($actualStoreValue !== null && $actualStoreValue !== $value) {
            throw new ExceptionComboRuntime("The page id can not be modified once generated. The value in the store is $actualStoreValue while the new value is $value");
        }
        parent::sendToStore();
        return $this;

    }


}
