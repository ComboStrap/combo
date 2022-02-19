<?php


namespace ComboStrap;

/**
 * Class JavascriptLibrary
 * @package ComboStrap
 * A javascript library in the resource directory
 */
class JavascriptLibrary extends Media
{

    const EXTENSION = "js";


    /**
     * @param $dokuwikiId
     * @return JavascriptLibrary
     */
    public static function createJavascriptLibraryFromDokuwikiId($dokuwikiId): JavascriptLibrary
    {
        $resource = DokuPath::createComboResource($dokuwikiId);
        return new JavascriptLibrary($resource);
    }


    /**
     * @return string
     */
    public function getUrl(): string
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

        $path = $this->getPath();
        if (!($path instanceof DokuPath)) {
            LogUtility::msg("Only Javascript script from a wiki path can be served");
            return "";
        }
        if(!FileSystems::exists($path)){
            LogUtility::msg("The library path ($path) does not exist. No Url created.");
            return "";
        }
        /**
         * @var DokuPath $path
         */
        if ($path->getLibrary() !== DokuPath::COMBO_DRIVE) {
            LogUtility::msg("Only Javascript script in the resource directory can be served, blank url returned");
            return "";
        };
        $direct = true;
        $att = [];
        $this->addCacheBusterToQueryParameters($att);
        $att[DokuPath::DRIVE_ATTRIBUTE] = $path->getLibrary();
        return ml($path->getDokuwikiId(), $att, $direct, $ampersand, true);
    }


}
