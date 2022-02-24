<?php


namespace ComboStrap;


class Brand
{

    const NEWSLETTER_BRAND_NAME = "newsletter";
    const EMAIL_BRAND_NAME = "email";

    public const BRAND_ABBREVIATIONS_MAPPING = [
        "hn" => "hackernews",
        "mail" => "email",
        "wp" => "wikipedia"
    ];
    /**
     * The brand of the current application/website
     */
    public const CURRENT_BRAND = "current";


    private $secondaryColor;
    private $brandUrl;

    /**
     * @var array
     */
    public static $brandDictionary;
    /**
     * @var bool
     */
    private $unknown = false;
    /**
     * @var mixed
     */
    private $brandDict;

    /**
     * Brand constructor.
     * @param string $name
     * @throws ExceptionCombo
     */
    public function __construct(string $name)
    {
        $this->name = strtolower($name);
        if (isset(self::BRAND_ABBREVIATIONS_MAPPING[$this->name])) {
            $this->name = self::BRAND_ABBREVIATIONS_MAPPING[$this->name];
        }

        /**
         * Get the brands
         */
        Brand::$brandDictionary = Brand::getBrandDictionary();


        /**
         * Build the data for the brand
         */
        $this->brandDict = Brand::$brandDictionary[$this->name];
        switch ($this->name) {
            case self::CURRENT_BRAND:
                $this->brandUrl = Site::getBaseUrl();
                $secondaryColor = Site::getSecondaryColor();
                if ($secondaryColor !== null) {
                    // the predicates on the secondary value is to avoid a loop with the the function below
                    $this->secondaryColor = $secondaryColor->toCssValue();
                }
                break;
            default:
                if ($this->brandDict !== null) {
                    $this->secondaryColor = $this->brandDict["colors"]["secondary"];
                    $this->brandUrl = $this->brandDict["url"];
                    return;
                }
                $this->unknown = true;
                break;
        }

    }

    /**
     * @return string[]
     * @throws ExceptionCombo
     */
    public static function getAllKnownBrandNames(): array
    {

        $brandsDict = self::getBrandNamesFromDictionary();
        $brandsAbbreviations = array_keys(self::BRAND_ABBREVIATIONS_MAPPING);
        return array_merge(
            $brandsDict,
            $brandsAbbreviations,
            [self::CURRENT_BRAND]
        );
    }

    /**
     * @throws ExceptionCombo
     */
    public static function getBrandNamesFromDictionary(): array
    {
        $brandDictionary = self::getBrandDictionary();
        return array_keys($brandDictionary);
    }

    /**
     * @throws ExceptionCombo
     */
    public static function getBrandDictionary(): array
    {
        if (Brand::$brandDictionary === null) {
            Brand::$brandDictionary = Dictionary::getFrom("brands");
        }
        return Brand::$brandDictionary;
    }


    /**
     * @var string
     * The name of the brand,
     * for company, we follow the naming of
     * https://github.com/ellisonleao/sharer.js
     */
    private $name;

    /**
     * @throws ExceptionCombo
     */
    public static function create(string $brandName): Brand
    {
        return new Brand($brandName);
    }

    /**
     * If the brand name is unknown (ie custom)
     * @return bool
     */
    public function isUnknown(): bool
    {
        return $this->unknown;

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString()
    {
        if ($this->name === Brand::CURRENT_BRAND) {
            return $this->name . " (" . Site::getTitle() . ")";
        }
        return $this->name;
    }

    /**
     * Shared/Follow Url template
     * the endpoint template url (for sharing and following)
     * @var string $type - the type of button
     */
    public function getWebUrlTemplate(string $type): ?string
    {
        if (isset($this->brandDict[$type])) {
            return $this->brandDict[$type]["web"];
        }
        return null;
    }

    /**
     * Brand button title
     * @return string
     * @var string $type - the button type
     */
    public function getTitle(string $type): ?string
    {
        if ($this->name === self::CURRENT_BRAND) {
            return Site::getTitle();
        }
        if ($this->brandDict !== null) {
            if (isset($this->brandDict[$type])) {
                return $this->brandDict[$type]["popup"];
            }
        }
        return null;

    }

    public function getPrimaryColor(): ?string
    {

        if ($this->brandDict !== null) {
            $primaryColor = $this->brandDict["colors"]["primary"];
            if ($primaryColor !== null) {
                return $primaryColor;
            }
        }

        // Unknown or current brand / unknown color
        $primaryColor = Site::getPrimaryColor();
        if ($primaryColor !== null) {
            return $primaryColor;
        }

        return null;

    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondaryColor;
    }

    /**
     * @param string $type - the button type
     * @return string|null
     */
    public function getIconName(string $type): ?string
    {

        switch ($this->name) {
            case self::CURRENT_BRAND:
                $image = Site::getLogoAsSvgImage();
                if ($image !== null) {
                    $path = $image->getPath();
                    if ($path instanceof DokuPath) {
                        /**
                         * End with svg, not seen as an external icon
                         */
                        return $path->getDokuwikiId();
                    }
                }
                break;
            default:
                if (isset($this->brandDict["icons"])) {
                    return $this->brandDict["icons"][$type];
                }
                break;
        }

        return null;
    }

    public function getBrandUrl(): ?string
    {
        return $this->brandUrl;
    }

    /**
     */
    public function supportButtonType(string $type): bool
    {
        switch ($type) {
            case BrandButton::TYPE_BUTTON_SHARE:
            case BrandButton::TYPE_BUTTON_FOLLOW:
                if ($this->getWebUrlTemplate($type) !== null) {
                    return true;
                }
                return false;
            default:
            case BrandButton::TYPE_BUTTON_BRAND:
                return true;
        }
    }


}
