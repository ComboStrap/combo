<?php

namespace ComboStrap;

use dokuwiki\Extension\Plugin;

class Message
{


    const SIGNATURE_CLASS = "signature";
    const TAG = "message";
    const TYPE_ERROR = "error";
    private $content = [];
    private $type;

    const TYPE_INFO = 'Info';
    const TYPE_WARNING = 'Warning';

    /**
     * @var Plugin
     */
    private $plugin;
    /**
     * @var string the page canonical
     */
    private $canonical = "support";
    private $signatureName;

    private $class;
    /**
     * @var int
     */
    private $status;


    public function __construct($type)
    {
        $this->type = $type;
    }

    public static function createInfoMessage($plainText = null): Message
    {
        $message = new Message(self::TYPE_INFO);
        if ($plainText !== null) {
            $message->addPlainTextContent($plainText);
        }
        return $message;
    }

    public static function createWarningMessage($plainText = null): Message
    {
        $message = new Message(self::TYPE_WARNING);
        if ($plainText !== null) {
            $message->addPlainTextContent($plainText);
        }
        return $message;
    }


    public
    function addContent($message, $mime): Message
    {
        if (!isset($this->content[$mime])) {
            $this->content[$mime] = [];
        }
        $this->content[$mime][] = $message;
        return $this;
    }

    public static function createErrorMessage(string $plainText): Message
    {
        $message = new Message(self::TYPE_ERROR);
        if ($plainText !== null) {
            $message->addPlainTextContent($plainText);
        }
        return $message;
    }


    public
    function addHtmlContent($message): Message
    {
        return $this->addContent($message, Mime::HTML);
    }

    public
    function setCanonical($canonical): Message
    {
        $this->canonical = $canonical;
        return $this;
    }

    public
    function setClass($class): Message
    {
        $this->class = $class;
        return $this;
    }

    public
    function getContent($mime = null): string
    {
        if ($mime != null) {
            return implode(DOKU_LF, $this->content[$mime]);
        }
        $contentAll = "";
        foreach ($this->content as $contentArray) {
            $contentAll .= implode(DOKU_LF, $contentArray);
        }
        return $contentAll;
    }

    public
    function getPlainTextContent(): ?string
    {
        $plainTextLines = $this->content[Mime::PLAIN_TEXT];
        if ($plainTextLines === null) {
            return null;
        }
        return implode(DOKU_LF, $plainTextLines);
    }

    public
    function getType(): string
    {
        return $this->type;
    }

    public
    function setSignatureName($signatureName): Message
    {
        $this->signatureName = $signatureName;
        return $this;
    }

    /**
     * Return an HTML Box (Used when sending message and in the main content)
     * @return string
     */
    public
    function toHtmlBox(): string
    {

        PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::TAG);
        $message = "";

        $tagAttributes = TagAttributes::createEmpty("message")
            ->addClassName("alert")
            ->addOutputAttributeValue("role", "alert");
        if ($this->class !== null) {
            $tagAttributes->addClassName($this->class);
        }
        if (sizeof($this->content) <> 0) {

            if ($this->getType() == Message::TYPE_INFO) {
                $tagAttributes->addClassName("alert-success");
            } else {
                $tagAttributes->addClassName("alert-warning");
            }

            $message = $tagAttributes->toHtmlEnterTag("div");
            $htmlContent = $this->getContent(Mime::HTML);
            if ($htmlContent !== null) {
                $message .= $htmlContent;
            }

            /**
             * If this is a test call without a plugin
             * we have no plugin attached
             */
            $firedByLang = "This message was fired by the ";
            if ($this->plugin != null) {
                $firedByLang = $this->plugin->getLang('message_come_from');
            }

            $message .= '<div class="' . self::SIGNATURE_CLASS . '">' . $firedByLang . PluginUtility::getDocumentationHyperLink($this->canonical, $this->signatureName, false) . '</div>';
            $message .= '</div>';

            /**
             * In dev, to spot the XHTML compliance error
             */
            if (PluginUtility::isDevOrTest()) {
                $isXml = XmlUtility::isXml($message);
                if (!$isXml) {
                    LogUtility::msg("This message is not xml compliant ($message)");
                    $message = <<<EOF
<div class='alert alert-warning'>
    <p>This message is not xml compliant</p>
    <pre>$message</pre>
</div>
EOF;
                }
            }

        }
        return $message;
    }

    /**
     * This is barely used because the syntax plugin does
     * not even inherit from {@link \dokuwiki\Extension\Plugin}
     * but from {@link \dokuwiki\Parsing\ParserMode\Plugin}
     * What fuck up is fucked upx
     * @param Plugin $plugin
     * @return $this
     */
    public function setPlugin(Plugin $plugin): Message
    {
        $this->plugin = $plugin;
        return $this;
    }

    public function addPlainTextContent($text): Message
    {
        return $this->addContent($text, Mime::PLAIN_TEXT);
    }

    public function sendLogMsg()
    {
        $content = $this->getContent(Mime::PLAIN_TEXT);
        switch ($this->type) {
            case self::TYPE_WARNING:
                $type = LogUtility::LVL_MSG_WARNING;
                break;
            case self::TYPE_INFO:
                $type = LogUtility::LVL_MSG_INFO;
                break;
            case self::TYPE_ERROR:
                $type = LogUtility::LVL_MSG_ERROR;
                break;
            default:
                $type = LogUtility::LVL_MSG_ERROR;
        }
        LogUtility::msg($content, $type, $this->canonical);
    }

    public function getDocumentationHyperLink(): ?string
    {
        if ($this->canonical !== null) {
            $canonicalPath = DokuPath::createFromUnknownRoot($this->canonical);
            $label = $canonicalPath->toLabel();
            return PluginUtility::getDocumentationHyperLink($this->canonical, $label, false);
        } else {
            return null;
        }
    }

    /**
     * An exit code / status
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): Message
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): int
    {
        if ($this->status !== null) {
            return $this->status;
        }
        if ($this->type === null) {
            return HttpResponse::STATUS_ALL_GOOD;
        }
        switch ($this->type) {
            case self::TYPE_ERROR:
                return HttpResponse::STATUS_INTERNAL_ERROR;
            case self::TYPE_INFO:
            default:
                return HttpResponse::STATUS_ALL_GOOD;
        }

    }

    public function setType(string $type): Message
    {
        $this->type = $type;
        return $this;
    }

}
