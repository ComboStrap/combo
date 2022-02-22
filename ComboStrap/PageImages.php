<?php


namespace ComboStrap;


class PageImages extends MetadataTabular
{


    const CANONICAL = "page:image";
    public const PROPERTY_NAME = 'images';
    public const PERSISTENT_NAME = 'images';
    public const FIRST_IMAGE_META_RELATION = "firstimage";
    public const CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE = "disableFirstImageAsPageImage";

    /**
     * The name should be plural, this one was not
     */
    const OLD_PROPERTY_NAME = "image";


    /**
     * PageImages constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    public static function createForPage(Page $page): PageImages
    {
        return (new PageImages())
            ->setResource($page);

    }

    public static function create(): Metadata
    {
        return new PageImages();
    }

    /**
     * Google accepts several images dimension and ratios
     * for the same image
     * We may get an array then
     */
    public function getValueAsPageImagesOrDefault(): array
    {

        $pageImages = $this->getValueAsPageImages();
        if ($pageImages !== null) {
            return $pageImages;
        }
        try {
            $defaultPageImage = $this->getDefaultImage();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while getting the default page image for the page {$this->getResource()}. The image was not used. Error: {$e->getMessage()}");
            return [];
        }
        if ($defaultPageImage === null) {
            return [];
        }
        try {
            return [
                PageImage::create($defaultPageImage, $this->getResource())
            ];
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while creating the default page image ($defaultPageImage) for the page {$this->getResource()}. The image was not used. Error: {$e->getMessage()}");
            return [];
        }

    }

    /**
     * @return array
     */
    private function toMetadataArray(): ?array
    {
        if ($this->pageImages === null) {
            return null;
        }
        $pageImagesMeta = [];
        ksort($this->pageImages);
        foreach ($this->pageImages as $pageImage) {
            $absolutePath = $pageImage->getImage()->getPath()->toAbsolutePath()->toString();
            $pageImagesMeta[$absolutePath] = [
                PageImagePath::PERSISTENT_NAME => $absolutePath
            ];
            if ($pageImage->getUsages() !== null && $pageImage->getUsages() !== $pageImage->getDefaultUsage()) {
                $pageImagesMeta[$absolutePath][PageImageUsage::PERSISTENT_NAME] = implode(", ", $pageImage->getUsages());
            }
        };
        return array_values($pageImagesMeta);
    }

