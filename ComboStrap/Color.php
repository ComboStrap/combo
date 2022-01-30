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

class Color
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
        'white',
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
    const WEB_COLORS = array(
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
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return array
     */
    public static function rgbToHsl(int $red, int $green, int $blue): array
    {

        $red = $red / 255;
        $green = $green / 255;
        $blue = $blue / 255;

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
        return array($hue, $saturation, $lightness);
    }

    /**
     * @param float $hue (0 to 360)
     * @param float $saturation (range 0 to 1)
     * @param float $lightness (range 0 to 1)
     * @return array
     *
     * Reference:
     * https://en.wikipedia.org/wiki/HSL_and_HSV#HSL_to_RGB
     * https://gist.github.com/brandonheyer/5254516
     */
    static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {

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
        $red = intval(round($red));
        $green = intval(round($green));
        $blue = intval(round($blue));
        return array($red, $green, $blue);
    }

    /**
     * @throws ExceptionCombo
     */
    private static function createFromRgbArray($array)
    {
        return new Color($array);
    }

    /**
     * @param mixed $color2Value
     * @param int|null $weight
     * @return Color
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
    function mix($color2Value, ?int $weight = 50): Color
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

        $color2 = Color::create($color2Value);
        $targetRed = $lerp($this->getRed(),$color2->getRed());
        $targetGreen = $lerp($this->getGreen(),$color2->getGreen());
        $targetBlue = $lerp($this->getBlue(),$color2->getBlue());
        return Color::createFromRgbArray(
            [
                $targetRed,
                $targetGreen,
                $targetBlue
            ]
        ) ;

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
    function toHex(): string
    {
        $f = function ($x) {
            return str_pad(dechex($x), 2, "0", STR_PAD_LEFT);
        };

        return "#" . implode("", array_map($f, [
                    $this->getRed(),
                    $this->getGreen(),
                    $this->getBlue()
                ]
            ));
    }

    /**
     * @throws ExceptionCombo
     */
    public static function create(string $color): Color
    {
        return new Color($color);
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
            return;
        }
        if ($colorValue[0] == "#") {
            [$this->red, $this->green, $this->blue] = $this->hex2rgb($colorValue);
        }
    }

    public function toCssValue(): string
    {
        $color = $this->colorValue;
        if ($color[0] == "#") {
            return $color;
        }
        $lowerColor = strtolower($color);
        if ($lowerColor == "reset") {
            $colorValue = "inherit!important";
        } else {
            // Custom Css variable
            if (in_array($lowerColor, self::BOOTSTRAP_COLORS)) {
                if ($lowerColor === self::PRIMARY_VALUE) {
                    $primaryColor = Site::getPrimaryColor();
                    if ($primaryColor !== null) {
                        return $primaryColor;
                    }
                }
                if ($lowerColor === self::SECONDARY_VALUE) {
                    $secondaryColor = Site::getSecondaryColor();
                    if ($secondaryColor !== null) {
                        return $secondaryColor;
                    }
                }
                $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
                switch ($bootstrapVersion) {
                    case Bootstrap::BootStrapFiveMajorVersion:
                        $colorValue = "bs-" . $lowerColor;
                        break;
                    default:
                        $colorValue = $lowerColor;
                        break;
                }
                $colorValue = "var(--" . $colorValue . ")";
            } else {
                // css color name
                $colorValue = $lowerColor;
            }
        }
        return $colorValue;
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


}
