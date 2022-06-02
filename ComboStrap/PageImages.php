<?php


namespace ComboStrap;


class PageImages extends MetadataTabular
{


    const CANONICAL = "page:image";
    public const PROPERTY_NAME = 'images';
    public const PERSISTENT_NAME = 'images';

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

    public static function create(): PageImages
    {
        return new PageImages();
    }


    /**
     * @param $persistentValue
     * @return PageImage[]
     * @throws ExceptionCompile
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
     * @throws ExceptionCompile
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
     * @throws ExceptionCompile
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
    public function getValueAsPageImages(): array
    {
        $this->buildCheck();

        $rows = parent::getValue();
        if ($rows === null) {
            return [];
        }
        $pageImages = [];
        foreach ($rows as $row) {
            /**
             * @var PageImagePath $pageImagePath
             */
            $pageImagePath = $row[PageImagePath::getPersistentName()];
            try {
                $pageImage = PageImage::create($pageImagePath->getValue(), $this->getResource());
            } catch (ExceptionCompile $e) {
                LogUtility::msg("Error while creating the page image ($pageImagePath) for the page {$this->getResource()}. The image was not used. Error: {$e->getMessage()}");
                continue;
            }
            /**
             * @var PageImageUsage $pageImageUsage
             */
            $pageImageUsage = $row[PageImageUsage::getPersistentName()];
            if ($pageImageUsage !== null) {
                try {
                    $usages = $pageImageUsage->getValue();
                    if ($usages !== null) {
                        $pageImage->setUsages($usages);
                    }
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("Bad Usage value. Should not happen on get");
                }
            }
            $pageImages[] = $pageImage;
        }
        return $pageImages;
    }

    /**
     * @throws ExceptionCompile
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
     * @throws ExceptionCompile
     */
    private function checkImageExistence()
    {
        foreach ($this->getValueAsPageImages() as $pageImage) {
            if (!$pageImage->getImage()->exists()) {
                throw new ExceptionCompile("The image ({$pageImage->getImage()}) does not exist", $this->getCanonical());
            }
        }
    }


    function getChildrenClass(): array
    {
        return [PageImagePath::class, PageImageUsage::class];
    }

    public function getUidClass(): string
    {
        return PageImagePath::class;
    }


    /**
     * @return array|array[] - the default row
     */
    public function getDefaultValue(): array
    {

        $pageImagePath = null;
        // Not really the default value but yeah
        $firstImage = FirstImage::createForPage($this->getResource())->getValue();
        if ($firstImage !== null) {
            $pageImagePath = PageImagePath::createFromParent($this)->buildFromStoreValue($firstImage);
        }
        $pageImageUsage = PageImageUsage::createFromParent($this)->buildFromStoreValue([PageImageUsage::DEFAULT]);
        return [
            [
                PageImagePath::getPersistentName() => $pageImagePath,
                PageImageUsage::getPersistentName() => $pageImageUsage
            ]
        ];

    }

    public static function getOldPersistentNames(): array
    {
        return [self::OLD_PROPERTY_NAME];
    }


}
