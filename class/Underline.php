<?php


namespace ComboStrap;


class Underline
{

    const UNDERLINE_ATTRIBUTE = "underline";
    const CANONICAL = self::UNDERLINE_ATTRIBUTE;

    /**
     * @param TagAttributes $attributes
     */
    public static function processUnderlineAttribute(TagAttributes &$attributes)
    {


        if ($attributes->hasComponentAttribute(Underline::UNDERLINE_ATTRIBUTE)) {
            $attributes->removeComponentAttribute(Underline::UNDERLINE_ATTRIBUTE);
            $attributes->addClassName("text-decoration-underline");
        }

    }


}
