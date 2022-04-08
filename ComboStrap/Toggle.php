<?php


namespace ComboStrap;


class Toggle
{

    /**
     * An indicator attribute that tells if the target element is collapsed or not (accordion)
     */
    const COLLAPSED = "collapsed";
    const TOGGLE_STATE = "toggle-state";
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
    static function processToggle(&$attributes)
    {


        /**
         * Toggle state
         */
        $value = $attributes->getValueAndRemove(self::TOGGLE_STATE);
        if ($value !== null) {
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
                        $breakpoint = $conditionalValue->getBreakpointSize();

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
            $attributes->addComponentAttributeValue("data-{$bootstrapNamespace}toggle", "collapse");
            $attributes->addComponentAttributeValue("data-{$bootstrapNamespace}target", $targetId);

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
