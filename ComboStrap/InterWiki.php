<?php

namespace ComboStrap;

class InterWiki
{


    const DEFAULT_INTERWIKI_NAME = 'default';

    /**
     * The pattern that select the characters to encode in URL
     */
    const CHARACTERS_TO_ENCODE = '/[[\\\\\]^`{|}#%]/';

    private static ?array $INTERWIKI_URL_TEMPLATES = null;

    private string $name;
    private string $ref;

    private ?string $urlWithoutFragment = null;

    private ?string $fragment = null;

    private string $markupType;

    /**
     * @param string $interWikiRef - The interwiki
     * @param string $markupType - The {@link MarkupRef::getSchemeType()} ie media or link
     */
    public function __construct(string $interWikiRef, string $markupType)
    {

        $this->ref = $interWikiRef;
        $this->markupType = $markupType;
        [$this->name, $this->urlWithoutFragment] = explode(">", $interWikiRef, 2);


        $hash = strrchr($this->urlWithoutFragment, '#');
        if ($hash) {
            $this->urlWithoutFragment = substr($this->urlWithoutFragment, 0, -strlen($hash));
            $this->fragment = substr($hash, 1);
        }

    }

    public static function addInterWiki(string $name, string $value)
    {
        // init
        $scopeId = self::initInterWikis();
        self::$INTERWIKI_URL_TEMPLATES[$scopeId][$name] = $value;
    }

    private static function initInterWikis(): string
    {
        $requestedPage = PluginUtility::getRequestedWikiId();
        if (
            self::$INTERWIKI_URL_TEMPLATES === null
            || self::$INTERWIKI_URL_TEMPLATES[$requestedPage] === null
        ) {
            self::$INTERWIKI_URL_TEMPLATES = null;
            // scoped by request id to be able to work on test because it's a global variable
            self::$INTERWIKI_URL_TEMPLATES[$requestedPage] = getInterwiki();
        }
        return $requestedPage;
    }

    public static function createMediaInterWikiFromString(string $ref): InterWiki
    {
        return new InterWiki($ref, MarkupRef::MEDIA_TYPE);
    }

    /**
     * @throws ExceptionNotFound
     * @throws ExceptionBadSyntax
     * Adapted  from {@link Doku_Renderer_xhtml::_resolveInterWiki()}
     */
    public function toUrl(): Url
    {

        $originalInterWikiUrlTemplate = $this->getTemplateUrlStringOrDefault();
        $interWikiUrlTemplate = $originalInterWikiUrlTemplate;

        /**
         * Dokuwiki Id template
         */
        if ($interWikiUrlTemplate[0] === ':') {
            $interWikiUrlTemplate = str_replace(
                '{NAME}',
                $this->urlWithoutFragment,
                $interWikiUrlTemplate
            );
            if ($this->fragment !== null) {
                $interWikiUrlTemplate = "$interWikiUrlTemplate#$this->fragment";
            }
            switch ($this->markupType) {
                case MarkupRef::MEDIA_TYPE:
                    return MarkupRef::createMediaFromRef($interWikiUrlTemplate)->getUrl();
                case MarkupRef::LINK_TYPE:
                default:
                    return MarkupRef::createLinkFromRef($interWikiUrlTemplate)->getUrl();
            }

        }

        // Replace placeholder if any
        if (preg_match('#{URL}#', $interWikiUrlTemplate)) {

            // Replace the Url
            $interWikiUrlTemplate = str_replace(
                '{URL}',
                rawurlencode($this->urlWithoutFragment),
                $interWikiUrlTemplate
            );

        }

        // Name placeholder means replace with URL encoding
        if (preg_match('#{NAME}#', $interWikiUrlTemplate)) {

            $interWikiUrlTemplate = str_replace(
                '{NAME}',
                preg_replace_callback(
                    self::CHARACTERS_TO_ENCODE,
                    function ($match) {
                        return rawurlencode($match[0]);
                    },
                    $this->urlWithoutFragment
                ),
                $interWikiUrlTemplate
            );

        }

        // Url replacement
        if (preg_match('#{(SCHEME|HOST|PORT|PATH|QUERY)}#', $interWikiUrlTemplate)) {

            $parsed = parse_url($this->urlWithoutFragment);
            if (empty($parsed['scheme'])) $parsed['scheme'] = '';
            if (empty($parsed['host'])) $parsed['host'] = '';
            if (empty($parsed['port'])) $parsed['port'] = 80;
            if (empty($parsed['path'])) $parsed['path'] = '';
            if (empty($parsed['query'])) $parsed['query'] = '';
            $interWikiUrlTemplate = strtr($interWikiUrlTemplate, [
                '{SCHEME}' => $parsed['scheme'],
                '{HOST}' => $parsed['host'],
                '{PORT}' => $parsed['port'],
                '{PATH}' => $parsed['path'],
                '{QUERY}' => $parsed['query'],
            ]);
        }

        // If no replacement
        if ($interWikiUrlTemplate === $originalInterWikiUrlTemplate) {

            $interWikiUrlTemplate = $interWikiUrlTemplate . rawurlencode($this->urlWithoutFragment);

        }


        if ($this->fragment) $interWikiUrlTemplate .= '#' . rawurlencode($this->fragment);

        return Url::createFromString($interWikiUrlTemplate);
    }

    /**
     * @param string $interWikiRef
     * @return InterWiki
     */
    public static function createLinkInterWikiFromString(string $interWikiRef): InterWiki
    {
        return new InterWiki($interWikiRef, MarkupRef::LINK_TYPE);
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getTemplateUrlString(): string
    {
        $interWikis = $this->getInterWikis();

        $urlTemplate = $interWikis[$this->name];
        if ($urlTemplate !== null) {
            return $urlTemplate;
        }
        throw new ExceptionNotFound("No Wiki ($this->name) found");

    }

    private static function getInterWikis()
    {
        $scopeId = self::initInterWikis();
        return self::$INTERWIKI_URL_TEMPLATES[$scopeId];
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getTemplateUrlStringOrDefault()
    {
        try {
            return $this->getTemplateUrlString();
        } catch (ExceptionNotFound $e) {
            $interWikis = $this->getInterWikis();
            if (isset($interWikis[self::DEFAULT_INTERWIKI_NAME])) {
                $this->name = self::DEFAULT_INTERWIKI_NAME;
                return $interWikis[self::DEFAULT_INTERWIKI_NAME];
            }
        }
        throw new ExceptionNotFound("The inter-wiki ({$this->getWiki()}) does not exist and there is no default inter-wiki defined.");

    }

    public function getWiki()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->ref;
    }


}
