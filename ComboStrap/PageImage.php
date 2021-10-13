<?php


namespace ComboStrap;


class PageImage
{
    const ILLUSTRATION = "illustration";
    const ICON = "icon";

    /**
     * @var Image
     */
    private $image;
    private $tag = self::ILLUSTRATION;

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
            $image = Image::createImageFromAbsolutePath($image);
        }
        return new PageImage($image);
    }

    public function setTag($tag): PageImage
    {
        $this->tag = $tag;
        return $this;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public static function getDefaultTag(): string
    {
        return self::ILLUSTRATION;
    }

    public static function getTagValues(): array
    {
        return [self::ILLUSTRATION, self::ICON];
    }


}
