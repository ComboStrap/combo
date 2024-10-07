<?php

namespace ComboStrap;

class RouterRedirectionBuilder
{

    private string $origin;

    /**
     * The target is null when the origin is `GO_TO_EDIT_MODE`
     */
    private string $type;
    private ?MarkupPath $targetMarkupPath = null;
    private ?Web\Url $targetUrl = null;


    private function __construct(string $origin)
    {
        $this->origin = $origin;
    }

    /**
     * @param string $origin - the origin of the redirection
     * @return RouterRedirectionBuilder
     */
    public static function createFromOrigin(string $origin): RouterRedirectionBuilder
    {
        return new RouterRedirectionBuilder($origin);
    }


    /**
     * @param string $type - the type (permanent, ...))
     * @return $this
     */
    public function setType(string $type): RouterRedirectionBuilder
    {
        $this->type = $type;
        return $this;
    }


    public function build(): RouterRedirection
    {
        return new RouterRedirection($this);
    }

    /**
     * @param MarkupPath $path - the path to redirect
     * @return $this
     */
    public function setTargetMarkupPath(MarkupPath $path): RouterRedirectionBuilder
    {
        // ->getCanonicalUrl()->toAbsoluteUrlString()
        $this->targetMarkupPath = $path;
        return $this;
    }

    /**
     * @param Web\Url $url - the URL to redirect
     * @return $this
     */
    public function setTargetUrl(Web\Url $url): RouterRedirectionBuilder
    {
        $this->targetUrl = $url;
        return $this;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTargetMarkupPath(): ?MarkupPath
    {
        return $this->targetMarkupPath;
    }

    public function getTargetUrl(): ?Web\Url
    {
        return $this->targetUrl;
    }


}
