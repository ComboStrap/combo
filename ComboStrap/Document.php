<?php


namespace ComboStrap;

/**
 * Class Document
 * @package ComboStrap
 * Represent a compiled document.
 */
abstract class Document
{

    /**
     * @var Page
     */
    private $page;


    /**
     * Document constructor.
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * Execute the compilation from one format to another
     * and stores the output in the {@link Document::getFile() cache file}
     * @return mixed
     */
    abstract function compile();

    function getPage(): Page
    {
        return $this->page;
    }

    /**
     * @return string - the file extension / format
     * For instance, "xhtml" for an html document
     */
    abstract function getExtension(): string;

    /**
     * @return string - the last part name of the renderer file without the php extension
     * known inside dokuwiki as the mode
     *
     * For instance for the renderer
     * renderer_plugin_combo_analytics
     * the name is
     * combo_analytics
     */
    abstract function getRendererName(): string;


    /**
     * Get the data from the cache file
     * or compile the content
     *
     * @return false|mixed|string
     */
    public function getOrGenerateContent()
    {
        if ($this->shouldCompile()) {

            /**
             * Cache Miss
             */
            return $this->compile();

        } else {

            $content = $this->getFileContent();

            /**
             * Cache hit
             */
            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
                && $this->getExtension() === HtmlDocument::extension
            ) {
                $logicalId = $this->getPage()->getLogicalId();
                $scope = $this->getPage()->getScope();
                $content = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"hit\" data-cache-file=\"{$this->getFile()->getAbsoluteFileSystemPath()}\"></div>" . $content;
            }
            return $content;
        }


    }

    /**
     *
     * @return null|mixed the content of the file (by default in a text format)
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getFileContent()
    {
        if (!$this->getFile()->exists()) {
            return null;
        }
        return $this->getFile()->getTextContent();
    }


    public function deleteIfExists(): Document
    {
        $this->getFile()->removeIfExists();
        return $this;
    }

    abstract public function getFile(): File;

    /**
     * @return bool true if the {@link Document::compile() compilation} should occurs
     *
     * For instance:
     *   * if the output cache file is stale: The most obvious reason would be that the source file has changed but change in configuration may also stale output file
     *   * True if the cache page does not exist
     *   * True if the cache is not allowed
     *
     */
    abstract public function shouldCompile(): bool;

}
