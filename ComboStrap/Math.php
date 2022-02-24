<?php


namespace ComboStrap;


class Math
{


    /**
     * Linear interpolation
     * @param $x
     * @param $y
     * @param $weight
     * @return float
     */
    public static function unlerp($x, $y, $weight): float
    {
        $x2 = ($weight / 100) * $x;
        return ($y - $x2) / (1 - $weight / 100);
    }

    /**
     * Linear interpolation back if x and weight are the same
     * @param $x
     * @param $y
     * @param $weight
     * @return float|int
     */
    public static function lerp($x, $y, $weight)
    {

        $X = ($weight / 100) * $x;
        $Y = (1 - $weight / 100) * $y;
        return $X + $Y;

    }
}
