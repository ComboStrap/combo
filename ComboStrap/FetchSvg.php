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
 *   * the {@link FetchSvg::setOriginalPath() original path}
 *   * the {@link FetchSvg::setRequestedName() name} if this is an {@link FetchSvg::setRequestedType() icon type}, the original path is then determined on {@link FetchSvg::getOriginalPath() get}
 *   * or by {@link FetchSvg::setMarkup() Svg Markup}
 *
 */
class FetchSvg extends FetchImage
{

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
    public const COLOR_TYPE_STROKE_OUTLINE = FetchSvg::STROKE_ATTRIBUTE;
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

    private ?DokuPath $originalPath = null;


    private ?ColorRgb $color = null;
    private string $buster;
    private ?string $preserveAspectRatio = null;
    private ?bool $preserveStyle = null;
    private ?string $requestedType = null;
    private bool $processed = false;


    public static function createSvgEmpty(): FetchSvg
    {
        return new FetchSvg();
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public static function createSvgFromPath(Path $path): FetchSvg
    {
        return self::createSvgEmpty()
            ->setOriginalPath($path);
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public static function createSvgFromFetchUrl(Url $fetchUrl): FetchSvg
    {
        return self::createSvgEmpty()->buildFromUrl($fetchUrl);
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
            return PluginUtility::getConfValue(FetchSvg::CONF_SVG_OPTIMIZATION_ENABLE, 1);
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
     * @return FetchSvg
     */
    public function setRequestedOptimization($boolean): FetchSvg
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
            $confNamespaceToKeeps = PluginUtility::getConfValue(FetchSvg::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP);
            $namespaceToKeep = StringUtility::explodeAndTrim($confNamespaceToKeeps, ",");
            foreach ($this->getXmlDocument()->getDocNamespaces() as $namespacePrefix => $namespaceUri) {
                if (
                    !empty($namespacePrefix)
                    && $namespacePrefix != "svg"
                    && !in_array($namespacePrefix, $namespaceToKeep)
                    && in_array($namespaceUri, FetchSvg::EDITOR_NAMESPACE)
                ) {
                    $this->getXmlDocument()->removeNamespace($namespaceUri);
                }
            }

            /**
             * Delete empty namespace rules
             */
            $documentElement = $this->getXmlDocument()->getXmlDom()->documentElement;
            foreach ($this->getXmlDocument()->getDocNamespaces() as $namespacePrefix => $namespaceUri) {
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
            $defaultValues = FetchSvg::SVG_DEFAULT_ATTRIBUTES_VALUE;
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
            $attributeConfToDelete = PluginUtility::getConfValue(FetchSvg::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE, "id, style, class, data-name");
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
                    $viewBoxAttribute = $documentElement->getAttribute(FetchSvg::VIEW_BOX);
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
            $elementsToDeleteConf = PluginUtility::getConfValue(FetchSvg::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE, "script, style, title, desc");
            $elementsToDelete = StringUtility::explodeAndTrim($elementsToDeleteConf, ",");
            foreach ($elementsToDelete as $elementToDelete) {
                if ($elementToDelete === "style" && $this->getRequestedPreserveStyleOrDefault()) {
                    continue;
                }
                XmlUtility::deleteAllElementsByName($elementToDelete, $this->getXmlDocument());
            }

            // Delete If Empty
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/defs
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/metadata
            $elementsToDeleteIfEmptyConf = PluginUtility::getConfValue(FetchSvg::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY, "metadata, defs, g");
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
                $documentElement->removeAttributeNS(FetchSvg::SVG_NAMESPACE_URI, FetchSvg::SVG_NAMESPACE_PREFIX);
            }

        }
    }


    /**
     *
     * @return int
     * @throws ExceptionCompile
     */
    public function getIntrinsicHeight(): int
    {
        $viewBox = $this->getXmlDocument()->getXmlDom()->documentElement->getAttribute(FetchSvg::VIEW_BOX);
        if ($viewBox !== "") {
            $attributes = $this->getViewBoxAttributes($viewBox);
            $viewBoxHeight = $attributes[3];
            try {
                return DataType::toInteger($viewBoxHeight);
            } catch (ExceptionCompile $e) {
                throw new ExceptionCompile("The media height ($viewBoxHeight) of the svg image ($this) is not a valid integer value");
            }
        }
        /**
         * Case with some icon such as
         * https://raw.githubusercontent.com/fefanto/fontaudio/master/svgs/fad-random-1dice.svg
         */
        $height = $this->getXmlDocument()->getXmlDom()->documentElement->getAttribute("height");
        if ($height === "") {
            throw new ExceptionCompile("The svg ($this) does not have a viewBox or height attribute, the intrinsic height cannot be determined");
        }
        try {
            return DataType::toInteger($height);
        } catch (ExceptionCompile $e) {
            throw new ExceptionCompile("The media width ($height) of the svg image ($this) is not a valid integer value");
        }

    }

    /**
     * @return int
     * @throws ExceptionBadSyntax
     */
    public
    function getIntrinsicWidth(): int
    {
        $viewBox = $this->getXmlDom()->documentElement->getAttribute(FetchSvg::VIEW_BOX);
        if ($viewBox !== "") {
            $attributes = $this->getViewBoxAttributes($viewBox);
            $viewBoxWidth = $attributes[2];
            try {
                return DataType::toInteger($viewBoxWidth);
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
            return DataType::toInteger($width);
        } catch (ExceptionCompile $e) {
            throw new ExceptionBadSyntax("The media width ($width) of the svg image ($this) is not a valid integer value");
        }

    }

    /**
     * @return string
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public function processAndGetMarkup(): string
    {


        return $this->process()
            ->getMarkup();


    }

    /**
     * @throws ExceptionBadState - if no svg was set to be processed
     */
    public function getMarkup(): string
    {
        return $this->getXmlDocument()->getXmlText();
    }


    /**
     *
     * @return Url - the fetch url
     *
     * @throws ExceptionBadState - if the svg could not be found
     */
    public function getFetchUrl(Url $url = null): Url
    {

        try {
            $dokuPath = $this->getOriginalPath();
        } catch (ExceptionCompile $e) {
            throw new ExceptionBadState("No original path could be determined. Error: {$e->getMessage()}");
        }
        $url = FetchRaw::createFromPath($dokuPath)->getFetchUrl($url);
        $url = parent::getFetchUrl($url);
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

        $this->addCommonImageQueryParameterToUrl($url);

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
     * @throws ExceptionBadSyntax - the file is not a svg file
     * @throws ExceptionNotFound - the file was not found
     */
    public function getFetchPath(): LocalPath
    {

        /**
         * Generated svg file cache init
         */
        $fetchCache = FetchCache::createFrom($this);
        $files[] = $this->originalPath;
        try {
            $files[] = ClassUtility::getClassPath(FetchSvg::class);
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
        $buster = FileSystems::getCacheBuster($this->getOriginalPath());
        try {
            $configFile = FileSystems::getCacheBuster(DirectoryLayout::getLocalConfPath());
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
            $dokuPath = FetchRaw::createEmpty()->buildFromUrl($url)->getFetchPath();
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

    /**
     * @return DokuPath - the path of the original svg if any
     * @throws ExceptionBadState - the original path was not set (Case of svg string) nor any icon
     */
    public function getOriginalPath(): DokuPath
    {

        if ($this->originalPath !== null) {
            return $this->originalPath;
        }

        try {
            return $this->getIconPath();
        } catch (ExceptionCompile $e) {
            throw new ExceptionBadState("No svg path was defined. The icon process returns the following error: {$e->getMessage()}");
        }


    }

    /**
     * @throws ExceptionBadArgument - for any bad argument
     * @throws ExceptionNotFound - if the svg file was not found
     * @throws ExceptionBadSyntax - if the svg is not valid
     */
    public function buildFromUrl(Url $url): FetchSvg
    {
        parent::buildFromUrl($url);
        $originalPath = FetchRaw::createEmpty()->buildFromUrl($url)->getFetchPath();
        $this->setOriginalPath($originalPath);
        $this->buildSharedImagePropertyFromFetchUrl($url);
        try {
            $color = $url->getQueryPropertyValue(ColorRgb::COLOR);
            // we can't have an hex in an url, we will see if this is encoded ;?
            $this->setRequestedColor(ColorRgb::createFromString($color));
        } catch (ExceptionNotFound $e) {
            // ok
        }

        try {
            $preserveAspectRatio = $url->getQueryPropertyValue(self::REQUESTED_PRESERVE_ASPECT_RATIO_KEY);
            $this->setRequestedPreserveAspectRatio($preserveAspectRatio);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        try {
            $name = $url->getQueryPropertyValue(FetchSvg::REQUESTED_NAME_ATTRIBUTE);
            $this->setRequestedName($name);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        try {
            $preserve = $this->getFetchUrl()->getQueryPropertyValue(self::REQUESTED_PRESERVE_ATTRIBUTE);
            if (strpos(strtolower($preserve), "style") !== false) {
                $this->setPreserveStyle(true);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }

        try {
            $this->requestedType = $this->getFetchUrl()->getQueryPropertyValue(TagAttributes::TYPE_KEY);
        } catch (ExceptionNotFound $e) {
            // ok
        }

        return $this;
    }

    public function setRequestedColor(ColorRgb $color): FetchSvg
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
    public function setRequestedPreserveAspectRatio(string $preserveAspectRatio): FetchSvg
    {
        $this->preserveAspectRatio = $preserveAspectRatio;
        return $this;
    }

    /**
     * @throws ExceptionBadArgument - if the path can not be converted to a doku path
     * @throws ExceptionBadSyntax - the content is not a valid svg
     * @throws ExceptionNotFound - the path was not found
     */
    public function setOriginalPath(Path $path): FetchSvg
    {
        $this->originalPath = DokuPath::createFromPath($path);
        $this->busterOriginalPath = FileSystems::getCacheBuster($path);

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
     * @return FetchSvg
     */
    public
    function setRequestedName(string $name): FetchSvg
    {
        $this->requestedName = $name;
        return $this;
    }


    public
    function __toString()
    {
        if ($this->originalPath !== null) {
            return $this->originalPath->__toString();
        }
        if ($this->requestedName !== null) {
            return $this->requestedName;
        }
        return "Anonymous Svg";
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

    /**
     *
     * @throws ExceptionBadState - if no xml document has been created
     * @throws ExceptionBadSyntax - bad svg syntax
     * @throws ExceptionNotFound - file not found
     */
    private function getXmlDocument(): XmlDocument
    {
        if ($this->xmlDocument === null) {
            $path = $this->getOriginalPath();
            try {
                $this->xmlDocument = XmlDocument::createXmlDocFromPath($path);
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadSyntax("The svg file ($path) is not a valid svg. Error: {$e->getMessage()}");
            } catch (ExceptionNotFound $e) {
                // ok file not found
                throw new ExceptionNotFound("The Svg file ($path) was not found", self::CANONICAL);
            }
        }
        return $this->xmlDocument;
    }

    /**
     * Utility function
     * @return \DOMDocument
     */
    public function getXmlDom(): \DOMDocument
    {
        return $this->getXmlDocument()->getXmlDom();
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

    public function setPreserveStyle(bool $bool): FetchSvg
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
    public function setMarkup(string $markup): FetchSvg
    {
        $this->xmlDocument = XmlDocument::createXmlDocFromMarkup($markup);
        return $this;
    }

    public function setRequestedType(string $requestedType): FetchSvg
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


        $localTagAttributes = TagAttributes::createEmpty(self::TAG);

        /**
         * ViewBox should exist
         */
        $viewBox = $this->getXmlDom()->documentElement->getAttribute(FetchSvg::VIEW_BOX);
        if ($viewBox === "") {
            try {
                $width = $this->getIntrinsicWidth();
            } catch (ExceptionCompile $e) {
                LogUtility::error("Svg processing stopped. Bad svg: We can't determine the width of the svg ($this) (The viewBox and the width does not exist) ", FetchSvg::CANONICAL);
                return $this->getXmlDocument()->getXmlText();
            }
            try {
                $height = $this->getIntrinsicHeight();
            } catch (ExceptionCompile $e) {
                LogUtility::error("Svg processing stopped. Bad svg: We can't determine the height of the svg ($this) (The viewBox and the height does not exist) ", FetchSvg::CANONICAL);
                return $this->getXmlDocument()->getXmlText();
            }
            $this->getXmlDom()->documentElement->setAttribute(FetchSvg::VIEW_BOX, "0 0 $width $height");
        }

        if ($this->getRequestedOptimizeOrDefault()) {
            $this->optimize();
        }

        // Set the name (icon) attribute for test selection
        try {
            $name = $this->getRequestedNameOrDefault();
            $this->getXmlDocument()->setRootAttribute('data-name', $name);
        } catch (ExceptionNotFound $e) {
            // ok no name
        }

        // Handy variable
        $documentElement = $this->getXmlDom()->documentElement;

        // Width requested
        try {
            $requestedWidth = $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
            $requestedWidth = null;
        }


        try {
            $svgUsageType = $this->getRequestedType();
        } catch (ExceptionNotFound $e) {
            $svgUsageType = null;
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
            return $this->getXmlDocument()->getXmlText();
        }
        try {
            $mediaHeight = $this->getIntrinsicHeight();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("The media height of ($this) returns the following error ({$e->getMessage()}). The processing was stopped");
            return $this->getXmlDocument()->getXmlText();
        }
        if (
            $mediaWidth == $mediaHeight
            && $mediaWidth < 400) // 356 for logos telegram are the size of the twitter emoji but tile may be bigger ?
        {
            $svgStructureType = FetchSvg::ICON_TYPE;
        } else {
            $svgStructureType = FetchSvg::ILLUSTRATION_TYPE;

            // some icon may be bigger
            // in size than 400. example 1024 for ant-design:table-outlined
            // https://github.com/ant-design/ant-design-icons/blob/master/packages/icons-svg/svg/outlined/table.svg
            // or not squared
            // if the usage is determined or the svg is in the icon directory, it just takes over.
            try {
                $isInIconDirectory = IconDownloader::isInIconDirectory($this->getOriginalPath());
            } catch (ExceptionNotFound $e) {
                // not a svg from a path
                $isInIconDirectory = false;
            }
            if ($svgUsageType === FetchSvg::ICON_TYPE || $isInIconDirectory) {
                $svgStructureType = FetchSvg::ICON_TYPE;
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
        if ($svgUsageType === null) {
            switch ($svgStructureType) {
                case FetchSvg::ICON_TYPE:
                    $svgUsageType = FetchSvg::ICON_TYPE;
                    break;
                default:
                    $svgUsageType = FetchSvg::ILLUSTRATION_TYPE;
                    break;
            }
        }
        $localTagAttributes->addClassName(StyleUtility::getStylingClassForTag(self::TAG . "-" . $svgUsageType));
        switch ($svgUsageType) {
            case FetchSvg::ICON_TYPE:
            case FetchSvg::TILE_TYPE:
                /**
                 * Dimension
                 *
                 * Using a icon in the navbrand component of bootstrap
                 * require the set of width and height otherwise
                 * the svg has a calculated width of null
                 * and the bar component are below the brand text
                 *
                 */
                $appliedWidth = $requestedWidth;
                if ($requestedWidth === null) {
                    if ($svgUsageType == FetchSvg::ICON_TYPE) {
                        $appliedWidth = FetchSvg::DEFAULT_ICON_WIDTH;
                    } else {
                        // tile
                        $appliedWidth = "192";
                    }
                }
                /**
                 * Dimension
                 * The default unit on attribute is pixel, no need to add it
                 * as in CSS
                 */
                $localTagAttributes->addOutputAttributeValue("width", $appliedWidth);
                $height = $localTagAttributes->getValueAndRemove(Dimension::HEIGHT_KEY, $appliedWidth);
                $localTagAttributes->addOutputAttributeValue("height", $height);
                break;
            default:
                /**
                 * Illustration / Image
                 */
                /**
                 * Responsive SVG
                 */
                try {
                    $this->getRequestedPreserveAspectRatio();
                } catch (ExceptionNotFound $e) {
                    /**
                     *
                     * Keep the same height
                     * Image in the Middle and border deleted when resizing
                     * https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/preserveAspectRatio
                     * Default is xMidYMid meet
                     */
                    $defaultAspectRatio = PluginUtility::getConfValue(FetchSvg::CONF_PRESERVE_ASPECT_RATIO_DEFAULT, "xMidYMid slice");
                    $localTagAttributes->addOutputAttributeValue("preserveAspectRatio", $defaultAspectRatio);
                }

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
                $localTagAttributes->addStyleDeclarationIfNotSet("width", "100%");
                $localTagAttributes->addStyleDeclarationIfNotSet("height", "auto");


                if ($requestedWidth !== null) {

                    /**
                     * If a dimension was set, it's seen by default as a max-width
                     * If it should not such as in a card, this property is already set
                     * and is not overwritten
                     */
                    try {
                        $widthInPixel = Dimension::toPixelValue($requestedWidth);
                    } catch (ExceptionCompile $e) {
                        LogUtility::msg("The requested width $requestedWidth could not be converted to pixel. It returns the following error ({$e->getMessage()}). Processing was stopped");
                        return $this->getXmlDocument()->getXmlText();
                    }
                    $localTagAttributes->addStyleDeclarationIfNotSet("max-width", "{$widthInPixel}px");

                    /**
                     * To have an internal width
                     * and not shrink on the css property `width: auto !important;`
                     * of a table
                     */
                    $this->getXmlDocument()->setRootAttribute("width", $widthInPixel);

                }

                break;
        }


        switch ($svgStructureType) {
            case FetchSvg::ICON_TYPE:
            case FetchSvg::TILE_TYPE:
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
                $svgColorType = FetchSvg::COLOR_TYPE_FILL_SOLID;
                if ($documentElement->hasAttribute(FetchSvg::STROKE_ATTRIBUTE)) {
                    $svgColorType = FetchSvg::COLOR_TYPE_STROKE_OUTLINE;
                }
                /**
                 * Double color icon ?
                 */
                $isDoubleColor = false;
                if ($svgColorType === FetchSvg::COLOR_TYPE_FILL_SOLID) {
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
                    $localTagAttributes->addOutputAttributeValue("fill", FetchSvg::CURRENT_COLOR);

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
                    $path = $this->getOriginalPath();
                    $pathString = $path->toAbsolutePath()->toPathString();
                    if (
                        preg_match("/carbon|eva/i", $pathString) === 1
                    ) {
                        XmlUtility::deleteAllElementsByName("rect", $this->getXmlDocument());
                    }
                } catch (ExceptionNotFound $e) {
                    // ok
                }


                $color = null;
                try {
                    $color = $this->getRequestedColor();
                } catch (ExceptionNotFound $e) {
                    if ($svgUsageType === FetchSvg::ILLUSTRATION_TYPE) {
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
                        case FetchSvg::COLOR_TYPE_FILL_SOLID:

                            if (!$isDoubleColor) {

                                $localTagAttributes->addOutputAttributeValue("fill", $colorValue);

                                if ($colorValue !== FetchSvg::CURRENT_COLOR) {
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
                                                $nodeElement->setAttribute("fill", FetchSvg::CURRENT_COLOR);
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
                        case FetchSvg::COLOR_TYPE_STROKE_OUTLINE:
                            $localTagAttributes->addOutputAttributeValue("fill", "none");
                            $localTagAttributes->addOutputAttributeValue(FetchSvg::STROKE_ATTRIBUTE, $colorValue);

                            if ($colorValue !== FetchSvg::CURRENT_COLOR) {
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
                                    $value = $nodeElement->getAttribute(FetchSvg::STROKE_ATTRIBUTE);
                                    if ($value !== "none") {
                                        $this->getXmlDocument()->removeAttributeValue(FetchSvg::STROKE_ATTRIBUTE, $nodeElement);
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
         * Ratio / Cropping (used for ratio cropping)
         * Width and height used to set the viewBox of a svg
         * to crop it
         * (In a raster image, there is not this distinction)
         *
         * With an icon, the viewBox can be small but it can be zoomed out
         * via the {@link Dimension::WIDTH_KEY}
         */
        $processedWidth = $mediaWidth;
        $processedHeight = $mediaHeight;
        if ($localTagAttributes->hasComponentAttribute(Dimension::RATIO_ATTRIBUTE)) {
            // We get a crop, it means that we need to change the viewBox
            $ratio = $localTagAttributes->getValueAndRemoveIfPresent(Dimension::RATIO_ATTRIBUTE);
            try {
                $targetRatio = Dimension::convertTextualRatioToNumber($ratio);
            } catch (ExceptionCompile $e) {
                LogUtility::msg("The target ratio attribute ($ratio) returns the following error ({$e->getMessage()}). The svg processing was stopped");
                return $this->getXmlDocument()->getXmlText();
            }
            [$processedWidth, $processedHeight] = $this->getCroppingDimensionsWithRatio($targetRatio, $mediaWidth, $mediaHeight);

            $this->getXmlDocument()->setRootAttribute(FetchSvg::VIEW_BOX, "0 0 $processedWidth $processedHeight");

        }

        /**
         * Zoom occurs after the crop if any
         */
        $zoomFactor = $localTagAttributes->getValueAsInteger(Dimension::ZOOM_ATTRIBUTE);
        if ($zoomFactor === null
            && $svgStructureType === FetchSvg::ICON_TYPE
            && $svgUsageType === FetchSvg::ILLUSTRATION_TYPE
        ) {
            $zoomFactor = -4;
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
            $this->getXmlDocument()->setRootAttribute(FetchSvg::VIEW_BOX, "$x $y $processedWidth $processedHeight");
        }


        // Add a class on each path for easy styling
        try {
            $name = $this->getRequestedNameOrDefault();
            $svgPaths = $this->getXmlDocument()->xpath("//*[local-name()='path']");
            for ($i = 0;
                 $i < $svgPaths->length;
                 $i++) {

                $stylingClass = $name . "-" . $i;
                $this->getXmlDocument()->addAttributeValue("class", $stylingClass, $svgPaths[$i]);
            }
        } catch (ExceptionNotFound $e) {
            // no name
        }


        /**
         * Svg attribute are case sensitive
         * but not the component attribute key
         * we get the value and set it then as HTML to have the good casing
         * on this attribute
         */
        $caseSensitives = ["preserveAspectRatio"];
        foreach ($caseSensitives as $caseSensitive) {
            if ($localTagAttributes->hasComponentAttribute($caseSensitive)) {
                $aspectRatio = $localTagAttributes->getValueAndRemove($caseSensitive);
                $localTagAttributes->addOutputAttributeValue($caseSensitive, $aspectRatio);
            }
        }

        /**
         * Set the attributes to the root
         */
        $toHtmlArray = $localTagAttributes->toHtmlArray();
        foreach ($toHtmlArray as $name => $value) {
            if (in_array($name, TagAttributes::MULTIPLE_VALUES_ATTRIBUTES)) {
                $actualValue = $this->getXmlDocument()->getRootAttributeValue($name);
                if ($actualValue !== null) {
                    $value = TagAttributes::mergeClassNames($value, $actualValue);
                }
            }
            $this->getXmlDocument()->setRootAttribute($name, $value);
        }
        return $this;
    }


    public function getName(): string
    {
        return self::CANONICAL;
    }

    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetchSvg
    {

        foreach (array_keys($tagAttributes->getComponentAttributes()) as $svgAttribute) {
            switch ($svgAttribute) {
                case Dimension::WIDTH_KEY:
                case Dimension::HEIGHT_KEY:
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    try {
                        $lengthInt = DataType::toInteger($value);
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
                case Dimension::RATIO_ATTRIBUTE:
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
                    try {
                        $lengthFloat = DataType::toFloat($value);
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The $svgAttribute value ($value) of the svg ($this) is not a float", self::CANONICAL);
                        continue 2;
                    }
                    $this->setRequestedAspectRatio($lengthFloat);
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
                    $value = $tagAttributes->getValueAndRemove($svgAttribute);
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
            }

        }
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionCompile
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    private function getIconPath(): DokuPath
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
            throw new ExceptionNotFound("The name ($requestedName) is not a valid icon name. Error: ({$e->getMessage()}.", self::CANONICAL);
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
        $this->setOriginalPath($originalPath);
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
            return $this->getOriginalPath()->getLastNameWithoutExtension();
        }
    }
}
