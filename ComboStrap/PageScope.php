<?php


namespace ComboStrap;

/**
 * The scope is the namespace used to store the cache
 *
 * It can be set by a component via the {@link p_set_metadata()}
 * in a {@link SyntaxPlugin::handle()} function
 *
 * This is mostly used on side slots to
 * have several output of a list {@link \syntax_plugin_combo_pageexplorer navigation pane}
 * for different namespace (ie there is one cache by namespace)
 *
 * The special value current means the namespace of the requested page
 */
class PageScope extends MetadataText
{


    public const SCOPE_KEY = "scope";
    /**
     * The special scope value current means the namespace of the requested page
     * The real scope value is then calculated before retrieving the cache
     */
    public const SCOPE_CURRENT_VALUE = "current";


    public static function createFromPage(Page $page)
    {
        return (new PageScope())
            ->setResource($page);
    }

    /**
     * @param string|null $value - the {@link PageScope::SCOPE_CURRENT_VALUE} or a namespace...
     * @return MetadataText
     * @throws ExceptionCombo
     */
    public function setValue(?string $value): MetadataText
    {
        return parent::setValue($value);
    }


    public function getTab()
    {
        return null;
    }

    public function getDescription(): string
    {
        return "The scope determine the current namespace of side slots (ie the namespace of the side slot or the namespace of the main slot)";
    }

    public function getLabel(): string
    {
        return "scope";
    }

    public function getName(): string
    {
        return self::SCOPE_KEY;
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
