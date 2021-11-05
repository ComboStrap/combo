<?php


namespace ComboStrap;


class PageImage
{
    const PAGE_IMAGE = "page-image";
    const ICON = "link-icon"; // next release ?
    const SOCIAL = "social";
    const FACEBOOK = "facebook";
    const GOOGLE = "google";
    const TWITTER = "twitter";
    const ALL = "all";
    const DEFAULT = self::ALL;
    const USAGE_ATTRIBUTE = "usage";

    /**
     * @var Image
     */
    private $image;
    private $usage = [self::DEFAULT];

    /**
     * PageImage constructor.
     */
    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    /**
     * @param Image|string $image
     * @return PageImage
     */
    public static function create($image): PageImage
    {
        if (!($image instanceof Image)) {
            $image = Image::createImageFromDokuwikiAbsolutePath($image);
        }
        return new PageImage($image);
    }

    /**
     * @param array $usage
     * @return $this
     */
    public function setUsage(array $usage): PageImage
    {
        $this->usage = $usage;
        return $this;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function getUsage(): array
    {
        return $this->usage;
    }

    public static function getDefaultUsage(): array
    {
        return [self::DEFAULT];
    }

    public static function getUsageValues(): array
    {
        return [
            self::ALL,
            self::GOOGLE,
            self::FACEBOOK,
            self::PAGE_IMAGE,
            self::SOCIAL,
            self::TWITTER,
        ];

    }


}
