<?php


namespace ComboStrap;


class PageImage
{
    const PAGE_IMAGE = "page-image";
    const ICON = "icon"; // next release ?
    const SOCIAL = "social";
    const FACEBOOK = "facebook";
    const GOOGLE = "google";
    const TWITTER = "twitter";
    const ALL = "all";
    const DEFAULT = self::ALL;
    const USAGE_ATTRIBUTE = "usage";
    const PATH_ATTRIBUTE = "path";

    /**
     * @var Image
     */
    private $image;
    private $usages;
    /**
     * @var Page
     */
    private $page;

    /**
     * PageImage constructor.
     */
    public function __construct(Image $image, Page $page)
    {
        $this->image = $image;
        $this->page = $page;
    }

    /**
     * @param Image|string $image
     * @param Page $page
     * @return PageImage
     */
    public static function create($image, Page $page): PageImage
    {
        if (!($image instanceof Image)) {
            $image = Image::createImageFromDokuwikiAbsolutePath($image);
        }
        return new PageImage($image, $page);
    }

    /**
     * @param array $usages
     * @return $this
     * @throws ExceptionCombo
     */
    public function setUsages(array $usages): PageImage
    {
        foreach ($usages as $usage) {
            $value = trim($usage);
            if (!in_array($value, self::getUsageValues())) {
                throw new ExceptionCombo("The page image usage value ($value) is not valid.");
            }
            $this->usages[$value] = $value;
        }
        return $this;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function getUsages(): array
    {
        if ($this->usages === null) {
            return [];
        }
        return array_values($this->usages);
    }

    public static function getDefaultUsage(): array
    {
        return [self::DEFAULT];
    }

    public static function getUsageValues(): array
    {
        return [
            self::ALL,
            self::ICON,
            self::GOOGLE,
            self::FACEBOOK,
            self::PAGE_IMAGE,
            self::SOCIAL,
            self::TWITTER,
        ];

    }


}
