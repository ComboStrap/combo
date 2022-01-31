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
        //'white', css value for now
        'gray',
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
    const VALUE_TYPE_BRANDING = "branding";

    /**
     * The shade / shift weight factor on text color
     */
    const TEXT_BOOTSTRAP_WEIGHT = 40;

    /**
     * Do we set also the branding color on
     * other elements ?
     */
    const BRANDING_COLOR_INHERITANCE_ENABLE_CONF = "brandingColorInheritanceEnable";
    const BRANDING_COLOR_INHERITANCE_ENABLE_CONF_DEFAULT = 1;

    /**
     * @var array
     */
    private static $dokuWikiStyles;
    /**
     * @var string
     */
    private $colorValue;
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
    private $type = self::VALUE_TYPE_UNKNOWN_NAME;



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
     * @throws ExceptionCombo
     */
    public static function createFromRgbChannels(int $red, int $green, int $blue): ColorRgb
    {
        return new ColorRgb([$red,$green,$blue]);
    }


    /**
     * @return ColorHsl
     */
    public function toHsl(): ColorHsl
    {

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

        return ColorHsl::createFromChannels($hue,$saturation*100,$lightness*100);


    }


    /**
     * @throws ExceptionCombo
     */
    private static function createFromRgbArray($array): ColorRgb
    {
        return new ColorRgb($array);
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

        $lerp = function ($x, $y) use ($weight) {
            $X = ($weight * $x) / 100;
            $Y = (100 - $weight) / 100 * $y;
            $v = $X + $Y;
            $rest = fmod($v, 1);
            if ($rest < 0.5) {
                return round($v, 0, PHP_ROUND_HALF_DOWN);
            } else {
                return round($v, 0, PHP_ROUND_HALF_UP);
            }
        };

        $color2 = ColorRgb::createFromString($color);
        $targetRed = $lerp($color2->getRed(), $this->getRed());
        $targetGreen = $lerp($color2->getGreen(), $this->getGreen());
        $targetBlue = $lerp($color2->getBlue(), $this->getBlue());
        return ColorRgb::createFromRgbArray(
            [
                $targetRed,
                $targetGreen,
                $targetBlue
            ]
        );

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
        switch ($hexLen) {
            case 3:
                $splitLength = 1;
                break;
            case 6:
                $splitLength = 2;
                break;
            default:
                throw new ExceptionCombo("The digit color value ($hex) is not 3 or 6 in length, this is not a valid CSS hexadecimal color value");
        }
        $result = preg_match("/[0-9a-f]{3,6}/i", $digits);
        if ($result !== 1) {
            throw new ExceptionCombo("The digit color value ($hex) is not a hexadecimal value, this is not a valid CSS hexadecimal color value");
        }
        $channelHexs = str_split($digits, $splitLength);
        $rgbDec = [];
        foreach ($channelHexs as $channelHex) {
            if ($splitLength === 1) {
                $channelHex .= $channelHex;
            }
            $rgbDec[] = hexdec($channelHex);
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
        $toCssHex = function ($x) {
            return str_pad(dechex($x), 2, "0", STR_PAD_LEFT);
        };

        $redHex = $toCssHex($this->getRed());
        $greenHex = $toCssHex($this->getGreen());
        $blueHex = $toCssHex($this->getBlue());
        return "#" . $redHex . $greenHex . $blueHex;
    }

    /**
     * @throws ExceptionCombo
     */
    public static function createFromString(string $color): ColorRgb
    {
        return new ColorRgb($color);
    }

    /**
     * @throws ExceptionCombo
     */
    public function __construct($colorValue)
    {

        $this->colorValue = $colorValue;
        if (is_array($colorValue)) {
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
            $this->type = self::VALUE_TYPE_RGB_ARRAY;
            return;
        }
        // Hexadecimal
        if ($colorValue[0] == "#") {
            [$this->red, $this->green, $this->blue] = $this->hex2rgb($colorValue);
            $this->type = self::VALUE_TYPE_RGB_HEX;
            return;
        }
        // Color Name
        // Custom Css variable
        $this->colorValue = strtolower($colorValue);
        if (in_array($this->colorValue, self::BOOTSTRAP_COLORS)) {
            $this->type = self::VALUE_TYPE_BOOTSTRAP_NAME;
            // value are unknown for now
            if (in_array($this->colorValue, array_keys(self::CSS_COLOR_NAMES))) {
                $value = self::CSS_COLOR_NAMES[$this->colorValue];
                [$this->red, $this->green, $this->blue] = $this->hex2rgb($value);
            }
            return;
        }
        if ($this->colorValue === self::VALUE_TYPE_RESET) {
            $this->type = self::VALUE_TYPE_RESET;
            return;
        }
        if (in_array($this->colorValue, array_keys(self::CSS_COLOR_NAMES))) {
            $this->type = self::VALUE_TYPE_CSS_NAME;
            $value = self::CSS_COLOR_NAMES[$this->colorValue];
            [$this->red, $this->green, $this->blue] = $this->hex2rgb($value);
            return;
        }
        // unknown css name
    }

    public function toCssValue(): string
    {

        switch ($this->type) {
            case self::VALUE_TYPE_RGB_ARRAY:
                return $this->toRgbHex();
            case self::VALUE_TYPE_RGB_HEX;
                return $this->colorValue;
            case self::VALUE_TYPE_BOOTSTRAP_NAME:


                $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
                switch ($bootstrapVersion) {
                    case Bootstrap::BootStrapFiveMajorVersion:
                        $colorValue = "bs-" . $this->colorValue;
                        break;
                    default:
                        $colorValue = $this->colorValue;
                        break;
                }
                return "var(--" . $colorValue . ")";
            case self::VALUE_TYPE_RESET:
                return "inherit!important";
            default:
                // unknown css color name
                return $this->colorValue;
        }


    }

    public function getRed()
    {
        return $this->red;
    }

    public function getGreen()
    {
        return $this->green;
    }

    public function getBlue()
    {
        return $this->blue;
    }

    /**
     * Mix with black
     */
    public function shade($weight): ColorRgb
    {
        try {
            return $this->mix('black', $weight);
        } catch (ExceptionCombo $e) {
            // should not happen
            LogUtility::msg("Error while shading. Error: {$e->getMessage()}");
            return $this;
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function shift(int $percentage): ColorRgb
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





    public function toRgbChannels(): array
    {
        return [$this->getRed(), $this->getGreen(), $this->getBlue()];
    }

    public function tint(int $percentage): ColorRgb
    {
        try {
            return $this->mix("white", $percentage);
        } catch (ExceptionCombo $e) {
            // should not happen
            LogUtility::msg("Error while tinting ($this) with a percentage ($percentage. Error: {$e->getMessage()}");
            return $this;
        }
    }

    public function __toString()
    {
        return $this->colorValue;
    }




}