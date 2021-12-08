<?php


namespace ComboStrap;


class Mime
{

    public const JSON = "application/json";
    public const HTML = "text/html";
    const PLAIN_TEXT = "text/plain";
    const HEADER_CONTENT_TYPE = "Content-Type";
    public const SVG = "image/svg+xml";
    public const JAVASCRIPT = "text/javascript";
    /**
     * @var array|null
     */
    private static $knownTypes;

    /**
     * @var string
     */
    private $mime;

    /**
     * Mime constructor.
     */
    public function __construct(string $mime)
    {
        $this->mime = $mime;
    }

    public static function create(string $mime): Mime
    {
        return new Mime($mime);
    }

    public function __toString()
    {
        return $this->mime ;
    }

    public function isKnown(): bool
    {

        if (self::$knownTypes === null) {
            self::$knownTypes = getMimeTypes();
        }
        return array_search($this->mime, self::$knownTypes) !== false;

    }

    public function isTextBased(): bool
    {
        if($this->getFirstPart()==="text"){
            return true;
        }
        if(in_array($this->mime,[self::SVG,self::JSON])){
            return true;
        }
        return false;
    }

    private function getFirstPart()
    {
        return explode("/",$this->mime)[0];
    }

    public function isImage(): bool
    {
        return substr($this->mime, 0, 5) === 'image';
    }

    public function toString(): string
    {
        return $this->__toString();
    }


}
