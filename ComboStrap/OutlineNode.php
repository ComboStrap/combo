<?php

namespace ComboStrap;



class OutlineNode
{


    /**
     *
     * @var Call[] $calls
     */
    private array $calls = [];

    /**
     */
    public function __construct()
    {
    }

    public static function create(): OutlineNode
    {
        return new OutlineNode();
    }

    public function addCall(Call $actualCall): OutlineNode
    {
        $this->calls[] = $actualCall;
        return $this;
    }

}
