<?php


namespace ComboStrap;

use DOMAttr;
use DOMElement;

/**
 * Class ImageSvg
 * @package ComboStrap
 *
 * Svg image fetch processing that can output:
 *   * an URL for an HTTP request
 *   * an SvgFile for an HTTP response or any further processing
 *
 * The original svg can be set with:
 *   * the {@link FetcherSvg::setSourcePath() original path}
 *   * the {@link FetcherSvg::setRequestedName() name} if this is an {@link FetcherSvg::setRequestedType() icon type}, the original path is then determined on {@link FetcherSvg::getSourcePath() get}
 *   * or by {@link FetcherSvg::setMarkup() Svg Markup}
 *
 */
class FetcherSvg extends IFetcherLocalImage
{

    use FetcherTraitWikiPath {
        setSourcePath as protected setOriginalPathTraitAlias;
    }

    const EXTENSION = "svg";
    const CANONICAL = "svg";

    const REQUESTED_PRESERVE_ASPECT_RATIO_KEY = "preserveAspectRatio";
    public const CURRENT_COLOR = "currentColor";
    /**
     * Default SVG values
     * https://github.com/svg/svgo/blob/master/plugins/_collections.js#L1579
     * The key are exact (not lowercase) to be able to look them up
     * for optimization
     */
    public const SVG_DEFAULT_ATTRIBUTES_VALUE = array(
        "x" => '0',
        "y" => '0',
        "width" => '100%',
        "height" => '100%',
        "preserveAspectRatio" => 'xMidYMid meet',
        "zoomAndPan" => 'magnify',
        "version" => '1.1',
        "baseProfile" => 'none',
        "contentScriptType" => 'application/ecmascript',
        "contentStyleType" => 'text/css',
    );
    /**
     * The namespace of the editors
     * https://github.com/svg/svgo/blob/master/plugins/_collections.js#L1841
     */
    public const EDITOR_NAMESPACE = [
        'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
        'http://inkscape.sourceforge.net/DTD/sodipodi-0.dtd',
        'http://www.inkscape.org/namespaces/inkscape',
        'http://www.bohemiancoding.com/sketch/ns',
        'http://ns.adobe.com/AdobeIllustrator/10.0/',
        'http://ns.adobe.com/Graphs/1.0/',
        'http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/',
        'http://ns.adobe.com/Variables/1.0/',
        'http://ns.adobe.com/SaveForWeb/1.0/',
        'http://ns.adobe.com/Extensibility/1.0/',
        'http://ns.adobe.com/Flows/1.0/',
        'http://ns.adobe.com/ImageReplacement/1.0/',
        'http://ns.adobe.com/GenericCustomNamespace/1.0/',
        'http://ns.adobe.com/XPath/1.0/',
        'http://schemas.microsoft.com/visio/2003/SVGExtensions/',
        'http://taptrix.com/vectorillustrator/svg_extensions',
        'http://www.figma.com/figma/ns',
        'http://purl.org/dc/elements/1.1/',
        'http://creativecommons.org/ns#',
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'http://www.serif.com/',
        'http://www.vector.evaxdesign.sk',
    ];
    public const CONF_PRESERVE_ASPECT_RATIO_DEFAULT = "svgPreserveAspectRatioDefault";
    public const TILE_TYPE = "tile";
    public const CONF_OPTIMIZATION_ELEMENTS_TO_DELETE = "svgOptimizationElementsToDelete";
    public const VIEW_BOX = "viewBox";
    /**
     * Optimization Configuration
     */
    public const CONF_OPTIMIZATION_NAMESPACES_TO_KEEP = "svgOptimizationNamespacesToKeep";
    public const CONF_SVG_OPTIMIZATION_ENABLE = "svgOptimizationEnable";
    public const COLOR_TYPE_STROKE_OUTLINE = FetcherSvg::STROKE_ATTRIBUTE;
    public const CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE = "svgOptimizationAttributesToDelete";
    public const CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY = "svgOptimizationElementsToDeleteIfEmpty";
    public const SVG_NAMESPACE_URI = "http://www.w3.org/2000/svg";
    public const STROKE_ATTRIBUTE = "stroke";
    public const DEFAULT_ICON_WIDTH = "24";
    public const REQUESTED_NAME_ATTRIBUTE = "name";
    public const REQUESTED_PRESERVE_ATTRIBUTE = "preserve";
    public const ILLUSTRATION_TYPE = "illustration";
    /**
     * There is only two type of svg icon / tile
     *   * fill color is on the surface (known also as Solid)
     *   * stroke, the color is on the path (known as Outline
     */
    public const COLOR_TYPE_FILL_SOLID = "fill";
    /**
     * Type of svg
     *   * Icon and tile have the same characteristic (ie viewbox = 0 0 A A) and the color can be set)
     *   * An illustration does not have rectangle shape and the color is not set
     */
    public const ICON_TYPE = "icon";
    /**
     * Namespace (used to query with xpath only the svg node)
     */
    public const SVG_NAMESPACE_PREFIX = "svg";
    const TAG = "svg";
    public const NAME_ATTRIBUTE = "name";
    public const DATA_NAME_HTML_ATTRIBUTE = "data-name";


    private ?ColorRgb $color = null;
    private ?string $preserveAspectRatio = null;
    private ?bool $preserveStyle = null;
    private ?string $requestedType = null;
    private bool $processed = false;
    private ?float $zoomFactor = null;
    private ?string $requestedClass = null;
    private int $intrinsicHeight;
    private int $intrinsicWidth;


    private static function createSvgEmpty(): FetcherSvg
    {
        return new FetcherSvg();
    }

