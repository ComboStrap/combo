<?php


namespace ComboStrap;

/**
 *
 * The scope is used to influence the cache render key.
 *
 *
 *
 * The determine the {@link Page::getLogicalPath()}
 * of the page used as key to store the render cache
 *
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
class CacheRuntimeDependencies
{


    public const DEPENDENCY_NAME = "requested";

    /**
     * The special scope value current means the namespace of the requested page
     * The real scope value is then calculated before retrieving the cache
     */
    public const REQUESTED_NAMESPACE_VALUE = "requested_namespace";
    /**
     * @deprecated use the {@link CacheRuntimeDependencies::REQUESTED_NAMESPACE_VALUE}
     */
    public const NAMESPACE_OLD_VALUE = "current";

    /**
     * The dependency value is the requested page path
     * (used for syntax mostly used in the header and footer of the main slot for instance)
     */
    public const REQUESTED_PAGE_VALUE = "requested_page";



    /**
     * @return string - output the namespace used in the cache key
     *
     * For example:
     *   * the ':sidebar' html output may be dependent to the namespace `ns` or `ns2`
     * @throws ExceptionCombo
     */
    public static function getValueForKey($dependenciesValue): string
    {

        /**
         * Set the logical id
         * When no $ID is set (for instance, test),
         * the logical id is the id
         *
         * The logical id depends on the namespace attribute of the {@link \syntax_plugin_combo_pageexplorer}
         * stored in the `scope` metadata.
         *
         * Scope is directory/namespace based
         */
        $requestPage = Page::createPageFromRequestedPage();
        switch ($dependenciesValue) {
            case CacheRuntimeDependencies::NAMESPACE_OLD_VALUE:
            case CacheRuntimeDependencies::REQUESTED_NAMESPACE_VALUE:
                $parentPath = $requestPage->getPath()->getParent();
                if ($parentPath === null) {
                    return ":";
                } else {
                    return $parentPath->toString();
                }
            case CacheRuntimeDependencies::REQUESTED_PAGE_VALUE:
                return $requestPage->getPath()->toString();
            default:
                throw new ExceptionCombo("The requested dependency value ($dependenciesValue) is unknown");
        }


    }
}
