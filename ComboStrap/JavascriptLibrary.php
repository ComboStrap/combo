<?php


namespace ComboStrap;

/**
 * Class JavascriptLibrary
 * @package ComboStrap
 * A javascript library in the resource directory
 */
class JavascriptLibrary extends Media
{
    const COMBO_MEDIA_TYPE = "combo-type";


    /**
     * @param $relativePath
     * @return JavascriptLibrary
     */
    public static function createJavascriptLibraryFromRelativeId($relativePath): JavascriptLibrary
    {
        $absolutePath = self::getHomeLibraryDirectory()."/$relativePath";
        return new JavascriptLibrary($absolutePath);
    }

    public static function getHomeLibraryDirectory(): string
    {
        return Resources::getAbsoluteResourcesDirectory() . "/library";
    }


    public function getUrl(): string
    {
        if(!$this->isResourceLibrary()){
            LogUtility::msg("Only Javascript Library in the resource directory can be served, blank url returned");
            return "";
        };
        $relativePath = substr($this->getAbsoluteFileSystemPath(), strlen(static::getHomeLibraryDirectory()));
        $relativeDokuPath = DokuPath::toDokuWikiSeparator($relativePath);
        $direct = true;
        $ampersand = DokuwikiUrl::URL_ENCODED_AND;
        $att = [];
        $this->addCacheBusterToQueryParameters($att);
        $att[self::COMBO_MEDIA_TYPE] = "library";
        return ml($relativeDokuPath, $att, $direct, $ampersand, true);
    }

    private function isResourceLibrary(): bool
    {
        $resourceDirectory = self::getHomeLibraryDirectory();
        if (!(strpos($this->getAbsoluteFileSystemPath(), $resourceDirectory) === 0)) {
            return false;
        }
        return true;
    }


}
