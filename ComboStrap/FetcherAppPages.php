<?php

namespace ComboStrap;


use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlEndpoint;
use dokuwiki\Action\Exception\FatalException;
use dokuwiki\ActionRouter;

/**
 * No Cache for the idenity forms
 * as if there is a cache problems,
 * We can't login anymore for instance
 */
class FetcherAppPages extends IFetcherAbs implements IFetcherString
{

    const NAME = "page-app";
    const CANONICAL = "page-app";

    use FetcherTraitWikiPath;

    private string $requestedLayout;
    private bool $build = false;


    private TemplateForWebPage $pageLayout;


    /**
     * @param Url|null $url
     * @return Url
     *
     * Note: The fetch url is the {@link FetcherCache keyCache}
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * Overwrite default fetcher endpoint
         * that is {@link UrlEndpoint::createFetchUrl()}
         */
        $url = UrlEndpoint::createDokuUrl();
        try {
            $url->addQueryParameter(PageTemplateName::PROPERTY_NAME, $this->getRequestedLayout());
        } catch (ExceptionNotFound $e) {
            // no requested layout
        }
        return parent::getFetchUrl($url)
            ->addQueryParameter("do", self::NAME);
    }


    /**
     * @return string
     * @throws ExceptionNotFound - if the main markup fragment could not be found
     * @throws ExceptionBadArgument - if the main markup fragment path can not be transformed as wiki path
     */
    public function getFetchString(): string
    {

        $contextPath = $this->getSourcePath();
        if($contextPath->hasRevision()) {
            /**
             * In the diff {@link ExecutionContext::DIFF_ACTION},
             * the `rev` property is passed in the URL and we get a bad context
             */
            $contextPath = WikiPath::createMarkupPathFromId($contextPath->getWikiId());
        }


        if (!$this->build) {

            $this->build = true;
            $pageLang = Site::getLangObject();
            $title = $this->getLabel();

            $this->pageLayout = TemplateForWebPage::create()
                ->setRequestedTemplateName($this->getRequestedTemplateOrDefault())
                ->setRequestedLang($pageLang)
                ->setRequestedEnableTaskRunner(false) // no page id
                ->setRequestedTitle($title)
                ->setRequestedContextPath($contextPath);

        }


        /**
         * The content
         *
         * Adapted from {@link tpl_content()}
         *
         * As this is only for identifier forms,
         * the buffer should not be a problem
         *
         * Because all admin action are using the php buffer
         * We can then have an overflow
         */
        global $ACT;
        ob_start();
        $actionName = FetcherAppPages::class . "::tpl_content_core";
        \dokuwiki\Extension\Event::createAndTrigger('TPL_ACT_RENDER', $ACT, $actionName);
        $mainHtml = ob_get_clean();


        /**
         * Add css
         */
        switch ($ACT) {
            case ExecutionContext::PREVIEW_ACTION:
            case ExecutionContext::EDIT_ACTION:
                $markupPath = MarkupPath::createPageFromPathObject($contextPath);
                if ($ACT === ExecutionContext::PREVIEW_ACTION && $markupPath->isSlot()) {
                    SlotSystem::sendContextPathMessage(SlotSystem::getContextPath());
                }
        }


        /**
         * Generate the whole html page via the layout
         */
        return $this->pageLayout
            ->setMainContent($mainHtml)
            ->render();

    }


    /**
     *
     */
    function getBuster(): string
    {
        return "";
    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::HTML);
    }

    public function getFetcherName(): string
    {
        return self::NAME;
    }

    /**
     * We take over the {@link tpl_content_core()} of Dokuwiki
     * because the instance of the router is not reinit.
     * We get then problem on test because of the private global static {@link ActionRouter::$instance) variable
     * @return bool
     * @noinspection PhpUnused - is a callback to the event TPL_ACT_RENDER called in this class
     */
    static public function tpl_content_core(): bool
    {

        /**
         * Was false, is true
         */
        $router = ActionRouter::getInstance(true);
        try {
            $router->getAction()->tplContent();
        } catch (FatalException $e) {
            // there was no content for the action
            msg(hsc($e->getMessage()), -1);
            return false;
        }
        return true;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedLayout(): string
    {
        if (!isset($this->requestedLayout)) {
            throw new ExceptionNotFound("No requested layout");
        }
        return $this->requestedLayout;
    }


    /**
     *
     */
    public function close(): FetcherAppPages
    {
        // nothing to do
        return $this;
    }


    private function getRequestedTemplateOrDefault(): string
    {
        try {
            return $this->getRequestedLayout();
        } catch (ExceptionNotFound $e) {
            global $ACT;
            switch ($ACT) {
                case ExecutionContext::EDIT_ACTION:
                case ExecutionContext::PREVIEW_ACTION:
                    return PageTemplateName::APP_EDIT;
                case ExecutionContext::LOGIN_ACTION:
                    return PageTemplateName::APP_LOGIN;
                case ExecutionContext::REGISTER_ACTION:
                    return PageTemplateName::APP_REGISTER;
                case ExecutionContext::RESEND_PWD_ACTION:
                    return PageTemplateName::APP_RESEND_PWD;
                case ExecutionContext::REVISIONS_ACTION:
                    return PageTemplateName::APP_REVISIONS;
                case ExecutionContext::DIFF_ACTION:
                    return PageTemplateName::APP_DIFF;
                case ExecutionContext::INDEX_ACTION:
                    return PageTemplateName::APP_INDEX;
                case ExecutionContext::PROFILE_ACTION:
                    return PageTemplateName::APP_PROFILE;
                default:
                case ExecutionContext::SEARCH_ACTION:
                    return PageTemplateName::APP_SEARCH;
            }

        }
    }


    public function getLabel(): string
    {
        global $ACT;
        $label = "App Pages";
        switch ($ACT) {
            case ExecutionContext::RESEND_PWD_ACTION:
                $label = "Resend Password";
                break;
            case ExecutionContext::LOGIN_ACTION:
                $label = "Login";
                break;
            case ExecutionContext::REGISTER_ACTION:
                $label = "Register";
                break;
            case ExecutionContext::EDIT_ACTION:
            case ExecutionContext::PREVIEW_ACTION:
                $label = "Editor";
                break;
            case ExecutionContext::DIFF_ACTION:
                $label = "Diff";
                break;
            case ExecutionContext::REVISIONS_ACTION:
                $label = "Log";
                break;
        }
        return $label;
    }


    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher
    {
        parent::buildFromTagAttributes($tagAttributes);
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        return $this;
    }
}
