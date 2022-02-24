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


use dokuwiki\StyleUtils;

class ColorRgb
{

    const COLOR = "color";

    const BORDER_COLOR = "border-color";

    const BOOTSTRAP_COLORS = array(
        'blue',
        'indigo',
        'purple',
        'pink',
        'red',
        'orange',
        'yellow',
        'green',
        'teal',
        'cyan',
        //'white', css value for now otherwise we don't know the value when tinting
        //'gray', css value for now otherwise we don't know the value when tinting
        'gray-dark',
        self::PRIMARY_VALUE,
        self::SECONDARY_VALUE,
        'success',
        'info',
        'warning',
        'danger',
        'light',
        'dark'
    );
    /**
     * https://drafts.csswg.org/css-color/#color-keywords
     */
    const CSS_COLOR_NAMES = array(
        'aliceblue' => '#F0F8FF',
        'antiquewhite' => '#FAEBD7',
        'aqua' => '#00FFFF',
        'aquamarine' => '#7FFFD4',
        'azure' => '#F0FFFF',
        'beige' => '#F5F5DC',
        'bisque' => '#FFE4C4',
        'black' => '#000000',
        'blanchedalmond' => '#FFEBCD',
        'blue' => '#0000FF',
        'blueviolet' => '#8A2BE2',
        'brown' => '#A52A2A',
        'burlywood' => '#DEB887',
        'cadetblue' => '#5F9EA0',
        'chartreuse' => '#7FFF00',
        'chocolate' => '#D2691E',
        'coral' => '#FF7F50',
        'cornflowerblue' => '#6495ED',
        'cornsilk' => '#FFF8DC',
        'crimson' => '#DC143C',
        'cyan' => '#00FFFF',
        'darkblue' => '#00008B',
        'darkcyan' => '#008B8B',
        'darkgoldenrod' => '#B8860B',
        'darkgray' => '#A9A9A9',
        'darkgreen' => '#006400',
        'darkgrey' => '#A9A9A9',
        'darkkhaki' => '#BDB76B',
        'darkmagenta' => '#8B008B',
        'darkolivegreen' => '#556B2F',
        'darkorange' => '#FF8C00',
        'darkorchid' => '#9932CC',
        'darkred' => '#8B0000',
        'darksalmon' => '#E9967A',
        'darkseagreen' => '#8FBC8F',
        'darkslateblue' => '#483D8B',
        'darkslategray' => '#2F4F4F',
        'darkslategrey' => '#2F4F4F',
        'darkturquoise' => '#00CED1',
        'darkviolet' => '#9400D3',
        'deeppink' => '#FF1493',
        'deepskyblue' => '#00BFFF',
        'dimgray' => '#696969',
        'dimgrey' => '#696969',
        'dodgerblue' => '#1E90FF',
        'firebrick' => '#B22222',
        'floralwhite' => '#FFFAF0',
        'forestgreen' => '#228B22',
        'fuchsia' => '#FF00FF',
        'gainsboro' => '#DCDCDC',
        'ghostwhite' => '#F8F8FF',
        'gold' => '#FFD700',
        'goldenrod' => '#DAA520',
        'gray' => '#808080',
        'green' => '#008000',
        'greenyellow' => '#ADFF2F',
        'grey' => '#808080',
        'honeydew' => '#F0FFF0',
        'hotpink' => '#FF69B4',
        'indianred' => '#CD5C5C',
        'indigo' => '#4B0082',
        'ivory' => '#FFFFF0',
        'khaki' => '#F0E68C',
        'lavender' => '#E6E6FA',
        'lavenderblush' => '#FFF0F5',
        'lawngreen' => '#7CFC00',
        'lemonchiffon' => '#FFFACD',
        'lightblue' => '#ADD8E6',
        'lightcoral' => '#F08080',
        'lightcyan' => '#E0FFFF',
        'lightgoldenrodyellow' => '#FAFAD2',
        'lightgray' => '#D3D3D3',
        'lightgreen' => '#90EE90',
        'lightgrey' => '#D3D3D3',
        'lightpink' => '#FFB6C1',
        'lightsalmon' => '#FFA07A',
        'lightseagreen' => '#20B2AA',
        'lightskyblue' => '#87CEFA',
        'lightslategray' => '#778899',
        'lightslategrey' => '#778899',
        'lightsteelblue' => '#B0C4DE',
        'lightyellow' => '#FFFFE0',
        'lime' => '#00FF00',
        'limegreen' => '#32CD32',
        'linen' => '#FAF0E6',
        'magenta' => '#FF00FF',
        'maroon' => '#800000',
        'mediumaquamarine' => '#66CDAA',
        'mediumblue' => '#0000CD',
        'mediumorchid' => '#BA55D3',
        'mediumpurple' => '#9370DB',
        'mediumseagreen' => '#3CB371',
        'mediumslateblue' => '#7B68EE',
        'mediumspringgreen' => '#00FA9A',
        'mediumturquoise' => '#48D1CC',
        'mediumvioletred' => '#C71585',
        'midnightblue' => '#191970',
        'mintcream' => '#F5FFFA',
        'mistyrose' => '#FFE4E1',
        'moccasin' => '#FFE4B5',
        'navajowhite' => '#FFDEAD',
        'navy' => '#000080',
        'oldlace' => '#FDF5E6',
        'olive' => '#808000',
        'olivedrab' => '#6B8E23',
        'orange' => '#FFA500',
        'orangered' => '#FF4500',
        'orchid' => '#DA70D6',
        'palegoldenrod' => '#EEE8AA',
        'palegreen' => '#98FB98',
        'paleturquoise' => '#AFEEEE',
        'palevioletred' => '#DB7093',
        'papayawhip' => '#FFEFD5',
        'peachpuff' => '#FFDAB9',
        'peru' => '#CD853F',
        'pink' => '#FFC0CB',
        'plum' => '#DDA0DD',
        'powderblue' => '#B0E0E6',
        'purple' => '#800080',
        'rebeccapurple' => '#663399',
        'red' => '#FF0000',
        'rosybrown' => '#BC8F8F',
        'royalblue' => '#4169E1',
        'saddlebrown' => '#8B4513',
        'salmon' => '#FA8072',
        'sandybrown' => '#F4A460',
        'seagreen' => '#2E8B57',
        'seashell' => '#FFF5EE',
        'sienna' => '#A0522D',
        'silver' => '#C0C0C0',
        'skyblue' => '#87CEEB',
        'slateblue' => '#6A5ACD',
        'slategray' => '#708090',
        'slategrey' => '#708090',
        'snow' => '#FFFAFA',
        'springgreen' => '#00FF7F',
        'steelblue' => '#4682B4',
        'tan' => '#D2B48C',
        'teal' => '#008080',
        'tip' => self::TIP_COLOR,
        'thistle' => '#D8BFD8',
        'tomato' => '#FF6347',
        'turquoise' => '#40E0D0',
        'violet' => '#EE82EE',
        'wheat' => '#F5DEB3',
        'white' => '#FFFFFF',
        'whitesmoke' => '#F5F5F5',
        'yellow' => '#FFFF00',
        'yellowgreen' => '#9ACD32'
    );

