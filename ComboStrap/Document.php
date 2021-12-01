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

    public function exists(): bool
    {
        return $this->getFile()->exists();
    }

    /**
     * Generate the content if it does not exists or the content is stale
     * otherwise return the content
     * @return false|mixed|string
     */
    public function getOrGenerateContent()
    {
        if ($this->isStale() || !$this->getFile()->exists()) {

            return $this->compile();

        } else {

            $content = $this->getFile()->getContent();

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

    public function delete(): Document
    {
        $this->getFile()->remove();
        return $this;
    }

    public function deleteIfExists(): Document
    {
        $this->getFile()->removeIfExists();
        return $this;
    }

    public function getModifiedTime(): ?\DateTime
    {

        return $this->getFile()->getModifiedTime();

    }

    abstract public function getFile(): File;

    /**
     * @return bool if the output file is stale
     * The most obvious reason would be that the source file has changed
     * but change in configuration may also stale output file
     */
    abstract public function isStale(): bool;

}
