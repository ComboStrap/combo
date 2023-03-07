<?php

namespace ComboStrap;

class InterWiki
{


    const DEFAULT_INTERWIKI_NAME = 'default';

    /**
     * The pattern that select the characters to encode in URL
     */
    const CHARACTERS_TO_ENCODE = '/[[\\\\\]^`{|}#%]/';
    const IW_PREFIX = "iw_";


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
        ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->addInterWiki($name, $value);

    }


    public static function createMediaInterWikiFromString(string $ref): InterWiki
    {
        return new InterWiki($ref, MarkupRef::MEDIA_TYPE);
    }

    /**
     * @return string - the general component class
     */
    public static function getComponentClass(): string
    {
        $oldClassName = SiteConfig::getConfValue(LinkMarkup::CONF_USE_DOKUWIKI_CLASS_NAME);
        if ($oldClassName) {
            return "interwiki";
        } else {
            return "link-interwiki";
        }
    }

    /**
     * @throws ExceptionNotFound
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
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

    private static function getInterWikis(): array
    {
        return ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getInterWikis();
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

    /**
     * @return string - the class for this specific interwiki
     */
    public function getSubComponentClass(): string
    {
        return self::IW_PREFIX . preg_replace('/[^_\-a-z0-9]+/i', '_', $this->getWiki());
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getSpecificCssRules(): string
    {

        /**
         * Adapted from {@link css_interwiki()}
         */
        foreach (['svg', 'png', 'gif'] as $ext) {
            $file = 'lib/images/interwiki/' . $this->name . '.' . $ext;
            $urlFile = DOKU_BASE . $file;
            $class = $this->getSubComponentClass();
            if (file_exists(DOKU_INC . $file)) {
                return <<<EOF
a.$class {
    background-image: url($urlFile)
}
EOF;
            }
        }
        throw new ExceptionNotFound("No interwiki file found");
    }

    public
    function getDefaultCssRules(): string
    {
        $url = DOKU_BASE . 'lib/images/interwiki.svg';
        return <<<EOF
a.interwiki {
    background: transparent url($url) 0 0 no-repeat;
    background-size: 1.2em;
    padding: 0 0 0 1.4em;
}
EOF;
    }


}
