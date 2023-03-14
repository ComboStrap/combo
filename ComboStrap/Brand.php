<?php


namespace ComboStrap;


class Brand
{

    const NEWSLETTER_BRAND_NAME = "newsletter";
    const EMAIL_BRAND_NAME = "email";


    /**
     * The brand of the current application/website
     */
    public const CURRENT_BRAND = "current";
    const CANONICAL = "brand";
    const ABBR_PROPERTY = 'abbr';
    /**
     * @var array an array of brand abbreviation as key and their name as value
     */
    private static array $BRAND_ABBR;


    private $secondaryColor;
    private $brandUrl;

    /**
     * @var array
     */
    public static array $brandDictionary;
    /**
     * @var bool
     */
    private bool $unknown = false;
    /**
     * @var mixed
     */
    private $brandDict;


    /**
     * Brand constructor.
     * @param string $name
     */
    private function __construct(string $name)
    {

        $this->name = $name;

        /**
         * Get the brands
         */
        $brandDictionary = Brand::getBrandDictionary();


        /**
         * Build the data for the brand
         */
        $this->brandDict = $brandDictionary[$this->name];
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
     */
    public static function getAllKnownBrandNames(): array
    {

        $brands = self::getAllBrands();
        $brandNames = [self::CURRENT_BRAND];
        foreach ($brands as $brand) {
            $brandNames[] = $brand->getName();
            try {
                $brandNames[] = $brand->getAbbr();
            } catch (ExceptionNotFound $e) {
                // ok
            }
        }
        return $brandNames;

    }


    /**
     * @return Brand[]
     */
    public static function getAllBrands(): array
    {
        $brandDictionary = self::getBrandDictionary();
        $brands = [];
        foreach (array_keys($brandDictionary) as $brandName) {
            $brands[] = self::create($brandName);
        }
        return $brands;
    }

    /**
     * @param $type - the button type (ie one of {@link BrandButton::TYPE_BUTTONS}
     * @return array - the brand names that can be used as type in the brand button
     */
    public static function getBrandNamesForButtonType($type): array
    {
        $brands = self::getAllBrands();
        $brandNamesForType = [];
        foreach ($brands as $brand) {
            if ($brand->supportButtonType($type)) {
                $brandNamesForType[] = $brand->getName();
                try {
                    $brandNamesForType[] = $brand->getAbbr();
                } catch (ExceptionNotFound $e) {
                    // ok
                }
            }
        }
        return $brandNamesForType;
    }

    /**
     *
     */
    public static function getBrandDictionary(): array
    {
        if (!isset(Brand::$brandDictionary)) {
            try {
                Brand::$brandDictionary = Dictionary::getFrom("brands");
            } catch (ExceptionCompile $e) {
                // Should never happens
                Brand::$brandDictionary = [];
                LogUtility::error("We can't load the brands dictionary. Error: " . $e->getMessage(), self::CANONICAL, $e);
            }
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


    public static function create(string $brandName): Brand
    {

        $brandNameQualified = strtolower($brandName);
        $brandNameQualified = Brand::getBrandNameFromAbbr($brandNameQualified);
        $objectIdentifier = self::CANONICAL . "-" . $brandNameQualified;
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            return $executionContext->getRuntimeObject($objectIdentifier);
        } catch (ExceptionNotFound $e) {
            $brandObject = new Brand($brandNameQualified);
            $executionContext->setRuntimeObject($objectIdentifier, $brandObject);
            return $brandObject;
        }

    }

    private static function getBrandNameFromAbbr(string $name)
    {
        if (!isset(self::$BRAND_ABBR)) {
            $brandDictionary = self::getBrandDictionary();
            foreach ($brandDictionary as $brandName => $brandProperties) {
                $abbr = $brandProperties[self::ABBR_PROPERTY];
                if (empty($abbr)) {
                    continue;
                }
                self::$BRAND_ABBR[$abbr] = $brandName;
            }
        }
        if (isset(self::$BRAND_ABBR[$name])) {
            return self::$BRAND_ABBR[$name];
        }
        return $name;

    }

    private static function getBrandAbbrFromDictionary()
    {

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
     * @param string|null $type - the button type
     * @return string|null
     */
    public function getIconName(?string $type): ?string
    {

        switch ($this->name) {
            case self::CURRENT_BRAND:
                try {
                    return Site::getLogoAsSvgImage()
                        ->getWikiId();
                } catch (ExceptionNotFound $e) {
                    // no logo installed
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

    /**
     * @throws ExceptionNotFound
     */
    private function getAbbr()
    {
        $value = $this->brandDict['abbr'];
        if (empty($value)) {
            throw new ExceptionNotFound("No abbreviations");
        }
        return $value;
    }


}
