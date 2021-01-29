<?php


use ComboStrap\PluginUtility;
use dokuwiki\Extension\Plugin;

class Message
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

    /**
     * Print a message
     */
    public function printMessage()
    {
        if ($this->getContent() <> "") {

            if ($this->getType() == Message::TYPE_CLASSIC) {
                ptln('<div class="alert alert-success combo-message ' . $this->class . '" role="alert">');
            } else {
                ptln('<div class="alert alert-warning combo-message ' . $this->class . '" role="alert">');
            }

            print $this->getContent();

            print '<div class="signature">' . $this->plugin->getLang('message_come_from') . PluginUtility::getUrl($this->signatureCanonical, $this->signatureName) . '</div>';
            print('</div>');

        }
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

}
