<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\MetaManagerForm;

abstract class MetadataImage extends MetadataWikiPath
{

    public function getTab(): string
    {
        return MetaManagerForm::TAB_IMAGE_VALUE;
    }

}