    /**
     * Branding colors
     */
    const PRIMARY_VALUE = "primary";
    const SECONDARY_VALUE = "secondary";

    const PRIMARY_COLOR_CONF = "primaryColor";
    const SECONDARY_COLOR_CONF = "secondaryColor";
    const BRANDING_COLOR_CANONICAL = "branding-colors";
    public const BACKGROUND_COLOR = "background-color";

    // the value is a bootstrap name
    const VALUE_TYPE_BOOTSTRAP_NAME = "bootstrap";
    const VALUE_TYPE_RGB_HEX = "rgb-hex";
    const VALUE_TYPE_RGB_ARRAY = "rgb-array";
    const VALUE_TYPE_RESET = "reset";
    const VALUE_TYPE_CSS_NAME = "css-name";
    const VALUE_TYPE_UNKNOWN_NAME = "unknown-name";

    /**
     * Do we set also the branding color on
     * other elements ?
     */
    const BRANDING_COLOR_INHERITANCE_ENABLE_CONF = "brandingColorInheritanceEnable";
    const BRANDING_COLOR_INHERITANCE_ENABLE_CONF_DEFAULT = 1;

    /**
     * Minimum recommended ratio by the w3c
     */
    const MINIMUM_CONTRAST_RATIO = 5;
    const WHITE = "white";
    const TIP_COLOR = "#ffee33";
    const CURRENT_COLOR = "currentColor";

