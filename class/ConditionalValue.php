<?php


namespace ComboStrap;


class ConditionalValue
{

    const CANONICAL ="conditional";

    /**
     * ConditionalValue constructor.
     */
    public function __construct($value)
    {
         $array =  explode("-",$value);
         if(sizeof($array)>2){
             LogUtility::msg("The screen conditional value ($value) should have only one separator character `-`", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
         }
    }
}
