<?php


namespace ComboStrap;


class PageImage
{

    const PAGE_IMAGE = "page-image";


    /**
     * A path and not a {@link FetcherImage}
     * because:
     *   * it's basically a path (no processing information are needed)
     *   * it's easier to manipulate a path
     *   * in syntax component, we pass attribute to the fetcher that it should delete if used (Way to check the attribute usage)
     * @var DokuPath
     */
    private DokuPath $image;
    private $usages;
    /**
     * @var PageFragment
     */
    private $page;

    /**
     * PageImage constructor.
     */
    public function __construct(DokuPath $image, PageFragment $page)
    {
        $this->image = $image;
        $this->page = $page;
    }

    /**
     * @param DokuPath $image
     * @param PageFragment $page
     * @return PageImage
     */
    public static function create(DokuPath $image, ResourceCombo $page): PageImage
    {
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

    public function getImagePath(): DokuPath
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
     * @return PageFragment
     */
    public function getPage(): PageFragment
    {
        return $this->page;
    }


}
