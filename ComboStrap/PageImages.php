<?php


namespace ComboStrap;



class PageImages extends MetadataTabular
{


    const CANONICAL = "page:image";
    public const PROPERTY_NAME = 'image';
    public const CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE = "disableFirstImageAsPageImage";
    public const FIRST_IMAGE_META_RELATION = "firstimage";


    /**
     * @var PageImage[] with path as key
     */
    private $pageImages;
    /**
     * @var PageImageUsage
     */
    private $pageImageUsages;
    /**
     * @var PageImagePath
     */
    private $pageImagePath;

    /**
     * PageImages constructor.
     */
    public function __construct()
    {
        $this->pageImagePath = PageImagePath::createFromParent($this);
        $this->pageImageUsages = PageImageUsage::createFromParent($this);;

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
    public function getPageImages(): ?array
    {
        $this->buildCheck();
        if ($this->pageImages === null) {
            return null;
        }
        return array_values($this->pageImages);
    }

    /**
     * @throws ExceptionCombo
     */
    public function addImage(string $wikiImagePath, $usages = null): PageImages
    {
        DokuPath::addRootSeparatorIfNotPresent($wikiImagePath);
        $pageImage = PageImage::create($wikiImagePath, $this->getResource());
        if (!$pageImage->getImage()->exists()) {
            throw new ExceptionCombo("The image ($wikiImagePath) does not exists", $this->getCanonical());
        }
        if ($usages !== null) {
            if (is_string($usages)) {
                $usages = explode(",", $usages);
            }
            $pageImage->setUsages($usages);
        }

        $this->pageImages[$wikiImagePath] = $pageImage;

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

    public function getDataType(): string
    {
        return DataType::TABULAR_TYPE_VALUE;
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
     * @param $sourceImagePath
     * @return PageImage|null - the removed page image or null
     */
    public function removeIfExists($sourceImagePath): ?PageImage
    {
        $this->buildCheck();
        DokuPath::addRootSeparatorIfNotPresent($sourceImagePath);
        if (!isset($this->pageImages[$sourceImagePath])) {
            return null;
        }
        $pageImage = $this->pageImages[$sourceImagePath];
        unset($this->pageImages[$sourceImagePath]);
        return $pageImage;
    }

    public function getFirstImage()
    {
        $store = $this->getStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return null;
        }
        $relation = $store->getCurrentFromName('relation');
        if (!isset($relation[PageImages::FIRST_IMAGE_META_RELATION])) {
            return null;
        }

        $firstImageId = $relation[PageImages::FIRST_IMAGE_META_RELATION];
        if (empty($firstImageId)) {
            return null;
        }
        if (media_isexternal($firstImageId)) {
            return null;
        }
        return Image::createImageFromId($firstImageId);


    }

    public function valueIsNotNull(): bool
    {
        return $this->pageImages !== null;
    }



    function getChildren(): array
    {
        return [$this->pageImagePath, $this->pageImageUsages];
    }

    public function getUid(): string
    {
        return PageImagePath::class;
    }


    public function getColumnValues(Metadata $childMetadata): array
    {
        $value = [];
        if ($this->pageImages !== null) {
            /**
             * TODO ? Use the Row data
             *  // This function is used and tailored only for the form data store actually
             *  // To make it generic, we could have also worked from the raw data
             *  // created a PageImageUsage / Path object and got the output
             */
            foreach ($this->pageImages as $pageImage) {
                switch ($childMetadata->getName()) {
                    case PageImagePath::PROPERTY_NAME:
                        $value[] = $pageImage->getImage()->getPath()->toString();
                        break;
                    case PageImageUsage::PERSISTENT_NAME:
                        $value[] = implode(",", $pageImage->getUsages());
                        break;
                    default:
                        LogUtility::msg("Child Metadata not known");
                }
            }
        }
        return $value;
    }

    public function getDefaultValueForColumn($childMetadata): array
    {
        // TODO: Implement getDefaultValueForColumn() method.
        return [];
    }
}
