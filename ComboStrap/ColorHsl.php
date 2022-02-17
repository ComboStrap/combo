<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;
class ColorHsl
{

    const CANONICAL = "color";
    private $hue;

    private $saturation;
    /**
     * @var float|int
     */
    private $lightness;

    /**
     * ColorHsl constructor.
     * @param $hue
     * @param $saturation
     * @param float|int $lightness
     */
    public function __construct($hue, $saturation, $lightness)
    {
        $this->hue = $hue;
        $this->saturation = $saturation;
        $this->lightness = $lightness;
    }


    public static function createFromChannels(float $hue, float $saturation, float $lightness): ColorHsl
    {
        return new ColorHsl($hue, $saturation, $lightness);
    }

    public function getLightness()
    {
        return $this->lightness;
    }

    public function getSaturation()
    {
        return $this->saturation;
    }

    public function getHue()
    {
        return $this->hue;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setLightness(int $int): ColorHsl
    {
        if ($int < 0 || $int > 100) {
            throw new ExceptionCombo("Lightness should be between 0 and 100");
        }
        $this->lightness = $int;
        return $this;
    }

    /**
     * @return ColorRgb Reference:
     *
     * Reference:
     * https://en.wikipedia.org/wiki/HSL_and_HSV#HSL_to_RGB
     * https://gist.github.com/brandonheyer/5254516
     * @throws ExceptionCombo
     */
    function toRgb(): ColorRgb
    {

        $lightness = $this->lightness / 100;
        $saturation = $this->saturation / 100;
        $hue = $this->hue;

        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $x = $chroma * (1 - abs(fmod(($hue / 60), 2) - 1));
        $m = $lightness - ($chroma / 2);

        if ($hue < 60) {
            $red = $chroma;
            $green = $x;
            $blue = 0;
        } else if ($hue < 120) {
            $red = $x;
            $green = $chroma;
            $blue = 0;
        } else if ($hue < 180) {
            $red = 0;
            $green = $chroma;
            $blue = $x;
        } else if ($hue < 240) {
            $red = 0;
            $green = $x;
            $blue = $chroma;
        } else if ($hue < 300) {
            $red = $x;
            $green = 0;
            $blue = $chroma;
        } else {
            $red = $chroma;
            $green = 0;
            $blue = $x;
        }

        $red = ($red + $m) * 255;
        $green = ($green + $m) * 255;
        $blue = ($blue + $m) * 255;

        /**
         * To the closest integer
         */
        try {
            return ColorRgb::createFromRgbChannels(
                intval(round($red)),
                intval(round($green)),
                intval(round($blue))
            );
        } catch (ExceptionCombo $e) {
            // should not happen but yeah, who knows
            // and because there is no safe constructor, no safe default, we throw
            $message = "Error while creating the rgb color from the hsl ($this)";
            throw new ExceptionCombo($message, self::CANONICAL, 0, $e);
        }

    }

    /**
     * @throws ExceptionCombo
     */
    public function setSaturation(int $saturation): ColorHsl
    {
        if ($saturation < 0 || $saturation > 100) {
            throw new ExceptionCombo("Saturation should be between 0 and 100");
        }
        $this->saturation = $saturation;
        return $this;
    }

    public function toComplement(): ColorHsl
    {
        // Adjust Hue 180 degrees
        $this->hue += ($this->hue > 180) ? -180 : 180;
        return $this;
    }

    public function __toString()
    {
        return "hsl($this->hue deg, $this->saturation%, $this->lightness%)";
    }

    public function darken(int $lightness = 5): ColorHsl
    {
        if ($this->lightness - $lightness < 0) {
            $this->lightness = 0;
        }
        $this->lightness -= $lightness;
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function diff($color): array
    {
        if ($color instanceof ColorRgb) {
            $color = $color->toHsl();
        }

        return [
            "h" => round($this->getHue() - $color->getHue(), 2),
            "s" => round($this->getSaturation() - $color->getSaturation(), 2),
            "l" => round($this->getLightness() - $color->getLightness(), 2),
        ];
    }


}
