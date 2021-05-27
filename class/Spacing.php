<?php


namespace ComboStrap;


class Spacing
{

    /**
     * Process the attributes that have an impact on the class
     * @param TagAttributes $attributes
     */
    public static function processSpacingAttributes(&$attributes)
    {

        // Spacing is just a class
        $spacing = "spacing";
        if ($attributes->hasComponentAttribute($spacing)) {

            $spacingValue = $attributes->getValueAndRemove($spacing);

            $spacingNames = preg_split("/\s/", $spacingValue);
            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
            foreach ($spacingNames as $spacingClass) {
                if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion) {

                    // The sides r and l has been renamed to e and s
                    // https://getbootstrap.com/docs/5.0/migration/#utilities-2
                    //

                    // https://getbootstrap.com/docs/5.0/utilities/spacing/
                    // By default, we consider tha there is no size and breakpoint
                    $sizeAndBreakPoint = "";
                    $propertyAndSide = $spacingClass;

                    $minusCharacter = "-";
                    $minusLocation = strpos($spacingClass, $minusCharacter);
                    if ($minusLocation !== false) {
                        // There is no size or break point
                        $sizeAndBreakPoint = substr($spacingClass, $minusLocation + 1);
                        $propertyAndSide = substr($spacingClass, 0, $minusLocation);
                    }
                    $propertyAndSide = str_replace("r", "e", $propertyAndSide);
                    $propertyAndSide = str_replace("l", "s", $propertyAndSide);
                    if ($sizeAndBreakPoint === "") {
                        $spacingClass = $propertyAndSide;
                    } else {
                        $spacingClass = $propertyAndSide . $minusCharacter . $sizeAndBreakPoint;
                    }

                }
                $attributes->addClassName($spacingClass);
            }
        }

    }
}
