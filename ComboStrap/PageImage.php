<?php


namespace ComboStrap;


class PageImage
{

    const PAGE_IMAGE = "page-image";

    // next release ?

    /**
     * @var FetcherImage
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
    public function __construct(FetcherImage $image, Page $page)
    {
        $this->image = $image;
        $this->page = $page;
    }

    /**
     * @param FetcherImage|string $image
     * @param Page $page
     * @return PageImage
     * @throws ExceptionCompile
     */
    public static function create($image, ResourceCombo $page): PageImage
    {
        if (!($image instanceof FetcherImage)) {
            $dokuPath = DokuPath::createMediaPathFromId($image);
            $image = FetcherImage::createImageFetchFromPath($dokuPath);
        }
        return new PageImage($image, $page);
    }

    /**
     * @param array $usages
     * @return $this
     * @throws ExceptionCompile
     */
    public function setUsages(array $usages): PageImage
    {
        foreach ($usages as $usage) {
            $value = trim($usage);
            if ($value === "") {
                continue;
            }
            if (!in_array($value, PageImageUsage::getUsageValues())) {
                throw new ExceptionCompile("The page image usage value ($value) is not valid.");
            }
            $this->usages[$value] = $value;
        }
        return $this;
    }

    public function getImage(): FetcherImage
    {
        return $this->image;
    }

    public function getUsages(): array
    {
        if ($this->usages === null) {
            return $this->getDefaultUsage();
        }
        return array_values($this->usages);
    }

    public function getDefaultUsage(): array
    {
        return [PageImageUsage::DEFAULT];
    }

    /**
     * @return Page
     */
    public function getPage(): Page
    {
        return $this->page;
    }


}
