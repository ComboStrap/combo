<?php

namespace ComboStrap;

use dokuwiki\Extension\Plugin;

class Message
{


    const SIGNATURE_CLASS = "signature";
    const TAG = "message";
    private $content = "";
    private $type = self::TYPE_CLASSIC;

    const TYPE_CLASSIC = 'Classic';
    const TYPE_WARNING = 'Warning';
    private $class;
    /**
     * @var Plugin
     */
    private $plugin;
    private $signatureCanonical;
    private $signatureName;

    /**
     * @param Plugin $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }


    public function addContent($message)
    {
        $this->content .= $message;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setSignatureCanonical($canonical)
    {
        $this->signatureCanonical = $canonical;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setSignatureName($signatureName)
    {
        $this->signatureName = $signatureName;
    }

    /**
     * Used when sending message and in the main content
     * @return string
     */
    public function getHtml()
    {

        PluginUtility::getSnippetManager()->upsertCssSnippetForRequest(self::TAG);
        $message = "";
        if ($this->getContent() <> "") {

            if ($this->getType() == Message::TYPE_CLASSIC) {
                $message .='<div class="alert alert-success combo-message ' . $this->class . '" role="alert">';
            } else {
                $message .='<div class="alert alert-warning combo-message ' . $this->class . '" role="alert">';
            }

            $message .= $this->getContent();

            $message .='<div class="'.self::SIGNATURE_CLASS.'">' . $this->plugin->getLang('message_come_from') . PluginUtility::getUrl($this->signatureCanonical, $this->signatureName, false) . '</div>';
            $message .='</div>';

        }
        return $message;
    }

}
