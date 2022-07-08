<?php

namespace ComboStrap;

/**
 * A object that permits to set the global state request data
 *
 *
 *
 * We don't restore the global state because this is useless
 * Dokuwiki does not have the notion of request object, all request data are passed via global state
 *
 *
 *   * Dokuwiki uses {@link Cache::useCache()} needs them also therefore if you split the process in two function: useCache
 * and processing, you need to set and unset each time
 *   * Get function may also needs them if the Object cache depends on them
 *   * ...
 *
 * Before executing any request, this function should be call
 */
class WikiRequestEnvironment
{


    private ?string $actualGlobalId;
    /**
     * It may be an array when preview/save/cancel
     * @var array|string|null
     */
    private $actualAct;
    private ?string $actualRequestedID;

    /**
     * @var string - to check if the requested id was set, if not the running id is set
     */
    private string $requestedId;

    public function __construct()
    {
        /**
         * The running fragment
         */
        global $ID;
        $this->actualGlobalId = $ID;

        /**
         * The requested action
         */
        global $ACT;
        $this->actualAct = $ACT;

        /**
         * The requested Page
         */
        $this->actualRequestedID = $this->getRequestedIdViaGlobalVariables();

    }

    public static function createAndCaptureState(): WikiRequestEnvironment
    {
        return new WikiRequestEnvironment();
    }

    public function setNewRunningId(string $runningId): WikiRequestEnvironment
    {
        global $ID;
        $ID = $runningId;
        return $this;
    }

    /**
     * A running id can be a secondary fragment
     * The requested id is the main fragment
     *
     * Note that this should be set only once at the request level
     * (ie in test purpose or the fetcher)
     *
     * @param string $requestedId
     * @return $this
     */
    public function setNewRequestedId(string $requestedId): WikiRequestEnvironment
    {
        $this->requestedId = $requestedId;
        global $INPUT;
        $INPUT->set("id", $requestedId);
        return $this;
    }

    public function setNewAct(string $string): WikiRequestEnvironment
    {
        global $ACT;
        $ACT = $string;
        return $this;
    }

    public function restoreState()
    {

        global $ACT;
        $ACT = $this->actualAct;
        global $ID;
        $ID = $this->actualGlobalId;
        global $INPUT;
        $INPUT->set(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE, $this->actualRequestedID);

    }

    public function getActualRequestedId(): string
    {
        return $this->actualRequestedID;
    }


    private function getRequestedIdViaGlobalVariables()
    {
        /**
         * {@link getID()} reads the id from the input variable
         *
         * The {@link \action_plugin_combo_lang::load_lang()}
         * set it back right
         */
        global $INPUT;
        $id = $INPUT->str("id");
        if (!empty($id)) {
            return $id;
        }

        /**
         * This should be less used
         * but shows where the requested id is spilled in dokuwiki
         *
         * If the component is in a sidebar, we don't want the ID of the sidebar
         * but the ID of the page.
         */
        global $INFO;
        if ($INFO !== null) {
            $callingId = $INFO['id'];
            if (!empty($callingId)) {
                return $callingId;
            }
        }


        global $ID;
        if ($ID !== null) {
            return $ID;
        }

        /**
         * This is the case with event triggered
         * before DokuWiki such as
         * https://www.dokuwiki.org/devel:event:init_lang_load
         */
        global $_REQUEST;
        if (isset($_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE])) {
            return $_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
        }

        if (!PluginUtility::isDevOrTest()) {
            // should never happen, we don't throw an exception
            LogUtility::internalError("Internal Error: The requested wiki id could not be determined");
        }

        return MarkupDynamicRender::DEFAULT_SLOT_ID_FOR_TEST;

    }

    /**
     * @return string
     */
    public function getRequestedId(): string
    {
        return $this->requestedId;
    }

    public function getActualAct()
    {
        return $this->actualAct;
    }

    public function getActualGlobalId(): ?string
    {
        return $this->actualGlobalId;
    }

}
