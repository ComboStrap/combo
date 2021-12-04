<?php


namespace ComboStrap;


use Exception;


class PageImages extends Metadata
{

    const CANONICAL = "page:image";
    public const IMAGE_META_PROPERTY = 'image';
    public const CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE = "disableFirstImageAsPageImage";
    public const FIRST_IMAGE_META_RELATION = "firstimage";
    public const IMAGE_PATH = "image-path";
    public const IMAGE_USAGE = "image-usage";


    /**
     * @var PageImage[]
     */
    private $pageImages;
    /**
     * @var bool
     */
    private $wasBuild = false;

    public static function createForPageWithDefaultStore(Page $page): PageImages
    {
        return (new PageImages())
            ->setResource($page)
            ->useDefaultStore();

    }

    public static function create(): PageImages
    {
        return new PageImages();
    }

    /**
     * @param PageImage[] $pageImages
     * @return array
     */
    private function toMetadataArray(array $pageImages): array
    {
        $pageImagesMeta = [];
        foreach ($pageImages as $pageImage) {
            $absolutePath = $pageImage->getImage()->getPath()->getAbsolutePath();
            $pageImagesMeta[$absolutePath] = [
                PageImage::PATH_ATTRIBUTE => $absolutePath
            ];
            if ($pageImage->getUsages() !== null && $pageImage->getUsages() !== $pageImage->getDefaultUsage()) {
                $pageImagesMeta[$absolutePath][PageImage::USAGE_ATTRIBUTE] = implode(", ", $pageImage->getUsages());
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
         * @var Page $page;
         */
        $page = $this->getResource();

        if (is_array($persistentValue)) {
            $images = [];
            foreach ($persistentValue as $key => $value) {
                $usage = null;
                if (is_numeric($key)) {
                    if (is_array($value)) {
                        if (isset($value[PageImage::USAGE_ATTRIBUTE])) {
                            $usage = $value[PageImage::USAGE_ATTRIBUTE];
                            if (is_string($usage)) {
                                $usage = explode(",", $usage);
                            }
                        }
                        $imagePath = $value[PageImage::PATH_ATTRIBUTE];
                    } else {
                        $imagePath = $value;
                    }
                } else {
                    $imagePath = $key;
                    if (is_array($value) && isset($value[PageImage::USAGE_ATTRIBUTE])) {
                        $usage = $value[PageImage::USAGE_ATTRIBUTE];
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

    public function buildFromStore(): PageImages
    {

        return $this->buildFromPersistentFormat($this->getStoreValue());

    }


    /**
     * @throws ExceptionCombo
     */
    public function setFromPersistentFormat($value): PageImages
    {
        $this->pageImages = PageImages::toPageImageArray($value);
        $this->checkImageExistence();
        return $this;
    }


    public function getCanonical(): string
    {
        return self::CANONICAL;
    }

    public function getName(): string
    {
        return self::IMAGE_META_PROPERTY;
    }

    /**
     * @throws ExceptionCombo
     */
    public function toPersistentValue()
    {
        $this->buildCheck();
        $this->checkImageExistence();
        return $this->toMetadataArray($this->pageImages);
    }

    public function toPersistentDefaultValue()
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
    public function getAll(): array
    {
        $this->buildCheck();
        if ($this->pageImages === null) {
            return [];
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

    private function buildCheck()
    {
        if (!$this->wasBuild && $this->pageImages === null) {
            $this->wasBuild = true;
            $this->buildFromStore();
        }
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_IMAGE_VALUE;
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

    public function toFormField(): FormMetaField
    {
        $this->buildCheck();
        $pageImagePath = FormMetaField::create(self::IMAGE_PATH)
            ->setLabel("Path")
            ->setCanonical($this->getCanonical())
            ->setDescription("The path of the image")
            ->setWidth(8);
        $pageImageUsage = FormMetaField::create(self::IMAGE_USAGE)
            ->setLabel("Usages")
            ->setCanonical($this->getCanonical())
            ->setDomainValues(PageImage::getUsageValues())
            ->setWidth(4)
            ->setMultiple(true)
            ->setDescription("The possible usages of the image");


        /** @noinspection PhpIfWithCommonPartsInspection */
        if ($this->pageImages !== null) {
            foreach ($this->pageImages as $pageImage) {
                $pageImagePathValue = $pageImage->getImage()->getPath()->getAbsolutePath();
                $pageImagePathUsage = $pageImage->getUsages();
                $pageImagePath->addValue($pageImagePathValue);
                $pageImageUsage->addValue($pageImagePathUsage, PageImage::DEFAULT);
            }
            $pageImagePath->addValue(null);
            $pageImageUsage->addValue(null, PageImage::DEFAULT);
        } else {
            $pageImageDefault = $this->getResource()->getDefaultPageImageObject()->getImage()->getPath()->getAbsolutePath();
            $pageImagePath->addValue(null, $pageImageDefault);
            $pageImageUsage->addValue(null, PageImage::DEFAULT);
        }


        // Image
        $formMeta = parent::toFormField();
        return $formMeta
            ->addColumn($pageImagePath)
            ->addColumn($pageImageUsage);

    }


    /**
     * @throws ExceptionCombo
     */
    public function setFromFormData($formData)
    {
        $imagePaths = $formData[self::IMAGE_PATH];
        if ($imagePaths !== null && $imagePaths !== "") {
            $usages = $formData[self::IMAGE_USAGE];
            $this->pageImages = [];
            $counter = 0;
            foreach ($imagePaths as $imagePath) {
                $usage = $usages[$counter];
                $usages = explode(",", $usage);
                if ($imagePath !== null && $imagePath !== "") {
                    $this->pageImages[] = PageImage::create($imagePath, $this->getResource())
                        ->setUsages($usages);
                }
                $counter++;
            }
        }
        $this->checkImageExistence();
        $this->sendToStore();
        return $this;
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
        foreach ($this->pageImages as $pageImage) {
            if (!$pageImage->getImage()->exists()) {
                throw new ExceptionCombo("The image ({$pageImage->getImage()}) does not exist", $this->getCanonical());
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
        if(!isset($this->pageImages[$sourceImagePath])){
            return null;
        }
        $pageImage = $this->pageImages[$sourceImagePath];
        unset($this->pageImages[$sourceImagePath]);
        $this->sendToStore();
        return $pageImage;
    }
}
