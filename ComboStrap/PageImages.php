<?php


namespace ComboStrap;


use Exception;

class PageImages extends Metadata
{
    public const IMAGE_META_PROPERTY = 'image';
    public const CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE = "disableFirstImageAsPageImage";
    public const FIRST_IMAGE_META_RELATION = "firstimage";


    /**
     * @var PageImage[]
     */
    private $pageImages;
    /**
     * @var bool
     */
    private $wasBuild = false;

    public static function createFromPage(Page $page): PageImages
    {
        return new PageImages($page);
    }

    /**
     * @param PageImage[] $pageImages
     * @return array
     */
    private function toMetadataArray(array $pageImages): array
    {
        $pageImagesMeta = [];
        foreach ($pageImages as $pageImage) {
            $absolutePath = $pageImage->getImage()->getDokuPath()->getAbsolutePath();
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
                $pageImage = PageImage::create($imagePath, $this->getPage());
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
            $images = [$persistentValue => PageImage::create($persistentValue, $this->getPage())];
        }

        foreach ($images as $pageImage) {
            if (!$pageImage->getImage()->exists()) {
                throw new ExceptionCombo("The image ({$pageImage->getImage()}) does not exist", $this->getCanonical());
            }
        }

        return $images;

    }

    public function buildFromFileSystem()
    {
        try {
            $this->pageImages = $this->toPageImageArray($this->getFileSystemValue());
        } catch (Exception $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromPersistentFormat($value): PageImages
    {
        $this->pageImages = PageImages::toPageImageArray($value);
        $this->persistToFileSystem();
        return $this;
    }

    public function getCanonical(): string
    {
        return "page:image";
    }

    public function getName(): string
    {
        return self::IMAGE_META_PROPERTY;
    }

    public function toPersistentValue()
    {
        $this->buildCheck();
        return $this->toMetadataArray($this->pageImages);
    }

    public function toPersistentDefaultValue()
    {
        return null;
    }

    public function getPersistenceType()
    {
        return Metadata::PERSISTENT_METADATA;
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
    public function addImage(string $imagePath, $usages = []): PageImages
    {
        $pageImage = PageImage::create($imagePath, $this->getPage());
        if (!$pageImage->getImage()->exists()) {
            throw new ExceptionCombo("The image ($imagePath) does not exists", $this->getCanonical());
        }
        if (is_string($usages)) {
            $usages = explode(",", $usages);
        }
        $pageImage->setUsages($usages);


        $this->pageImages[$imagePath] = $pageImage;
        $this->persistToFileSystem();
        return $this;
    }

    private function buildCheck()
    {
        if (!$this->wasBuild && $this->pageImages === null) {
            $this->wasBuild = true;
            $this->buildFromFileSystem();
        }
    }
}
