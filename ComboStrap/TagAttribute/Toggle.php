<?php


namespace ComboStrap\TagAttribute;


use ComboStrap\Bootstrap;
use ComboStrap\ConditionalValue;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

class Toggle
{

    /**
     * An indicator attribute that tells if the target element is collapsed or not (accordion)
     */
    const COLLAPSED = "collapsed";
    const TOGGLE_STATE = "toggle-state";
    const TOGGLE_STATE_NONE = "none";
    const TOGGLE_STATE_EXPANDED = "expanded";
    const TOGGLE_STATE_COLLAPSED = "collapsed";
    const CANONICAL = "toggle";


    /**
     * The collapse attribute are the same
     * for all component except a link
     * @param TagAttributes $attributes
     *
     */
    public
    static function processToggle(TagAttributes $attributes)
    {


        /**
         * Toggle state
         */
        $value = $attributes->getValueAndRemove(self::TOGGLE_STATE);
        if ($value !== null) {
            if ($value === self::TOGGLE_STATE_NONE) {
                return;
            }
            $values = explode(" ", $value);
            foreach ($values as $value) {
                if (empty($value)) {
                    continue;
                }
                switch ($value) {
                    case self::TOGGLE_STATE_EXPANDED:
                        $attributes->addClassName("collapse show");
                        break;
                    case self::TOGGLE_STATE_COLLAPSED:
                        $attributes->addClassName("collapse");
                        break;
                    default:
                        /**
                         * It may be a conditional breakpoint collapse
                         */
                        try {
                            $conditionalValue = ConditionalValue::createFrom($value);
                        } catch (ExceptionBadSyntax $e) {
                            LogUtility::msg("The toggle state ($value) is invalid. It should be (expanded, collapsed or breakpoint-expanded)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            continue 2;
                        }

                        $toggleStateValue = $conditionalValue->getValue();
                        if ($toggleStateValue !== self::TOGGLE_STATE_EXPANDED) {
                            LogUtility::msg("The toggle breakpoint ($value) supports only `expanded` as value, not $toggleStateValue.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            continue 2;
                        }
                        $id = $attributes->getValue("id");
                        if (empty($id)) {
                            LogUtility::msg("A conditional toggle breakpoint ($value) needs an id attribute.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            continue 2;
                        }
                        $breakpoint = $conditionalValue->getBreakpointSize();
                        $styleSheet = <<<EOF
@media (min-width: {$breakpoint}px) {
   #{$id} {
        display: block!important
   }
}
EOF;
                        /**
                         * The snippet id is dependent on id
                         * if there is more than one
                         */
                        $snippetId = self::CANONICAL."-$id";
                        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($snippetId, $styleSheet);

                }
            }
        }

        /**
         * Old
         * https://combostrap.com/release/deprecated/toggle
         * @deprecated
         */
        $collapse = "toggleTargetId";
        if ($attributes->hasComponentAttribute($collapse)) {
            $targetId = $attributes->getValueAndRemoveIfPresent($collapse);
        } else {
            $targetId = $attributes->getValueAndRemoveIfPresent("collapse");
        }
        if ($targetId != null) {
            $bootstrapNamespace = "bs-";
            if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
                $bootstrapNamespace = "";
            }
            /**
             * We can use it in a link
             */
            if (substr($targetId, 0, 1) != "#") {
                $targetId = "#" . $targetId;
            }
            $attributes->addOutputAttributeValue("data-{$bootstrapNamespace}toggle", "collapse");
            $attributes->addOutputAttributeValue("data-{$bootstrapNamespace}target", $targetId);

        }

        /**
         * Toggle state
         * @deprecated
         * https://combostrap.com/release/deprecated/toggle
         */
        $collapsed = self::COLLAPSED;
        if ($attributes->hasComponentAttribute($collapsed)) {
            $value = $attributes->getValueAndRemove($collapsed);
            if ($value) {
                $attributes->addClassName("collapse");
            }
        }


    }

    public static function disableEntity(string $mode): bool
    {
        if ($mode === "entity") {
            return false;
        }
        return true;
    }

}
