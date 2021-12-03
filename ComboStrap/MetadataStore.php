<?php


namespace ComboStrap;


interface MetadataStore
{

    /**
     * @param Metadata $metadata
     * @return mixed
     * @throws ExceptionCombo
     */
    public function set(Metadata $metadata);

    public function get(Metadata $metadata, $default = null);


}
