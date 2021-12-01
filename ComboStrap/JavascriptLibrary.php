<?php


namespace ComboStrap;

/**
 * Class JavascriptLibrary
 * @package ComboStrap
 * A javascript library in the resource directory
 */
class JavascriptLibrary extends Media
{

    /**
     * Dokuwiki know as file system starts at page and media
     * This parameters permits to add another one
     * that starts at the resource directory
     */
    const COMBO_MEDIA_FILE_SYSTEM = "combo-fs";
    const EXTENSION = "js";
    const MIME = "text/javascript";


    /**
     * @param $relativeDokuPath
     * @return JavascriptLibrary
     */
    public static function createJavascriptLibraryFromRelativeId($relativeDokuPath): JavascriptLibrary
    {
        $relativeFsPath = DokuPath::toFileSystemSeparator($relativeDokuPath);
        $absolutePath = Resources::getAbsoluteResourcesDirectory() . DIRECTORY_SEPARATOR . $relativeFsPath;
        return new JavascriptLibrary($absolutePath);
    }


    /**
     *
     * @param string $ampersand
     * @return string
     */
    public function getUrl($ampersand = DokuwikiUrl::AMPERSAND_CHARACTER): string
    {
        /**
         * The ampersand must not be send encoded
         *
         * The url properties when used in a header
         * are encoded via the {@link _tpl_metaheaders_action}
         * that uses the {@link buildAttributes()} function
         * that uses the function {@link htmlspecialchars} against the url
         */
        $ampersand = DokuwikiUrl::AMPERSAND_CHARACTER;

        if (!$this->isResourceScript()) {
            LogUtility::msg("Only Javascript script in the resource directory can be served, blank url returned");
            return "";
        };
        $relativePath = substr($this->getAbsoluteFileSystemPath(), strlen(Resources::getAbsoluteResourcesDirectory()));
        $relativeDokuPath = DokuPath::toDokuWikiSeparator($relativePath);
        $direct = true;
        $att = [];
        $this->addCacheBusterToQueryParameters($att);
        $att[self::COMBO_MEDIA_FILE_SYSTEM] = "resources";
        return ml($relativeDokuPath, $att, $direct, $ampersand, true);
    }

    private function isResourceScript(): bool
    {
        $resourceDirectory = Resources::getAbsoluteResourcesDirectory();
        if (!(strpos($this->getAbsoluteFileSystemPath(), $resourceDirectory) === 0)) {
            return false;
        }
        return true;
    }


}
