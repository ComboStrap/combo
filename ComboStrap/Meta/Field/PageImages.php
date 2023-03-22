<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\FirstRasterImage;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataTabular;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\MetaManagerForm;
use ComboStrap\PageImageUsage;
use ComboStrap\WikiPath;

/**
 * @deprecated
 */
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


    public static function createForPage(MarkupPath $page): PageImages
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
         * @var MarkupPath $page ;
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
                WikiPath::addRootSeparatorIfNotPresent($imagePath);
                $imagePathObject = WikiPath::createMediaPathFromPath($imagePath);
                $pageImage = PageImage::create($imagePathObject, $page);
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
            WikiPath::addRootSeparatorIfNotPresent($persistentValue);
            $imagePathObject = WikiPath::createMediaPathFromPath($persistentValue);
            $images = [$persistentValue => PageImage::create($imagePathObject, $page)];
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
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }


    /**
     * @return PageImage[]
     */
    public function getValueAsPageImages(): array
    {
        $this->buildCheck();

        try {
            $rows = parent::getValue();
        } catch (ExceptionNotFound $e) {
            return [];
        }
        $pageImages = [];
        foreach ($rows as $row) {
            /**
             * @var PageImagePath $pageImagePath
             */
            $pageImagePath = $row[PageImagePath::getPersistentName()];
            try {
                $pageImagePathValue = $pageImagePath->getValue();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The page path didn't have any values in the rows", self::CANONICAL);
                continue;
            }
            $pageImage = PageImage::create($pageImagePathValue, $this->getResource());

            /**
             * @var PageImageUsage $pageImageUsage
             */
            $pageImageUsage = $row[PageImageUsage::getPersistentName()];
            if ($pageImageUsage !== null) {
                try {
                    $usages = $pageImageUsage->getValue();
                    $pageImage->setUsages($usages);
                } catch (ExceptionNotFound $e) {
                    // ok, no images
                } catch (ExceptionCompile $e) {
                    LogUtility::internalError("Bad Usage value. Should not happen on get. Error: " . $e->getMessage(), self::CANONICAL, $e);
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

        $pageImagePath = PageImagePath::createFromParent($this)->setFromStoreValue($wikiImagePath);
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


    public function isMutable(): bool
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
            if (!FileSystems::exists($pageImage->getImagePath())) {
                throw new ExceptionCompile("The image ({$pageImage->getImagePath()}) does not exist", $this->getCanonical());
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
        try {
            $firstImage = FirstRasterImage::createForPage($this->getResource())->getValue();
            $pageImagePath = PageImagePath::createFromParent($this)->buildFromStoreValue($firstImage);
        } catch (ExceptionNotFound $e) {
            // no first image
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

    /**
     * @param array $data
     * @return void
     *
     * Trick to advertise the image
     * saved in {@link \ComboStrap\Meta\Api\Metadata}
     * if the frontmatter is not used
     *
     */
    public function modifyMetaDokuWikiArray(array &$data)
    {
        $pageImages = $this->getValueAsPageImages();
        foreach ($pageImages as $pageImage) {
            /**
             * {@link Doku_Renderer_metadata::_recordMediaUsage()}
             */
            $dokuPath = $pageImage->getImagePath();
            $data[MetadataDokuWikiStore::CURRENT_METADATA]['relation']['media'][$dokuPath->getWikiId()] = FileSystems::exists($dokuPath);
        }

    }


}
