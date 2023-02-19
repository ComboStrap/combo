<?php


namespace ComboStrap;


class Vertical
{


    public const VERTICAL_ATTRIBUTE = "vertical";
    const CANONICAL = self::VERTICAL_ATTRIBUTE;

    const VALUES = ["start", "end", "center", "baseline", "stretch"];
    const COMPONENTS = [GridTag::TAG, ];

    public static function processVertical(TagAttributes &$tagAttributes)
    {


        Horizontal::processFlexAttribute( self::VERTICAL_ATTRIBUTE, $tagAttributes);

    }

}
