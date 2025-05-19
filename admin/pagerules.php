<?php

use ComboStrap\DirectoryLayout;
use ComboStrap\LogUtility;
use ComboStrap\PageRules;
use ComboStrap\PluginUtility;
use ComboStrap\Site;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * The admin pages
 * need to inherit from this class
 *
 *
 * ! important !
 * The suffix of the class name should:
 *   * be equal to the name of the file
 *   * and have only letters
 */
class admin_plugin_combo_pagerules extends DokuWiki_Admin_Plugin
{
    const DELETE_ACTION = 'Delete';
    const SAVE_ACTION = 'save';

    /**
     * @var PageRules
     */
    private PageRules $pageRuleManager;


    /**
     * admin_plugin_combo constructor.
     *
     * Use the get function instead
     */
    public function __construct()
    {

        // enable direct access to language strings
        // of use of $this->getLang
        $this->setupLocale();


    }

    /**
     * Handle Sqlite instantiation  here and not in the constructor
     * to not make sqlite mandatory everywhere
     */
    private function initiatePageRuleManager()
    {

        if (!isset($this->pageRuleManager)) {

            $this->pageRuleManager = new PageRules();

        }
    }


    /**
     * Access for managers allowed
     */
    function forAdminOnly(): bool
    {
        return false;
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort(): int
    {
        return 140;
    }

    /**
     * return prompt for admin menu
     * @param string $language
     * @return string
     */
    function getMenuText($language): string
    {
        return ucfirst(PluginUtility::$PLUGIN_NAME) . " - " . $this->lang['PageRules'];
    }

    public function getMenuIcon()
    {
        return DirectoryLayout::getComboImagesDirectory()->resolve('page-next.svg')->toAbsoluteId();
    }


    /**
     * handle user request
     */
    function handle()
    {

        $this->initiatePageRuleManager();

        /**
         * If one of the form submit has the add key
         */
        if (($_POST[self::SAVE_ACTION] ?? null) && checkSecurityToken()) {

            $id = $_POST[PageRules::ID_NAME] ?? null;
            $matcher = $_POST[PageRules::MATCHER_NAME] ?? null;
            $target = $_POST[PageRules::TARGET_NAME] ?? null;
            $priority = $_POST[PageRules::PRIORITY_NAME] ?? null;

            if ($matcher == null) {
                msg('Matcher can not be null', LogUtility::LVL_MSG_ERROR);
                return;
            }
            if ($target == null) {
                msg('Target can not be null', LogUtility::LVL_MSG_ERROR);
                return;
            }

            if ($matcher == $target) {
                msg($this->lang['SameSourceAndTargetAndPage'] . ': ' . $matcher . '', LogUtility::LVL_MSG_ERROR);
                return;
            }

            if ($id == null) {
                if (!$this->pageRuleManager->patternExists($matcher)) {
                    $this->pageRuleManager->addRule($matcher, $target, $priority);
                    msg($this->lang['Saved'], LogUtility::LVL_MSG_INFO);
                } else {
                    msg("The matcher pattern ($matcher) already exists. The page rule was not inserted.", LogUtility::LVL_MSG_ERROR);
                }
            } else {
                $this->pageRuleManager->updateRule($id, $matcher, $target, $priority);
                msg($this->lang['Saved'], LogUtility::LVL_MSG_INFO);
            }


        }

        if (($_POST[self::DELETE_ACTION] ?? null) && checkSecurityToken()) {

            $ruleId = $_POST[PageRules::ID_NAME];
            $this->pageRuleManager->deleteRule($ruleId);
            msg($this->lang['Deleted'], LogUtility::LVL_MSG_INFO);

        }

    }

    /**
     * output appropriate html
     * TODO: Add variable parsing where the key is the key of the lang object ??
     */
    function html()
    {

        $this->initiatePageRuleManager();

        echo('<h1>' . ucfirst(PluginUtility::$PLUGIN_NAME) . ' - ' . ucfirst($this->getPluginComponent()) . '</a></h1>');
        $relativePath = 'admin/' . $this->getPluginComponent() . '_intro';
        echo($this->locale_xhtml($relativePath));

        // Forms
        if ($_POST['upsert'] ?? null) {

            $matcher = null;
            $target = null;
            $priority = 1;

            // Update ?
            $id = $_POST[PageRules::ID_NAME];
            if ($id != null) {
                $rule = $this->pageRuleManager->getRule($id);
                $matcher = $rule[PageRules::MATCHER_NAME];
                $target = $rule[PageRules::TARGET_NAME];
                $priority = $rule[PageRules::PRIORITY_NAME];
            }


            // Forms
            echo('<div class="level2" >');
            echo('<div id="form_container" style="max-width: 600px;">');
            echo('<form action="" method="post">');
            echo('<input type="hidden" name="sectok" value="' . getSecurityToken() . '" />');
            echo('<p><b>If the Dokuwiki ID matches the following pattern:</b></p>');
            $matcherDefault = "";
            if ($matcher != null) {
                $matcherDefault = 'value="' . $matcher . '"';
            }
            echo('<label for="' . PageRules::MATCHER_NAME . '">(You can use the asterisk (*) character)</label>');
            echo('<p><input type="text"  style="width: 100%;" id="' . PageRules::MATCHER_NAME . '" required="required" name="' . PageRules::MATCHER_NAME . '" ' . $matcherDefault . ' class="edit" placeholder="pattern"/> </p>');
            echo('<p><b>Then applies this redirect settings:</b></p>');
            $targetDefault = "";
            if ($matcher != null) {
                $targetDefault = 'value="' . $target . '"';
            }
            echo('<label for="' . PageRules::TARGET_NAME . '">Target: (A DokuWiki Id or an URL where you can use the ($) group character)</label>');
            echo('<p><input type="text" style="width: 100%;" required="required" id="' . PageRules::TARGET_NAME . '" name="' . PageRules::TARGET_NAME . '" ' . $targetDefault . ' class="edit" placeholder="target" /></p>');
            echo('<label for="' . PageRules::PRIORITY_NAME . '">Priority: (The order in which rules are applied)</label>');
            echo('<p><input type="text" id="' . PageRules::PRIORITY_NAME . '." style="width: 100%;" required="required" placeholder="priority" name="' . PageRules::PRIORITY_NAME . '" value="' . $priority . '" class="edit" /></p>');
            echo('<input type="hidden" name="do"    value="admin" />');
            if ($id != null) {
                echo('<input type="hidden" name="' . PageRules::ID_NAME . '" value="' . $id . '" />');
            }
            echo('<input type="hidden" name="page"  value="' . $this->getPluginName() . '_' . $this->getPluginComponent() . '" />');
            echo('<p>');
            echo('<a class="btn btn-light" href="?do=admin&page=webcomponent_pagerules" > ' . 'Cancel' . ' <a/>');
            echo('<input class="btn btn-primary" type="submit" name="save" class="button" value="' . 'Save' . '" />');
            echo('</p>');
            echo('</form>');
            echo('</div>');

            echo('</div>');


        } else {

            echo('<h2><a id="pagerules_list">' . 'Rules' . '</a></h2>');
            echo('<div class="level2">');

            echo('<form class="pt-3 pb-3" action="" method="post">');
            echo('<input type="hidden" name="sectok" value="' . getSecurityToken() . '" />');
            echo('    <input type="hidden" name="do"    value="admin" />');
            echo('	<input type="hidden" name="page"  value="' . $this->getPluginName() . '_' . $this->getPluginComponent() . '" />');
            echo('	<input type="submit" name="upsert" name="Create a page rule" class="button" value="' . $this->getLangOrDefault('AddNewRule', 'Add a new rule') . '" />');
            echo('</form>');

            // List of redirection
            $rules = $this->pageRuleManager->getRules();

            if (sizeof($rules) == 0) {
                echo('<p>No Rules found</p>');
            } else {
                echo('<div class="table-responsive">');

                echo('<table class="table table-hover">');
                echo('	<thead>');
                echo('		<tr>');
                echo('			<th>&nbsp;</th>');
                echo('			<th>' . $this->getLangOrDefault('Priority', 'Priority') . '</th>');
                echo('			<th>' . $this->getLangOrDefault('Matcher', 'Matcher') . '</th>');
                echo('			<th>' . $this->getLangOrDefault('Target', 'Target') . '</th>');
                echo('			<th>' . $this->getLangOrDefault('NDate', 'Date') . '</th>');
                echo('	    </tr>');
                echo('	</thead>');
                echo('	<tbody>');


                foreach ($rules as $key => $row) {

                    $id = $row[PageRules::ID_NAME];
                    $matcher = $row[PageRules::MATCHER_NAME];
                    $target = $row[PageRules::TARGET_NAME];
                    $timestamp = $row[PageRules::TIMESTAMP_NAME];
                    $priority = $row[PageRules::PRIORITY_NAME];


                    echo('	  <tr class="redirect_info">');
                    echo('		<td>');
                    echo('			<form action="" method="post" style="display: inline-block">');
                    echo('<input type="hidden" name="sectok" value="' . getSecurityToken() . '" />');
                    echo('<button style="background: none;border: 0;">');
                    echo(inlineSVG(DirectoryLayout::getComboImagesDirectory()->resolve('delete.svg')->toAbsoluteId()));
                    echo('</button>');
                    echo('				<input type="hidden" name="Delete"  value="Yes" />');
                    echo('				<input type="hidden" name="' . PageRules::ID_NAME . '"  value="' . $id . '" />');
                    echo('			</form>');
                    echo('			<form action="" method="post" style="display: inline-block">');
                    echo('<input type="hidden" name="sectok" value="' . getSecurityToken() . '" />');
                    echo('<button style="background: none;border: 0;">');
                    echo(inlineSVG(DirectoryLayout::getComboImagesDirectory()->resolve('file-document-edit-outline.svg')->toAbsoluteId()));
                    echo('</button>');
                    echo('				<input type="hidden" name="upsert"  value="Yes" />');
                    echo('				<input type="hidden" name="' . PageRules::ID_NAME . '"  value="' . $id . '" />');
                    echo('			</form>');

                    echo('		</td>');
                    echo('		<td>' . $priority . '</td>');
                    echo('	    <td>' . $matcher . '</td>');
                    echo('		<td>' . $target . '</td>');
                    echo('		<td>' . $timestamp . '</td>');
                    echo('    </tr>');
                }
                echo('  </tbody>');
                echo('</table>');
                echo('</div>'); //End Table responsive
            }

            echo('</div>'); // End level 2


        }


    }

    /**
     * An utility function to return the plugin translation or a default value
     * @param $id
     * @param $default
     * @return mixed|string
     */
    private function getLangOrDefault($id, $default)
    {
        $lang = $this->getLang($id);
        return $lang != '' ? $lang : $default;
    }


    static function getAdminPageName()
    {
        return PluginUtility::getAdminPageName(get_called_class());
    }

}