    /**
     * @var array
     */
    private static $dokuWikiStyles;

    /**
     * @var int
     */
    private $red;
    /**
     * @var mixed
     */
    private $green;
    /**
     * @var mixed
     */
    private $blue;
    /**
     * @var string
     */
    private $nameType = self::VALUE_TYPE_UNKNOWN_NAME;
    /**
     * The color name
     * It can be:
     *   * a bootstrap
     *   * a css name
     *   * or `reset`
     * @var null|string
     */
    private $name;
    private $transparency;


    /**
     * The styles of the dokuwiki systems
     * @return array
     */
    public static function getDokuWikiStyles(): array
    {
        if (self::$dokuWikiStyles === null) {
            self::$dokuWikiStyles = (new StyleUtils())->cssStyleini();
        }
        return self::$dokuWikiStyles;

    }

    /**
     * Same round instructions than SCSS to be able to do the test
     * have value as bootstrap
     * @param float $value
     * @return float
     */
    private static function round(float $value): float
    {
        $rest = fmod($value, 1);
        if ($rest < 0.5) {
            return round($value, 0, PHP_ROUND_HALF_DOWN);
        } else {
            return round($value, 0, PHP_ROUND_HALF_UP);
        }
    }

    /**
     * @throws ExceptionCombo
     */
    public static function createFromRgbChannels(int $red, int $green, int $blue): ColorRgb
    {
        return (new ColorRgb())
            ->setRgbChannels([$red, $green, $blue]);
    }

    /**
     * Utility function to get white
     * @throws ExceptionCombo
     */
    public static function getWhite(): ColorRgb
    {

        return (new ColorRgb())
            ->setName("white")
            ->setRgbChannels([255, 255, 255])
            ->setNameType(self::VALUE_TYPE_CSS_NAME);

    }

    /**
     * Utility function to get black
     * @throws ExceptionCombo
     */
    public static function getBlack(): ColorRgb
    {

        return (new ColorRgb())
            ->setName("black")
            ->setRgbChannels([0, 0, 0])
            ->setNameType(self::VALUE_TYPE_CSS_NAME);

    }

    /**
     * @throws ExceptionCombo
     */
    public static function createFromHex(string $color)
    {

        return (new ColorRgb())
            ->setHex($color);


    }


    /**
     * @return ColorHsl
     * @throws ExceptionCombo
     */
    public function toHsl(): ColorHsl
    {

        if ($this->red === null) {
            throw new ExceptionCombo("This color ($this) does not have any channel known, we can't transform it to hsl");
        }
        $red = $this->red / 255;
        $green = $this->green / 255;
        $blue = $this->blue / 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);


