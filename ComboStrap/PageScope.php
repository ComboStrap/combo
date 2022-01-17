<?php


namespace ComboStrap;

/**
 * The scope is used to determine the {@link Page::getLogicalPath()}
 * of the page used as key to store the cache
 *
 * It can be set by a component via the {@link p_set_metadata()}
 * in a {@link SyntaxPlugin::handle()} function
 *
 * This is mostly used on
 *   * side slots to have several output of a list {@link \syntax_plugin_combo_pageexplorer navigation pane} for different namespace (ie there is one cache by namespace)
 *   * header and footer main slot to have one output for each requested main page
 *
 *
 */
class PageScope extends MetadataText
{


    public const PROPERTY_NAME = "scope";
    /**
     * The special scope value current means the namespace of the requested page
     * The real scope value is then calculated before retrieving the cache
     */
    public const SCOPE_CURRENT_NAMESPACE_VALUE = "current_namespace";
    /**
     * @deprecated use the {@link PageScope::SCOPE_CURRENT_NAMESPACE_VALUE}
     */
    public const SCOPE_CURRENT_NAMESPACE_OLD_VALUE = "current";

    /**
     * The scope is the current requested page
     * (used for header and footer of the main slot)
     */
    public const SCOPE_CURRENT_PAGE_VALUE = "current_page";


    public static function createFromPage(Page $page)
    {
        return (new PageScope())
            ->setResource($page);
    }

    /**
     * @param string|null $value - the {@link PageScope::SCOPE_CURRENT_NAMESPACE_VALUE} or {@link PageScope::SCOPE_CURRENT_PAGE_VALUE} or a namespace value ...
     * @return MetadataText
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
    {
        return parent::setValue($value);
    }

    public function getValue(): ?string
    {
        $lastName = $this->getResource()->getPath()->getLastName();
        if (in_array($lastName, \action_plugin_combo_slot::SLOT_MAIN_NAMES)) {
            return self::SCOPE_CURRENT_PAGE_VALUE;
        };
        return parent::getValue();
    }


    public function getTab(): ?string
    {
        return null;
    }

    public function getDescription(): string
    {
        return "The scope determine the current namespace of slots (ie the namespace of the slot or the namespace of the main slot)";
    }

    public function getLabel(): string
    {
        return "scope";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        /**
         * Component are setting the value
         */
        return Metadata::RUNTIME_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getDefaultValue()
    {
        return null;
    }
}