    /**
     * @param $persistentValue
     * @return PageImage[]
     * @throws ExceptionCombo
     */
    public function toPageImageArray($persistentValue): array
    {

        if ($persistentValue === null) {
            return [];
        }

        /**
         * @var Page $page ;
         */
        $page = $this->getResource();

        if (is_array($persistentValue)) {
            $images = [];
            foreach ($persistentValue as $key => $value) {
                $usage = null;
                if (is_numeric($key)) {
                    if (is_array($value)) {
                        if (isset($value[PageImageUsage::PERSISTENT_NAME])) {
                            $usage = $value[PageImageUsage::PERSISTENT_NAME];
                            if (is_string($usage)) {
                                $usage = explode(",", $usage);
                            }
                        }
                        $imagePath = $value[PageImagePath::PERSISTENT_NAME];
                    } else {
                        $imagePath = $value;
                    }
                } else {
                    $imagePath = $key;
                    if (is_array($value) && isset($value[PageImageUsage::PERSISTENT_NAME])) {
                        $usage = $value[PageImageUsage::PERSISTENT_NAME];
                        if (!is_array($usage)) {
                            $usage = explode(",", $usage);;
                        }
                    }
                }
                DokuPath::addRootSeparatorIfNotPresent($imagePath);
                $pageImage = PageImage::create($imagePath, $page);
                if ($usage !== null) {
                    $pageImage->setUsages($usage);
                }
                $images[$imagePath] = $pageImage;

            }
            return $images;
        } else {
            /**
             * A single path image
             */
            DokuPath::addRootSeparatorIfNotPresent($persistentValue);
            $images = [$persistentValue => PageImage::create($persistentValue, $page)];
        }

        return $images;

    }


    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): Metadata
    {
        $this->buildFromStoreValue($value);
        $this->checkImageExistence();
        return $this;
    }


    public function getCanonical(): string
    {
        return self::CANONICAL;
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistentName(): string
    {
        return self::PERSISTENT_NAME;
    }

    /**
     * @throws ExceptionCombo
     */
    public function toStoreValue(): ?array
    {
        $this->buildCheck();
        $this->checkImageExistence();
        return parent::toStoreValue();
    }

    public function toStoreDefaultValue()
    {
        return null;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }


    /**
     * @return PageImage[]
     */
    public function getValueAsPageImages(): ?array
    {
        $this->buildCheck();

        $rows = parent::getValue();
        if ($rows === null) {
            return null;
        }
        $pageImages = [];
        foreach ($rows as $row) {
            /**
             * @var PageImagePath $pageImagePath
             */
            $pageImagePath = $row[PageImagePath::getPersistentName()];
            try {
                $pageImage = PageImage::create($pageImagePath->getValue(), $this->getResource());
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error while creating the page image ($pageImagePath) for the page {$this->getResource()}. The image was not used. Error: {$e->getMessage()}");
                continue;
            }
            /**
             * @var PageImageUsage $pageImageUsage
             */
            $pageImageUsage = $row[PageImageUsage::getPersistentName()];
            if ($pageImageUsage !== null) {
                try {
                    $pageImage->setUsages($pageImageUsage->getValue());
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("Bad Usage value. Should not happen on get");
                }
            }
            $pageImages[] = $pageImage;
        }
        return $pageImages;
    }

    /**
     * @throws ExceptionCombo
     */
    public function addImage(string $wikiImagePath, $usages = null): PageImages
    {

        $pageImagePath = PageImagePath::createFromParent($this)
            ->setFromStoreValue($wikiImagePath);
        $row[PageImagePath::getPersistentName()] = $pageImagePath;
        if ($usages !== null) {
            $pageImageUsage = PageImageUsage::createFromParent($this)
                ->setFromStoreValue($usages);
            $row[PageImageUsage::getPersistentName()] = $pageImageUsage;
        }
        $this->rows[] = $row;

        /**
         * What fucked up is fucked up
         * The runtime {@link Doku_Renderer_metadata::_recordMediaUsage()}
         * ```
         * meta['relation']['media'][$src] = $exist
         * ```
         * is only set when parsing to add page to the index
         */
        return $this;
    }


    public function getTab(): string
    {
        return MetaManagerForm::TAB_IMAGE_VALUE;
    }


    public function getDescription(): string
    {
        return "The illustrative images of the page";
    }

    public function getLabel(): string
    {
        return "Page Images";
    }


    public function getMutable(): bool
    {
        return true;
    }

    /**
     *
     * We check the existence of the image also when persisting,
     * not when building
     * because when moving a media, the images does not exist any more
     *
     * We can then build the the pageimages with non-existing images
     * but we can't save
     *
     * @throws ExceptionCombo
     */
    private function checkImageExistence()
    {
        if ($this->pageImages !== null) {
            foreach ($this->pageImages as $pageImage) {
                if (!$pageImage->getImage()->exists()) {
                    throw new ExceptionCombo("The image ({$pageImage->getImage()}) does not exist", $this->getCanonical());
                }
            }
        }
    }


    /**
     * @throws ExceptionCombo
     */
    public
    function getDefaultImage(): ?Image
    {
        if (!PluginUtility::getConfValue(self::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE)) {
            return $this->getFirstImage();
        }
        return null;
    }

    /**
     * @return ImageRaster|ImageSvg|null - the first image of the page
     * @throws ExceptionCombo
     */
    public function getFirstImage()
    {

        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }
        /**
         * Our first image metadata
         * We can't overwrite the {@link \Doku_Renderer_metadata::$firstimage first image}
         * We put it then in directly under the root
         */
        $firstImageId = $store->getCurrentFromName(PageImages::FIRST_IMAGE_META_RELATION);

        /**
         * Dokuwiki first image metadata
         */
        if (empty($firstImageId)) {
            $relation = $store->getCurrentFromName('relation');
            if (!isset($relation[PageImages::FIRST_IMAGE_META_RELATION])) {
                return null;
            }

            $firstImageId = $relation[PageImages::FIRST_IMAGE_META_RELATION];
            if (empty($firstImageId)) {
                return null;
            }
        }

        /**
         * Image Id check
         */
        if (media_isexternal($firstImageId)) {
            return null;
        }
        return Image::createImageFromId($firstImageId);

    }


    function getChildrenClass(): array
    {
        return [PageImagePath::class, PageImageUsage::class];
    }

    public function getUidClass(): string
    {
        return PageImagePath::class;
    }


    public function getDefaultValue(): ?array
    {

        $defaultImage = $this->getDefaultImage();
        $pageImagePath = null;
        if ($defaultImage !== null) {
            $pageImagePath = PageImagePath::createFromParent($this)
                ->buildFromStoreValue($defaultImage->getPath()->toString());
        }
        return [
            [
                PageImagePath::getPersistentName() => $pageImagePath,
                PageImageUsage::getPersistentName() => PageImageUsage::createFromParent($this)->buildFromStoreValue([PageImageUsage::DEFAULT])
            ]
        ];

    }

    public static function getOldPersistentNames(): array
    {
        return [self::OLD_PROPERTY_NAME];
    }


}
