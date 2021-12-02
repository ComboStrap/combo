<?php


namespace ComboStrap;


interface MetadataStore
{


    public function set(Metadata $metadata);

    public function get(Metadata $metadata, $default = null);


}
