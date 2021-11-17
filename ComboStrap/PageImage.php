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
    const PATH_ATTRIBUTE = "path";
    const CANONICAL = "page:image";

    /**
     * @var Image
     */
    private $image;
    private $usage = [self::DEFAULT];
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
     * @param PageImage[] $pageImages
     * @return array
     */
    public static function toMetadataArray(array $pageImages): array
    {
        $pageImagesMeta = [];
        foreach ($pageImages as $pageImage) {
            $pageImagesMeta[] = [
                self::PATH_ATTRIBUTE => $pageImage->getImage()->getDokuPath(),
                self::USAGE_ATTRIBUTE => $pageImage->getUsages()
            ];
        };
        return array_values($pageImagesMeta);
    }

    /**
     * @param $rawValue
     * @param Page $page
     * @return PageImage[]
     */
    public static function toPageImageArray($rawValue, Page $page): array
    {

        if ($rawValue === null) {
            return [];
        }
        if (is_array($rawValue)) {
            $images = [];
            foreach ($rawValue as $key => $value) {
                $usage = PageImage::getDefaultUsage();
                if (is_numeric($key)) {
                    if (is_array($value)) {
                        $usage = $value[PageImage::USAGE_ATTRIBUTE];
                        $imagePath = $value[PageImage::PATH_ATTRIBUTE];
                    } else {
                        $imagePath = $value;
                    }
                } else {
                    $imagePath = $key;
                    if (is_array($value) && isset($value[PageImage::USAGE_ATTRIBUTE])) {
                        $usage = $value[PageImage::USAGE_ATTRIBUTE];
                        if (!is_array($usage)) {
                            $usage = [$usage];
                        }
                    }
                }
                DokuPath::addRootSeparatorIfNotPresent($imagePath);
                $images[$imagePath] = PageImage::create($imagePath, $page)
                    ->setUsage($usage);
            }
            return $images;
        } else {
            /**
             * A single path image
             */
            DokuPath::addRootSeparatorIfNotPresent($rawValue);
            return [$rawValue => PageImage::create($rawValue, $page)];
        }

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

    public function getUsages(): array
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