    /**
     */
    public static function createSvgFromPath(WikiPath $path): FetcherSvg
    {
        $fetcher = self::createSvgEmpty();
        $fetcher->setSourcePath($path);
        return $fetcher;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createSvgFromFetchUrl(Url $fetchUrl): FetcherSvg
    {
        $fetchSvg = self::createSvgEmpty();
        $fetchSvg->buildFromUrl($fetchUrl);
        return $fetchSvg;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public static function createSvgFromMarkup(string $markup): FetcherSvg
    {
        return self::createSvgEmpty()->setMarkup($markup);
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return FetcherSvg
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionCompile
     */
    public static function createFromAttributes(TagAttributes $tagAttributes): FetcherSvg
    {
        $fetcher = FetcherSvg::createSvgEmpty();
        $fetcher->buildFromTagAttributes($tagAttributes);
        return $fetcher;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedOptimization(): bool
    {

        if ($this->requestedOptimization === null) {
            throw new ExceptionNotFound("Optimization was not set");
        }
        return $this->requestedOptimization;

    }

    public function getRequestedOptimizeOrDefault(): bool
    {
        try {
            return $this->getRequestedOptimization();
        } catch (ExceptionNotFound $e) {
            return Site::getConfValue(FetcherSvg::CONF_SVG_OPTIMIZATION_ENABLE, 1);
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedPreserveStyle(): bool
    {

        if ($this->preserveStyle === null) {
            throw new ExceptionNotFound("No preserve style attribute was set");
        }
        return $this->preserveStyle;

    }


    /**
     * @param $boolean
     * @return FetcherSvg
     */
    public function setRequestedOptimization($boolean): FetcherSvg
    {
        $this->requestedOptimization = $boolean;
        return $this;
    }

    /**
     * Optimization
     * Based on https://jakearchibald.github.io/svgomg/
     * (gui of https://github.com/svg/svgo)
     *
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadState
     */
    public
    function optimize()
    {

        if ($this->getRequestedOptimizeOrDefault()) {

            /**
             * Delete Editor namespace
             * https://github.com/svg/svgo/blob/master/plugins/removeEditorsNSData.js
             */
            $confNamespaceToKeeps = Site::getConfValue(FetcherSvg::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP);
            $namespaceToKeep = StringUtility::explodeAndTrim($confNamespaceToKeeps, ",");
            foreach ($this->getXmlDocument()->getNamespaces() as $namespacePrefix => $namespaceUri) {
                if (
                    !empty($namespacePrefix)
                    && $namespacePrefix != "svg"
                    && !in_array($namespacePrefix, $namespaceToKeep)
                    && in_array($namespaceUri, FetcherSvg::EDITOR_NAMESPACE)
                ) {
                    $this->getXmlDocument()->removeNamespace($namespaceUri);
                }
            }

            /**
             * Delete empty namespace rules
             */
            $documentElement = $this->getXmlDocument()->getDomDocument()->documentElement;
            foreach ($this->getXmlDocument()->getNamespaces() as $namespacePrefix => $namespaceUri) {
                $nodes = $this->getXmlDocument()->xpath("//*[namespace-uri()='$namespaceUri']");
                $attributes = $this->getXmlDocument()->xpath("//@*[namespace-uri()='$namespaceUri']");
                if ($nodes->length == 0 && $attributes->length == 0) {
                    $result = $documentElement->removeAttributeNS($namespaceUri, $namespacePrefix);
                    if ($result === false) {
                        LogUtility::msg("Internal error: The deletion of the empty namespace ($namespacePrefix:$namespaceUri) didn't succeed", LogUtility::LVL_MSG_WARNING, "support");
                    }
                }
            }

            /**
             * Delete comments
             */
            $commentNodes = $this->getXmlDocument()->xpath("//comment()");
            foreach ($commentNodes as $commentNode) {
                $this->getXmlDocument()->removeNode($commentNode);
            }

            /**
             * Delete default value (version=1.1 for instance)
             */
            $defaultValues = FetcherSvg::SVG_DEFAULT_ATTRIBUTES_VALUE;
            foreach ($documentElement->attributes as $attribute) {
                /** @var DOMAttr $attribute */
                $name = $attribute->name;
                if (isset($defaultValues[$name])) {
                    if ($defaultValues[$name] == $attribute->value) {
                        $documentElement->removeAttributeNode($attribute);
                    }
                }
            }

            /**
             * Suppress the attributes (by default id, style and class, data-name)
             */
            $attributeConfToDelete = Site::getConfValue(FetcherSvg::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE, "id, style, class, data-name");
            $attributesNameToDelete = StringUtility::explodeAndTrim($attributeConfToDelete, ",");
            foreach ($attributesNameToDelete as $value) {

                if (in_array($value, ["style", "class", "id"]) && $this->getRequestedPreserveStyleOrDefault()) {
                    // we preserve the style, we preserve the class
                    continue;
                }

                $nodes = $this->getXmlDocument()->xpath("//@$value");
                foreach ($nodes as $node) {
                    /** @var DOMAttr $node */
                    /** @var DOMElement $DOMNode */
                    $DOMNode = $node->parentNode;
                    $DOMNode->removeAttributeNode($node);
                }
            }

            /**
             * Remove width/height that coincides with a viewBox attr
             * https://www.w3.org/TR/SVG11/coords.html#ViewBoxAttribute
             * Example:
             * <svg width="100" height="50" viewBox="0 0 100 50">
             * <svg viewBox="0 0 100 50">
             *
             */
            $widthAttributeValue = $documentElement->getAttribute("width");
            if (!empty($widthAttributeValue)) {
                $widthPixel = Unit::toPixel($widthAttributeValue);

                $heightAttributeValue = $documentElement->getAttribute("height");
                if (!empty($heightAttributeValue)) {
                    $heightPixel = Unit::toPixel($heightAttributeValue);

                    // ViewBox
                    $viewBoxAttribute = $documentElement->getAttribute(FetcherSvg::VIEW_BOX);
                    if (!empty($viewBoxAttribute)) {
                        $viewBoxAttributeAsArray = StringUtility::explodeAndTrim($viewBoxAttribute, " ");

                        if (sizeof($viewBoxAttributeAsArray) == 4) {
                            $minX = $viewBoxAttributeAsArray[0];
                            $minY = $viewBoxAttributeAsArray[1];
                            $widthViewPort = $viewBoxAttributeAsArray[2];
                            $heightViewPort = $viewBoxAttributeAsArray[3];
                            if (
                                $minX == 0 &
                                $minY == 0 &
                                $widthViewPort == $widthPixel &
                                $heightViewPort == $heightPixel
                            ) {
                                $documentElement->removeAttribute("width");
                                $documentElement->removeAttribute("height");
                            }

                        }
                    }
                }
            }


            /**
             * Suppress script and style
             *
             *
             * Delete of scripts https://developer.mozilla.org/en-US/docs/Web/SVG/Element/script
             *
             * And defs/style
             *
             * The style can leak in other icon/svg inlined in the document
             *
             * Technically on icon, there should be no `style`
             * on inline icon otherwise, the css style can leak
             *
             * Example with carbon that use cls-1 on all icons
             * https://github.com/carbon-design-system/carbon/issues/5568
             * The facebook icon has a class cls-1 with an opacity of 0
             * that leaks to the tumblr icon that has also a cls-1 class
             *
             * The illustration uses inline fill to color and styled
             * For instance, all un-draw: https://undraw.co/illustrations
             */
            $elementsToDeleteConf = Site::getConfValue(FetcherSvg::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE, "script, style, title, desc");
            $elementsToDelete = StringUtility::explodeAndTrim($elementsToDeleteConf, ",");
            foreach ($elementsToDelete as $elementToDelete) {
                if ($elementToDelete === "style" && $this->getRequestedPreserveStyleOrDefault()) {
                    continue;
                }
                XmlSystems::deleteAllElementsByName($elementToDelete, $this->getXmlDocument());
            }

            // Delete If Empty
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/defs
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/metadata
            $elementsToDeleteIfEmptyConf = Site::getConfValue(FetcherSvg::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY, "metadata, defs, g");
            $elementsToDeleteIfEmpty = StringUtility::explodeAndTrim($elementsToDeleteIfEmptyConf);
            foreach ($elementsToDeleteIfEmpty as $elementToDeleteIfEmpty) {
                $elementNodeList = $this->getXmlDocument()->xpath("//*[local-name()='$elementToDeleteIfEmpty']");
                foreach ($elementNodeList as $element) {
                    /** @var DOMElement $element */
                    if (!$element->hasChildNodes()) {
                        $element->parentNode->removeChild($element);
                    }
                }
            }

            /**
             * Delete the svg prefix namespace definition
             * At the end to be able to query with svg as prefix
             */
            if (!in_array("svg", $namespaceToKeep)) {
                $documentElement->removeAttributeNS(FetcherSvg::SVG_NAMESPACE_URI, FetcherSvg::SVG_NAMESPACE_PREFIX);
            }

        }
    }


    /**
     *
     * @return int
     */
    public function getIntrinsicHeight(): int
    {

        return $this->intrinsicHeight;

    }

    /**
     * @return int
     */
    public
    function getIntrinsicWidth(): int
    {
        return $this->intrinsicWidth;

    }

    /**
     * @return string
     * @throws ExceptionBadArgument
     * @throws ExceptionBadState
     * @throws ExceptionBadSyntax
     * @throws ExceptionCompile
     * @throws ExceptionNotFound
     */
    public function processAndGetMarkup(): string
    {

        return $this->process()->getMarkup();


    }


    /**
     * @throws ExceptionBadState - if no svg was set to be processed
     */
    public function getMarkup(): string
    {
        return $this->getXmlDocument()->toXml();
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public function setSourcePath(WikiPath $path): IFetcherLocalImage
    {

        try {
            $this->xmlDocument = XmlDocument::createXmlDocFromPath($path);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The svg file ($path) is not a valid svg. Error: {$e->getMessage()}");
        } catch (ExceptionNotFound $e) {
            // ok file not found
            throw new ExceptionNotFound("The svg file ($path) was not found", self::CANONICAL);
        }
        $this->setIntrinsicDimensions();
        $this->setOriginalPathTraitAlias($path);
        return $this;

    }


    /**
     *
     * @return Url - the fetch url
     *
     */
    public function getFetchUrl(Url $url = null): Url
    {

        $url = parent::getFetchUrl($url);

        /**
         * Trait
         */
        $this->addLocalPathParametersToFetchUrl($url, self::$MEDIA_QUERY_PARAMETER);

        /**
         * Specific properties
         */
        try {
            $url->addQueryParameter(ColorRgb::COLOR, $this->getRequestedColor()->toCssValue());
        } catch (ExceptionNotFound $e) {
            // no color ok
        }
        try {
            $url->addQueryParameter(self::REQUESTED_PRESERVE_ASPECT_RATIO_KEY, $this->getRequestedPreserveAspectRatio());
        } catch (ExceptionNotFound $e) {
            // no preserve ratio ok
        }
        try {
            $url->addQueryParameter(self::REQUESTED_NAME_ATTRIBUTE, $this->getRequestedName());
        } catch (ExceptionNotFound $e) {
            // no name
        }
        try {
            $url->addQueryParameter(Dimension::ZOOM_ATTRIBUTE, $this->getRequestedZoom());
        } catch (ExceptionNotFound $e) {
            // no name
        }
        try {
            $url->addQueryParameter(TagAttributes::CLASS_KEY, $this->getRequestedClass());
        } catch (ExceptionNotFound $e) {
            // no name
        }
        try {
            $url->addQueryParameter(TagAttributes::TYPE_KEY, $this->getRequestedType());
        } catch (ExceptionNotFound $e) {
            // no name
        }

        return $url;

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedPreserveAspectRatio(): string
    {
        if ($this->preserveAspectRatio === null) {
            throw new ExceptionNotFound("No preserve Aspect Ratio was requested");
        }
        return $this->preserveAspectRatio;
    }

    /**
     * Return the svg file transformed by the attributes
     * from cache if possible. Used when making a fetch with the URL
     * @return LocalPath
     * @throws ExceptionBadArgument
     * @throws ExceptionBadState
     * @throws ExceptionBadSyntax - the file is not a svg file
     * @throws ExceptionCompile
     * @throws ExceptionNotFound - the file was not found
     */
    public function getFetchPath(): LocalPath
    {

        /**
         * Generated svg file cache init
         */
        $fetchCache = FetcherCache::createFrom($this);
        $files[] = $this->getSourcePath();
        try {
            $files[] = ClassUtility::getClassPath(FetcherSvg::class);
        } catch (\ReflectionException $e) {
            LogUtility::internalError("Unable to add the FetchImageSvg class as dependency. Error: {$e->getMessage()}");
        }
        try {
            $files[] = ClassUtility::getClassPath(XmlDocument::class);
        } catch (\ReflectionException $e) {
            LogUtility::internalError("Unable to add the XmlDocument class as dependency. Error: {$e->getMessage()}");
        }
        $files = array_merge(Site::getConfigurationFiles(), $files); // svg generation depends on configuration
        foreach ($files as $file) {
            $fetchCache->addFileDependency($file);
        }

        global $ACT;
        if (PluginUtility::isDev() && $ACT === "preview") {
            // in dev mode, don't cache
            $isCacheUsable = false;
        } else {
            $isCacheUsable = $fetchCache->isCacheUsable();
        }
        if (!$isCacheUsable) {
            $content = self::processAndGetMarkup();
            $fetchCache->storeCache($content);
        }
        return $fetchCache->getFile();

    }

    /**
     * The buster is also based on the configuration file
     *
     * It the user changes the configuration, the svg file is generated
     * again and the browser cache should be deleted (ie the buster regenerated)
     *
     * {@link ResourceCombo::getBuster()}
     * @return string
     *
     * @throws ExceptionNotFound
     */
    public function getBuster(): string
    {
        $buster = FileSystems::getCacheBuster($this->getSourcePath());
        try {
            $configFile = FileSystems::getCacheBuster(DirectoryLayout::getConfLocalFilePath());
            $buster = "$buster-$configFile";
        } catch (ExceptionNotFound $e) {
            // no local conf file
            if (PluginUtility::isDevOrTest()) {
                LogUtility::internalError("A local configuration file should be present in dev");
            }
        }
        return $buster;

    }


    function acceptsFetchUrl(Url $url): bool
    {

        try {
            $dokuPath = FetcherRawLocalPath::createEmpty()->buildFromUrl($url)->processIfNeededAndGetFetchPath();
        } catch (ExceptionBadArgument $e) {
            return false;
        }
        try {
            $mime = FileSystems::getMime($dokuPath);
        } catch (ExceptionNotFound $e) {
            return false;
        }
        if ($mime->toString() === Mime::SVG) {
            return true;
        }
        return false;

    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::SVG);
    }


    public function setRequestedColor(ColorRgb $color): FetcherSvg
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedColor(): ColorRgb
    {
        if ($this->color === null) {
            throw new ExceptionNotFound("No requested color");
        }
        return $this->color;
    }

    /**
     * @param string $preserveAspectRatio - the aspect ratio of the svg
     * @return $this
     */
    public function setRequestedPreserveAspectRatio(string $preserveAspectRatio): FetcherSvg
    {
        $this->preserveAspectRatio = $preserveAspectRatio;
        return $this;
    }


    /**
     * @var string|null - a name identifier that is added in the SVG
     */
    private ?string $requestedName = null;

    /**
     * @var ?boolean do the svg should be optimized
     */
    private ?bool $requestedOptimization = null;

    /**
     * @var XmlDocument|null
     */
    private ?XmlDocument $xmlDocument = null;


    /**
     * The name:
     *  * if this is a icon, this is the icon name of the {@link IconDownloader}. It's used to download the icon if not present.
     *  * is used to add a data attribute in the svg to be able to select it for test purpose
     *
     * @param string $name
     * @return FetcherSvg
     */
    public
    function setRequestedName(string $name): FetcherSvg
    {
        $this->requestedName = $name;
        return $this;
    }


    public
    function __toString()
    {
        return $this->getSourcePath()->__toString();

    }


    /**
     * @param string $viewBox
     * @return string[]
     */
    private function getViewBoxAttributes(string $viewBox): array
    {
        $attributes = explode(" ", $viewBox);
        if (sizeof($attributes) === 1) {
            /**
             * We may find also comma. Example:
             * viewBox="0,0,433.62,289.08"
             */
            $attributes = explode(",", $viewBox);
        }
        return $attributes;
    }


    private function getXmlDocument(): XmlDocument
    {
        if ($this->xmlDocument === null) {
            throw new ExceptionRuntime("Internal error: The xml document was not set.");
        }
        return $this->xmlDocument;
    }

    /**
     * Utility function
     * @return \DOMDocument
     */
    public function getXmlDom(): \DOMDocument
    {
        return $this->getXmlDocument()->getDomDocument();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedName(): string
    {
        if ($this->requestedName === null) {
            throw new ExceptionNotFound("Name was not set");
        }
        return $this->requestedName;
    }

    public function setPreserveStyle(bool $bool): FetcherSvg
    {
        $this->preserveStyle = $bool;
        return $this;
    }

    public function getRequestedPreserveStyleOrDefault(): bool
    {
        try {
            return $this->getRequestedPreserveStyle();
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedType(): string
    {
        if ($this->requestedType === null) {
            throw new ExceptionNotFound("The requested type was not specified");
        }
        return $this->requestedType;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    private function setMarkup(string $markup): FetcherSvg
    {
        $this->xmlDocument = XmlDocument::createXmlDocFromMarkup($markup);
        $this->setIntrinsicDimensions();
        return $this;
    }


    public function setRequestedType(string $requestedType): FetcherSvg
    {
        $this->requestedType = $requestedType;
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionBadState
     * @throws ExceptionNotFound|ExceptionCompile
     */
    public function process()
    {

        if ($this->processed) {
            LogUtility::internalError("The svg was already processed");
            return $this;
        }

        $this->processed = true;

        // Handy variable
        $documentElement = $this->getXmlDocument()->getElement();

        /**
         * ViewBox should exist
         */
        $viewBox = $documentElement->getAttribute(FetcherSvg::VIEW_BOX);
        if ($viewBox === "") {
            $width = $this->getIntrinsicWidth();
            $targetHeight = $this->getIntrinsicHeight();
            $documentElement->setAttribute(FetcherSvg::VIEW_BOX, "0 0 $width $targetHeight");
        }

        if ($this->getRequestedOptimizeOrDefault()) {
            $this->optimize();
        }

        // Set the name (icon) attribute for test selection
        try {
            $name = $this->getRequestedNameOrDefault();
            $documentElement->setAttribute('data-name', $name);
        } catch (ExceptionNotFound $e) {
            // ok no name
        }


        // Width requested
        try {
            $requestedWidth = $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
            $requestedWidth = null;
        }


        try {
            $requestedType = $this->getRequestedType();
        } catch (ExceptionNotFound $e) {
            $requestedType = null;
        }

        /**
         * Svg Structure
         *
         * All attributes that are applied for all usage (output independent)
         * and that depends only on the structure of the icon
         *
         * Why ? Because {@link \syntax_plugin_combo_pageimage}
         * can be an icon or an illustrative image
         *
         */
        try {
            $mediaWidth = $this->getIntrinsicWidth();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("The media width of ($this) returns the following error ({$e->getMessage()}). The processing was stopped");
            return $this->getXmlDocument()->toXml();
        }
        try {
            $mediaHeight = $this->getIntrinsicHeight();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("The media height of ($this) returns the following error ({$e->getMessage()}). The processing was stopped");
            return $this->getXmlDocument()->toXml();
        }
        if (
            $mediaWidth == $mediaHeight
            && $mediaWidth < 400) // 356 for logos telegram are the size of the twitter emoji but tile may be bigger ?
        {
            $svgStructureType = FetcherSvg::ICON_TYPE;
        } else {
            $svgStructureType = FetcherSvg::ILLUSTRATION_TYPE;

            // some icon may be bigger
            // in size than 400. example 1024 for ant-design:table-outlined
            // https://github.com/ant-design/ant-design-icons/blob/master/packages/icons-svg/svg/outlined/table.svg
            // or not squared
            // if the usage is determined or the svg is in the icon directory, it just takes over.
            try {
                $isInIconDirectory = IconDownloader::isInIconDirectory($this->getSourcePath());
            } catch (ExceptionNotFound $e) {
                // not a svg from a path
                $isInIconDirectory = false;
            }
            if ($requestedType === FetcherSvg::ICON_TYPE || $isInIconDirectory) {
                $svgStructureType = FetcherSvg::ICON_TYPE;
            }

        }

        /**
         * Svg type
         * The svg type is the svg usage
         * How the svg should be shown (the usage)
         *
         * We need it to make the difference between an icon
         *   * in a paragraph (the width and height are the same)
         *   * as an illustration in a page image (the width and height may be not the same)
         */
        if ($requestedType === null) {
            switch ($svgStructureType) {
                case FetcherSvg::ICON_TYPE:
                    $requestedType = FetcherSvg::ICON_TYPE;
                    break;
                default:
                    $requestedType = FetcherSvg::ILLUSTRATION_TYPE;
                    break;
            }
        }

        /**
         * A tag attributes to manage the add of style properties
         * in the style attribute
         */
        $stylingAttributes = TagAttributes::createEmpty(self::TAG);

        /**
         * Dimension and other attributes by requested type
         */
        switch ($requestedType) {
            case FetcherSvg::ICON_TYPE:
            case FetcherSvg::TILE_TYPE:
                /**
                 * Dimension: An icon or a tile have the same height and width
                 *
                 * Using a icon in the navbrand component of bootstrap
                 * require the set of width and height otherwise
                 * the svg has a calculated width of null
                 * and the bar component are below the brand text
                 *
                 */
                if ($this->norWidthNorHeightWasRequested()) {
                    if ($requestedType == FetcherSvg::ICON_TYPE) {
                        $length = FetcherSvg::DEFAULT_ICON_WIDTH;
                    } else {
                        // tile
                        $length = "192";
                    }
                    $targetWidth = $length;
                    $targetHeight = $length;
                } else {
                    $targetWidth = $this->getTargetWidth();
                    $targetHeight = $this->getTargetHeight();
                }
                if ($targetWidth !== $targetHeight) {
                    LogUtility::info("An icon or tile is defined as having the same dimension but the width ($targetWidth) is different from the height ($targetHeight). The icon will be cropped.");
                }

                /**
                 * Dimension
                 * The default unit on attribute is pixel, no need to add it to the number as in CSS
                 */
                $documentElement->setAttribute("width", $targetWidth)
                    ->setAttribute("height", $targetHeight);
                break;
            default:
                /**
                 * Illustration / Image
                 */
                /**
                 * Responsive SVG
                 */
                try {
                    $aspectRatio = $this->getRequestedPreserveAspectRatio();
                } catch (ExceptionNotFound $e) {
                    /**
                     *
                     * Keep the same height
                     * Image in the Middle and border deleted when resizing
                     * https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/preserveAspectRatio
                     * Default is xMidYMid meet
                     */
                    $aspectRatio = Site::getConfValue(FetcherSvg::CONF_PRESERVE_ASPECT_RATIO_DEFAULT, "xMidYMid slice");
                }
                $documentElement->setAttribute("preserveAspectRatio", $aspectRatio);

                /**
                 * Note on dimension width and height
                 * Width and height element attribute are in reality css style properties.
                 *   ie the max-width style
                 * They are treated in {@link PluginUtility::processStyle()}
                 */

                /**
                 * Adapt to the container by default
                 * Height `auto` and not `100%` otherwise you get a layout shift
                 */
                $stylingAttributes->addStyleDeclarationIfNotSet("width", "100%");
                $stylingAttributes->addStyleDeclarationIfNotSet("height", "auto");


                if ($requestedWidth !== null) {

                    /**
                     * If a dimension was set, it's seen by default as a max-width
                     * If it should not such as in a card, this property is already set
                     * and is not overwritten
                     */
                    try {
                        $widthInPixel = ConditionalLength::createFromString($requestedWidth)->toPixelNumber();
                    } catch (ExceptionCompile $e) {
                        LogUtility::msg("The requested width $requestedWidth could not be converted to pixel. It returns the following error ({$e->getMessage()}). Processing was stopped");
                        return $this->getXmlDocument()->toXml();
                    }
                    $stylingAttributes->addStyleDeclarationIfNotSet("max-width", "{$widthInPixel}px");

                    /**
                     * To have an internal width
                     * and not shrink on the css property `width: auto !important;`
                     * of a table
                     */
                    $documentElement->setAttribute("width", $widthInPixel);

                }

                break;
        }


        switch ($svgStructureType) {
            case FetcherSvg::ICON_TYPE:
            case FetcherSvg::TILE_TYPE:
                /**
                 * Determine if this is a:
                 *   * fill one color
                 *   * fill two colors
                 *   * or stroke svg icon
                 *
                 * The color can be set:
                 *   * on fill (surface)
                 *   * on stroke (line)
                 *
                 * If the stroke attribute is not present this is a fill icon
                 */
                $svgColorType = FetcherSvg::COLOR_TYPE_FILL_SOLID;
                if ($documentElement->hasAttribute(FetcherSvg::STROKE_ATTRIBUTE)) {
                    $svgColorType = FetcherSvg::COLOR_TYPE_STROKE_OUTLINE;
                }
                /**
                 * Double color icon ?
                 */
                $isDoubleColor = false;
                if ($svgColorType === FetcherSvg::COLOR_TYPE_FILL_SOLID) {
                    $svgFillsElement = $this->getXmlDocument()->xpath("//*[@fill]");
                    $fillColors = [];
                    for ($i = 0; $i < $svgFillsElement->length; $i++) {
                        /**
                         * @var DOMElement $nodeElement
                         */
                        $nodeElement = $svgFillsElement[$i];
                        $value = $nodeElement->getAttribute("fill");
                        if ($value !== "none") {
                            /**
                             * Icon may have none alongside colors
                             * Example:
                             */
                            $fillColors[$value] = $value;
                        }
                    }
                    if (sizeof($fillColors) > 1) {
                        $isDoubleColor = true;
                    }
                }

                /**
                 * CurrentColor
                 *
                 * By default, the icon should have this property when downloaded
                 * but if this not the case (such as for Material design), we set them
                 *
                 * Feather set it on the stroke
                 * Example: view-source:https://raw.githubusercontent.com/feathericons/feather/master/icons/airplay.svg
                 * <svg
                 *  fill="none"
                 *  stroke="currentColor">
                 */
                if (!$isDoubleColor && !$documentElement->hasAttribute("fill")) {

                    /**
                     * Note: if fill was not set, the default color would be black
                     */
                    $documentElement->setAttribute("fill", FetcherSvg::CURRENT_COLOR);

                }


                /**
                 * Eva/Carbon Source Icon are not optimized at the source
                 * Example:
                 *   * eva:facebook-fill
                 *   * carbon:logo-tumblr (https://github.com/carbon-design-system/carbon/issues/5568)
                 *
                 * We delete the rectangle
                 * Style should have already been deleted by the optimization
                 *
                 * This optimization should happen if the color is set
                 * or not because we set the color value to `currentColor`
                 *
                 * If the rectangle stay, we just see a black rectangle
                 */
                try {
                    $path = $this->getSourcePath();
                    $pathString = $path->toAbsolutePath()->toQualifiedId();
                    if (
                        preg_match("/carbon|eva/i", $pathString) === 1
                    ) {
                        XmlSystems::deleteAllElementsByName("rect", $this->getXmlDocument());
                    }
                } catch (ExceptionNotFound $e) {
                    // ok
                }


                $color = null;
                try {
                    $color = $this->getRequestedColor();
                } catch (ExceptionNotFound $e) {
                    if ($requestedType === FetcherSvg::ILLUSTRATION_TYPE) {
                        $primaryColor = Site::getPrimaryColorValue();
                        if ($primaryColor !== null) {
                            $color = $primaryColor;
                        }
                    }
                }


                /**
                 * Color
                 * Color applies only if this is an icon.
                 *
                 */
                if ($color !== null) {
                    /**
                     *
                     * We say that this is used only for an icon (<72 px)
                     *
                     * Not that an icon svg file can also be used as {@link \syntax_plugin_combo_pageimage}
                     *
                     * We don't set it as a styling attribute
                     * because it's not taken into account if the
                     * svg is used as a background image
                     * fill or stroke should have at minimum "currentColor"
                     */
                    $colorValue = ColorRgb::createFromString($color)->toCssValue();


                    switch ($svgColorType) {
                        case FetcherSvg::COLOR_TYPE_FILL_SOLID:

                            if (!$isDoubleColor) {

                                $documentElement->setAttribute("fill", $colorValue);

                                if ($colorValue !== FetcherSvg::CURRENT_COLOR) {
                                    /**
                                     * Update the fill property on sub-path
                                     * If the fill is set on sub-path, it will not work
                                     *
                                     * fill may be set on group or whatever
                                     */
                                    $svgPaths = $this->getXmlDocument()->xpath("//*[local-name()='path' or local-name()='g']");
                                    for ($i = 0; $i < $svgPaths->length; $i++) {
                                        /**
                                         * @var DOMElement $nodeElement
                                         */
                                        $nodeElement = $svgPaths[$i];
                                        $value = $nodeElement->getAttribute("fill");
                                        if ($value !== "none") {
                                            if ($nodeElement->parentNode->tagName !== "svg") {
                                                $nodeElement->setAttribute("fill", FetcherSvg::CURRENT_COLOR);
                                            } else {
                                                $this->getXmlDocument()->removeAttributeValue("fill", $nodeElement);
                                            }
                                        }
                                    }

                                }
                            } else {
                                // double color
                                $firsFillElement = $this->getXmlDocument()->xpath("//*[@fill][1]")->item(0);
                                if ($firsFillElement instanceof DOMElement) {
                                    $firsFillElement->setAttribute("fill", $colorValue);
                                }
                            }
                            break;

                        case FetcherSvg::COLOR_TYPE_STROKE_OUTLINE:
                            $documentElement->setAttribute("fill", "none");
                            $documentElement->setAttribute(FetcherSvg::STROKE_ATTRIBUTE, $colorValue);

                            if ($colorValue !== FetcherSvg::CURRENT_COLOR) {
                                /**
                                 * Delete the stroke property on sub-path
                                 */
                                // if the fill is set on sub-path, it will not work
                                $svgPaths = $this->getXmlDocument()->xpath("//*[local-name()='path']");
                                for ($i = 0; $i < $svgPaths->length; $i++) {
                                    /**
                                     * @var DOMElement $nodeElement
                                     */
                                    $nodeElement = $svgPaths[$i];
                                    $value = $nodeElement->getAttribute(FetcherSvg::STROKE_ATTRIBUTE);
                                    if ($value !== "none") {
                                        $this->getXmlDocument()->removeAttributeValue(FetcherSvg::STROKE_ATTRIBUTE, $nodeElement);
                                    } else {
                                        $this->getXmlDocument()->removeNode($nodeElement);
                                    }
                                }

                            }
                            break;
                    }

                }
                break;

        }

        /**
         * Ratio / Width / Height Cropping
         *
         * Width and height used to set the viewBox of a svg
         * to crop it (In a raster image, there is not this distinction)
         *
         * With an icon, the viewBox can be small but it can be zoomed out
         * via the {@link Dimension::WIDTH_KEY}
         *
         * We get a crop, it means that we need to change the viewBox
         *
         */
        $ratio = $this->getTargetAspectRatio();
        [$processedWidth, $processedHeight] = $this->getCroppingDimensionsWithRatio($ratio);
        $documentElement->setAttribute(FetcherSvg::VIEW_BOX, "0 0 $processedWidth $processedHeight");


        /**
         * Zoom occurs after the crop if any
         */
        try {
            $zoomFactor = $this->getRequestedZoom();
        } catch (ExceptionNotFound $e) {
            if ($svgStructureType === FetcherSvg::ICON_TYPE && $requestedType === FetcherSvg::ILLUSTRATION_TYPE) {
                $zoomFactor = -4;
            } else {
                $zoomFactor = null; // 0r 1 :)
            }
        }

        if ($zoomFactor !== null) {
            // icon case, we zoom out otherwise, this is ugly, the icon takes the whole place
            if ($zoomFactor < 0) {
                $processedWidth = -$zoomFactor * $processedWidth;
                $processedHeight = -$zoomFactor * $processedHeight;
            } else {
                $processedWidth = $processedWidth / $zoomFactor;
                $processedHeight = $processedHeight / $zoomFactor;
            }
            // center
            $actualWidth = $mediaWidth;
            $actualHeight = $mediaHeight;
            $x = -($processedWidth - $actualWidth) / 2;
            $y = -($processedHeight - $actualHeight) / 2;
            $documentElement->setAttribute(FetcherSvg::VIEW_BOX, "$x $y $processedWidth $processedHeight");
        }


        /**
         * Set the attributes to the root element
         * Svg attribute are case sensitive
         * Styling
         */
        $stylingAttributeAsArray = $stylingAttributes->toHtmlArray();
        foreach ($stylingAttributeAsArray as $name => $value) {
            $documentElement->setAttribute($name, $value);
        }

        /**
         * Class
         */
        try {
            $class = $this->getRequestedClass();
            $documentElement->addClass($class);
        } catch (ExceptionNotFound $e) {
            // no class
        }
        // add class with svg type
        $documentElement
            ->addClass(StyleUtility::addComboStrapSuffix(self::TAG))
            ->addClass(StyleUtility::addComboStrapSuffix(self::TAG . "-" . $requestedType));
        // Add a class on each path for easy styling
        try {
            $name = $this->getRequestedNameOrDefault();
            $svgPaths = $documentElement->querySelectorAll('path');
            for ($i = 0;
                 $i < count($svgPaths);
                 $i++) {
                $element = $svgPaths[$i];
                $stylingClass = $name . "-" . $i;
                $element->addClass($stylingClass);
            }
        } catch (ExceptionNotFound $e) {
            // no name
        }

        return $this;

    }


    public function getFetcherName(): string
    {
        return self::CANONICAL;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionCompile
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherImage
    {

        foreach (array_keys($tagAttributes->getComponentAttributes()) as $svgAttribute) {
            $svgAttribute = strtolower($svgAttribute);
            switch ($svgAttribute) {
                case Dimension::WIDTH_KEY:
                case Dimension::HEIGHT_KEY:
                    /**
                     * Length may be defined with CSS unit
                     * https://www.w3.org/TR/SVG2/coords.html#Units
                     */
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    try {
                        $lengthInt = ConditionalLength::createFromString($value)->toPixelNumber();
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The $svgAttribute value ($value) of the svg ($this) is not an integer", self::CANONICAL);
                        continue 2;
                    }
                    if ($svgAttribute === Dimension::WIDTH_KEY) {
                        $this->setRequestedWidth($lengthInt);
                    } else {
                        $this->setRequestedHeight($lengthInt);
                    }
                    continue 2;
                case Dimension::ZOOM_ATTRIBUTE;
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    try {
                        $lengthFloat = DataType::toFloat($value);
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The $svgAttribute value ($value) of the svg ($this) is not a float", self::CANONICAL);
                        continue 2;
                    }
                    $this->setRequestedZoom($lengthFloat);
                    continue 2;
                case ColorRgb::COLOR:
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    try {
                        $color = ColorRgb::createFromString($value);
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The $svgAttribute value ($value) of the svg ($this) is not an valid color", self::CANONICAL);
                        continue 2;
                    }
                    $this->setRequestedColor($color);
                    continue 2;
                case TagAttributes::TYPE_KEY:
                    $value = $tagAttributes->getValue($svgAttribute);
                    $this->setRequestedType($value);
                    continue 2;
                case self::REQUESTED_PRESERVE_ATTRIBUTE:
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    if ($value === "style") {
                        $preserve = true;
                    } else {
                        $preserve = false;
                    }
                    $this->setPreserveStyle($preserve);
                    continue 2;
                case self::NAME_ATTRIBUTE:
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    $this->setRequestedName($value);
                    continue 2;
                case TagAttributes::CLASS_KEY:
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    $this->setRequestedClass($value);
                    continue 2;
                case strtolower(self::REQUESTED_PRESERVE_ASPECT_RATIO_KEY):
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    $this->setRequestedPreserveAspectRatio($value);
                    continue 2;
            }

        }

        /**
         * Icon case
         */
        try {
            $iconDownload =
                !$tagAttributes->hasAttribute(FetcherTraitWikiPath::$MEDIA_QUERY_PARAMETER) &&
                $this->getRequestedType() === self::ICON_TYPE
                && $this->getRequestedName() !== null;
            if ($iconDownload) {
                try {
                    $dokuPath = $this->downloadAndGetIconPath();
                } catch (ExceptionCompile $e) {
                    throw new ExceptionBadArgument("We can't get the icon path. Error: {$e->getMessage()}. (ie media or icon name attribute is mandatory).", self::CANONICAL, 1, $e);
                }
                $this->setSourcePath($dokuPath);

            }
        } catch (ExceptionNotFound $e) {
            // no requested type or name
        }

        /**
         * Raw Trait
         */
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        parent::buildFromTagAttributes($tagAttributes);
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionCompile
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    private function downloadAndGetIconPath(): WikiPath
    {
        /**
         * It may be a Svg icon that we needs to download
         */
        try {
            $requestedType = $this->getRequestedType();
            $requestedName = $this->getRequestedName();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("No path was defined and no icon name was defined");
        }
        if ($requestedType !== self::ICON_TYPE) {
            throw new ExceptionNotFound("No original path was set and no icon was defined");
        }

        try {
            $iconDownloader = IconDownloader::createFromName($requestedName);
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionNotFound("The name ($requestedName) is not a valid icon name. Error: ({$e->getMessage()}.", self::CANONICAL, 1, $e);
        }
        $originalPath = $iconDownloader->getPath();
        if (FileSystems::exists($originalPath)) {
            return $originalPath;
        }
        try {
            $iconDownloader->download();
        } catch (ExceptionCompile $e) {
            throw new ExceptionCompile("The icon ($requestedName) could not be downloaded. Error: ({$e->getMessage()}.", self::CANONICAL);
        }
        $this->setSourcePath($originalPath);
        return $originalPath;
    }

    /**
     * This is used to add a name and class to the svg to make selection more easy
     * @throws ExceptionBadState
     * @throws ExceptionNotFound
     */
    private function getRequestedNameOrDefault(): string
    {
        try {
            return $this->getRequestedName();
        } catch (ExceptionNotFound $e) {
            return $this->getSourcePath()->getLastNameWithoutExtension();
        }
    }

    /**
     * @return bool - true if no width or height was requested
     */
    private function norWidthNorHeightWasRequested(): bool
    {
        try {
            $this->getRequestedWidth();
            return false;
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $this->getRequestedHeight();
            return false;
        } catch (ExceptionNotFound $e) {
            return true;
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedZoom(): float
    {
        $zoom = $this->zoomFactor;
        if ($zoom === null) {
            throw new ExceptionNotFound("No zoom requested");
        }
        return $zoom;
    }

    public function setRequestedZoom(float $zoomFactor): FetcherSvg
    {
        $this->zoomFactor = $zoomFactor;
        return $this;
    }

    public function setRequestedClass(string $value): FetcherSvg
    {
        $this->requestedClass = $value;
        return $this;

    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedClass(): string
    {
        if ($this->requestedClass === null) {
            throw new ExceptionNotFound("No class was set");
        }
        return $this->requestedClass;
    }

    /**
     * Analyse and set the mandatory intrinsic dimensions
     * @throws ExceptionBadSyntax
     */
    private function setIntrinsicDimensions()
    {
        $this->setIntrinsicHeight()
            ->setIntrinsicWidth();
    }

    /**
     * @throws ExceptionBadSyntax
     */
    private function setIntrinsicHeight(): FetcherSvg
    {
        $viewBox = $this->getXmlDocument()->getDomDocument()->documentElement->getAttribute(FetcherSvg::VIEW_BOX);
        if ($viewBox !== "") {
            $attributes = $this->getViewBoxAttributes($viewBox);
            $viewBoxHeight = $attributes[3];
            try {
                $this->intrinsicHeight = DataType::toInteger($viewBoxHeight);
                return $this;
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadSyntax("The media height ($viewBoxHeight) of the svg image ($this) is not a valid integer value");
            }
        }
        /**
         * Case with some icon such as
         * https://raw.githubusercontent.com/fefanto/fontaudio/master/svgs/fad-random-1dice.svg
         */
        $height = $this->getXmlDocument()->getDomDocument()->documentElement->getAttribute("height");
        if ($height === "") {
            throw new ExceptionBadSyntax("The svg ($this) does not have a viewBox or height attribute, the intrinsic height cannot be determined");
        }
        try {
            $this->intrinsicHeight = DataType::toInteger($height);
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionBadSyntax("The media width ($height) of the svg image ($this) is not a valid integer value");
        }
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    private function setIntrinsicWidth(): FetcherSvg
    {
        $viewBox = $this->getXmlDom()->documentElement->getAttribute(FetcherSvg::VIEW_BOX);
        if ($viewBox !== "") {
            $attributes = $this->getViewBoxAttributes($viewBox);
            $viewBoxWidth = $attributes[2];
            try {
                $this->intrinsicWidth = DataType::toInteger($viewBoxWidth);
                return $this;
            } catch (ExceptionCompile $e) {
                throw new ExceptionBadSyntax("The media with ($viewBoxWidth) of the svg image ($this) is not a valid integer value");
            }
        }

        /**
         * Case with some icon such as
         * https://raw.githubusercontent.com/fefanto/fontaudio/master/svgs/fad-random-1dice.svg
         */
        $width = $this->getXmlDom()->documentElement->getAttribute("width");
        if ($width === "") {
            throw new ExceptionBadSyntax("The svg ($this) does not have a viewBox or width attribute, the intrinsic width cannot be determined");
        }
        try {
            $this->intrinsicWidth = DataType::toInteger($width);
            return $this;
        } catch (ExceptionCompile $e) {
            throw new ExceptionBadSyntax("The media width ($width) of the svg image ($this) is not a valid integer value");
        }
    }


}