        $lightness = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0) {
            $hue = $saturation = 0; // achromatic
        } else {
            $saturation = $d / (1 - abs(2 * $lightness - 1));

            switch ($max) {
                case $red:
                    $hue = 60 * fmod((($green - $blue) / $d), 6);
                    if ($blue > $green) {
                        $hue += 360;
                    }
                    break;

                case $green:
                    $hue = 60 * (($blue - $red) / $d + 2);
                    break;

                default:
                case $blue:
                    $hue = 60 * (($red - $green) / $d + 4);
                    break;

            }
        }

        /**
         * No round to get a neat inverse
         */

        return ColorHsl::createFromChannels($hue, $saturation * 100, $lightness * 100);


    }


    /**
     * @param array|string|ColorRgb $color
     * @param int|null $weight
     * @return ColorRgb
     *
     *
     * Because Bootstrap uses the mix function of SCSS
     * https://sass-lang.com/documentation/modules/color#mix
     * We try to be as clause as possible
     *
     * https://gist.github.com/jedfoster/7939513
     *
     * This is a linear extrapolation along the segment
     * @throws ExceptionCombo
     */
    function mix($color, ?int $weight = 50): ColorRgb
    {
        if ($weight === null) {
            $weight = 50;
        }

        $color2 = ColorRgb::createFromString($color);
        $targetRed = self::round(Math::lerp($color2->getRed(), $this->getRed(), $weight));
        $targetGreen = self::round(Math::lerp($color2->getGreen(), $this->getGreen(), $weight));
        $targetBlue = self::round(Math::lerp($color2->getBlue(), $this->getBlue(), $weight));
        return ColorRgb::createFromRgbChannels($targetRed, $targetGreen, $targetBlue);

    }

    /**
     * @throws ExceptionCombo
     */
    function unmix($color, ?int $weight = 50): ColorRgb
    {
        if ($weight === null) {
            $weight = 50;
        }

        $color2 = ColorRgb::createFromString($color);
        $targetRed = self::round(Math::unlerp($color2->getRed(), $this->getRed(), $weight));
        if ($targetRed < 0) {
            throw new ExceptionCombo("This is not possible, the red value ({$color2->getBlue()}) with the percentage $weight could not be unmixed. They were not calculated with color mixing.");
        }
        $targetGreen = self::round(Math::unlerp($color2->getGreen(), $this->getGreen(), $weight));
        if ($targetGreen < 0) {
            throw new ExceptionCombo("This is not possible, the green value ({$color2->getGreen()}) with the percentage $weight could not be unmixed. They were not calculated with color mixing.");
        }
        $targetBlue = self::round(Math::unlerp($color2->getBlue(), $this->getBlue(), $weight));
        if ($targetBlue < 0) {
            throw new ExceptionCombo("This is not possible, the blue value ({$color2->getBlue()}) with the percentage $weight could not be unmixed. They were not calculated with color mixing.");
        }
        return ColorRgb::createFromRgbChannels($targetRed, $targetGreen, $targetBlue);

    }

    /**
     * Takes an hexadecimal color and returns the rgb channels
     *
     * @param mixed $hex
     *
     * @throws ExceptionCombo
     */
    function hex2rgb($hex = '#000000'): array
    {
        if ($hex[0] !== "#") {
            throw new ExceptionCombo("The color value ($hex) does not start with a #, this is not valid CSS hexadecimal color value");
        }
        $digits = str_replace("#", "", $hex);
        $hexLen = strlen($digits);
        $transparency = false;
        switch ($hexLen) {
            case 3:
                $lengthColorHex = 1;
                break;
            case 6:
                $lengthColorHex = 2;
                break;
            case 8:
                $lengthColorHex = 2;
                $transparency = true;
                break;
            default:
                throw new ExceptionCombo("The digit color value ($hex) is not 3 or 6 in length, this is not a valid CSS hexadecimal color value");
        }
        $result = preg_match("/[0-9a-f]{3,8}/i", $digits);
        if ($result !== 1) {
            throw new ExceptionCombo("The digit color value ($hex) is not a hexadecimal value, this is not a valid CSS hexadecimal color value");
        }
        $channelHexs = str_split($digits, $lengthColorHex);
        $rgbDec = [];
        foreach ($channelHexs as $channelHex) {
            if ($lengthColorHex === 1) {
                $channelHex .= $channelHex;
            }
            $rgbDec[] = hexdec($channelHex);
        }
        if (!$transparency) {
            $rgbDec[] = null;
        }
        return $rgbDec;
    }

    /**
     * rgb2hex
     *
     * @return string
     */
    function toRgbHex(): string
    {

        switch ($this->nameType) {
            case self::VALUE_TYPE_CSS_NAME:
                return strtolower(self::CSS_COLOR_NAMES[$this->name]);
            default:
                $toCssHex = function ($x) {
                    return str_pad(dechex($x), 2, "0", STR_PAD_LEFT);
                };
                $redHex = $toCssHex($this->getRed());
                $greenHex = $toCssHex($this->getGreen());
                $blueHex = $toCssHex($this->getBlue());
                $withoutAlpha = "#" . $redHex . $greenHex . $blueHex;
                if ($this->transparency === null) {
                    return $withoutAlpha;
                }
                return $withoutAlpha . $toCssHex($this->getTransparency());
        }

    }

    /**
     * @throws ExceptionCombo
     */
    public
    static function createFromString(string $color): ColorRgb
    {
        if ($color[0] === "#") {
            return self::createFromHex($color);
        } else {
            return self::createFromName($color);
        }
    }

    /**
     * @throws ExceptionCombo
     */
    public
    static function createFromName(string $color): ColorRgb
    {
        return (new ColorRgb())
            ->setName($color);
    }


    public
    function toCssValue(): string
    {

        switch ($this->nameType) {
            case self::VALUE_TYPE_RGB_ARRAY:
                return $this->toRgbHex();
            case self::VALUE_TYPE_CSS_NAME:
            case self::VALUE_TYPE_RGB_HEX;
                return $this->name;
            case self::VALUE_TYPE_BOOTSTRAP_NAME:
                $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
                switch ($bootstrapVersion) {
                    case Bootstrap::BootStrapFiveMajorVersion:
                        $colorValue = "bs-" . $this->name;
                        break;
                    default:
                        $colorValue = $this->name;
                        break;
                }
                return "var(--" . $colorValue . ")";
            case self::VALUE_TYPE_RESET:
                return "inherit!important";
            default:
                // unknown color name
                if ($this->name === null) {
                    LogUtility::msg("The name should not be null");
                    return "black";
                }
                return $this->name;
        }


    }

    public
    function getRed()
    {
        return $this->red;
    }

    public
    function getGreen()
    {
        return $this->green;
    }

    public
    function getBlue()
    {
        return $this->blue;
    }

    /**
     * Mix with black
     * @var int $percentage between 0 and 100
     */
    public
    function shade(int $percentage): ColorRgb
    {
        try {
            return $this->mix('black', $percentage);
        } catch (ExceptionCombo $e) {
            // should not happen
            LogUtility::msg("Error while shading. Error: {$e->getMessage()}");
            return $this;
        }
    }

    public
    function getNameType(): string
    {
        return $this->nameType;
    }

    /**
     * @param int $percentage between -100 and 100
     * @return $this
     */
    public
    function scale(int $percentage): ColorRgb
    {
        if ($percentage === 0) {
            return $this;
        }
        if ($percentage > 0) {
            return $this->shade($percentage);
        } else {
            return $this->tint(abs($percentage));
        }

    }


    public
    function toRgbChannels(): array
    {
        return [$this->getRed(), $this->getGreen(), $this->getBlue()];
    }

    /**
     * @param int $percentage between 0 and 100
     * @return $this
     */
    public
    function tint(int $percentage): ColorRgb
    {
        try {
            return $this->mix("white", $percentage);
        } catch (ExceptionCombo $e) {
            // should not happen
            LogUtility::msg("Error while tinting ($this) with a percentage ($percentage. Error: {$e->getMessage()}");
            return $this;
        }
    }

    public
    function __toString()
    {
        return $this->toCssValue();
    }

    public
    function getLuminance(): float
    {
        $toLuminanceFactor = function ($channel) {
            $pigmentRatio = $channel / 255;
            return $pigmentRatio <= 0.03928 ? $pigmentRatio / 12.92 : pow(($pigmentRatio + 0.055) / 1.055, 2.4);
        };
        $R = $toLuminanceFactor($this->getRed());
        $G = $toLuminanceFactor($this->getGreen());
        $B = $toLuminanceFactor($this->getBlue());
        return $R * 0.2126 + $G * 0.7152 + $B * 0.0722;

    }

    /**
     * The ratio that returns the chrome browser
     * @param ColorRgb $colorRgb
     * @return float
     * @throws ExceptionCombo
     */
    public
    function getContrastRatio(ColorRgb $colorRgb): float
    {
        $actualColorHsl = $this->toHsl();
        $actualLightness = $actualColorHsl->getLightness();
        $targetColorHsl = $colorRgb->toHsl();
        $targetLightNess = $targetColorHsl->getLightness();
        if ($actualLightness > $targetLightNess) {
            $lighter = $this;
            $darker = $colorRgb;
        } else {
            $lighter = $colorRgb;
            $darker = $this;
        }
        $ratio = ($lighter->getLuminance() + 0.05) / ($darker->getLuminance() + 0.05);
        return floor($ratio * 100) / 100;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function toMinimumContrastRatio(string $color, float $minimum = self::MINIMUM_CONTRAST_RATIO, $darknessIncrement = 5): ColorRgb
    {
        $targetColor = ColorRgb::createFromString($color);
        $ratio = $this->getContrastRatio($targetColor);
        $newColorRgb = $this;
        $newColorHsl = $this->toHsl();
        while ($ratio < $minimum) {
            $newColorHsl = $newColorHsl->darken($darknessIncrement);
            $newColorRgb = $newColorHsl->toRgb();
            if ($newColorHsl->getLightness() === 0) {
                break;
            }
            $ratio = $newColorRgb->getContrastRatio($targetColor);
        }
        return $newColorRgb;
    }

    /**
     * Returns the complimentary color
     */
    public
    function complementary(): ColorRgb
    {
        try {
            return $this
                ->toHsl()
                ->toComplement()
                ->toRgb();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while getting the complementary color of ($this). Error: {$e->getMessage()}");
            return $this;
        }

    }

    public
    function getName(): string
    {
        $hexColor = $this->toRgbHex();
        if (in_array($hexColor, self::CSS_COLOR_NAMES)) {
            return self::CSS_COLOR_NAMES[$hexColor];
        }
        return $hexColor;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function toMinimumContrastRatioAgainstWhite(float $minimumContrastRatio = self::MINIMUM_CONTRAST_RATIO, int $darknessIncrement = 5): ColorRgb
    {
        return $this->toMinimumContrastRatio(self::WHITE, $minimumContrastRatio, $darknessIncrement);
    }

    /**
     * @throws ExceptionCombo
     */
    private
    function setHex(string $color): ColorRgb
    {
        // Hexadecimal
        if ($color[0] !== "#") {
            throw new ExceptionCombo("The value is not an hexadecimal color value ($color)");
        }
        [$this->red, $this->green, $this->blue, $this->transparency] = $this->hex2rgb($color);
        $this->nameType = self::VALUE_TYPE_RGB_HEX;
        $this->name = $color;
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public
    function setRgbChannels(array $colorValue): ColorRgb
    {
        if (sizeof($colorValue) != 3) {
            throw new ExceptionCombo("A rgb color array should be of length 3");
        }
        foreach ($colorValue as $color) {
            try {
                $channel = DataType::toInteger($color);
            } catch (ExceptionCombo $e) {
                throw new ExceptionCombo("The rgb color $color is not an integer. Error: {$e->getMessage()}");
            }
            if ($channel < 0 and $channel > 255) {
                throw new ExceptionCombo("The rgb color $color is not between 0 and 255");
            }
        }
        [$this->red, $this->green, $this->blue] = $colorValue;
        $this->nameType = self::VALUE_TYPE_RGB_ARRAY;
        return $this;
    }

    private
    function setNameType(string $type): ColorRgb
    {
        $this->nameType = $type;
        return $this;
    }

    /**
     * Via a name
     * @throws ExceptionCombo
     */
    private
    function setName(string $name): ColorRgb
    {

        $qualifiedName = strtolower($name);
        $this->name = $qualifiedName;
        if (in_array($qualifiedName, self::BOOTSTRAP_COLORS)) {
            /**
             * Branding colors overwrite
             */
            switch ($this->name) {
                case ColorRgb::PRIMARY_VALUE:
                    $primaryColor = Site::getPrimaryColorValue();
                    if ($primaryColor !== null) {
                        if ($primaryColor !== ColorRgb::PRIMARY_VALUE) {
                            return self::createFromString($primaryColor);
                        }
                        LogUtility::msg("The primary color cannot be set with the value primary. Default to bootstrap color.", self::BRANDING_COLOR_CANONICAL);
                    }
                    break;
                case ColorRgb::SECONDARY_VALUE:
                    $secondaryColor = Site::getSecondaryColorValue();
                    if ($secondaryColor !== null) {
                        if ($secondaryColor !== ColorRgb::SECONDARY_VALUE) {
                            return self::createFromString($secondaryColor);
                        }
                        LogUtility::msg("The secondary color cannot be set with the value secondary. Default to bootstrap color.", self::BRANDING_COLOR_CANONICAL);
                    }
                    break;
            }

            return $this->setNameType(self::VALUE_TYPE_BOOTSTRAP_NAME);
        }
        if ($qualifiedName === self::VALUE_TYPE_RESET) {
            return $this->setNameType(self::VALUE_TYPE_RESET);
        }
        if (in_array($qualifiedName, array_keys(self::CSS_COLOR_NAMES))) {
            $this->setHex(self::CSS_COLOR_NAMES[$qualifiedName])
                ->setNameType(self::VALUE_TYPE_CSS_NAME);
            $this->name = $qualifiedName; // hex is a also a name, the previous setHex overwrite the name
            return $this;
        }
        LogUtility::msg("The color name ($name) is unknown");
        return $this;

    }

    public
    function getTransparency()
    {
        return $this->transparency;
    }




}
