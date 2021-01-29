<?php

namespace ComboStrap;

use dokuwiki\Extension\Plugin;

class Note
{


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

    public function getHtml()
    {
        $message = "";
        if ($this->getContent() <> "") {

            if ($this->getType() == Note::TYPE_CLASSIC) {
                $message .='<div class="alert alert-success combo-message ' . $this->class . '" role="alert">';
            } else {
                $message .='<div class="alert alert-warning combo-message ' . $this->class . '" role="alert">';
            }

            $message .= $this->getContent();

            $message .='<div class="signature">' . $this->plugin->getLang('message_come_from') . PluginUtility::getUrl($this->signatureCanonical, $this->signatureName) . '</div>';
            $message .='</div>';

        }
        return $message;
    }

}
