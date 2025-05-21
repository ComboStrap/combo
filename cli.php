<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

use ComboStrap\DatabasePageRow;
use ComboStrap\Event;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\ExceptionSqliteNotAvailable;
use ComboStrap\ExecutionContext;
use ComboStrap\FsWikiUtility;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Field\BacklinkCount;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\Sqlite;
use splitbrain\phpcli\Options;

/**
 * All dependency are loaded
 */
require_once(__DIR__ . '/vendor/autoload.php');

/**
 * The memory of the server 128 is not enough
 */
ini_set('memory_limit', '256M');


/**
 * Class cli_plugin_combo
 *
 * This is a cli:
 * https://www.dokuwiki.org/devel:cli_plugins#example
 *
 * Usage:
 *
 * ```
 * docker exec -ti $(CONTAINER) /bin/bash
 * ```
 * ```
 * set animal=animal-directory-name
 * php ./bin/plugin.php combo --help
 * ```
 * or via the IDE
 *
 *
 * Example:
 * https://www.dokuwiki.org/tips:grapher
 *
 */
class cli_plugin_combo extends DokuWiki_CLI_Plugin
{

    const METADATA_TO_DATABASE = "metadata-to-database";
    const ANALYTICS = "analytics";
    const METADATA_TO_FRONTMATTER = "metadata-to-frontmatter";
    const BROKEN_LINKS = "broken-links";
    const SYNC = "sync";
    const PLUGINS_TO_UPDATE = "plugins-to-update";
    const FORCE_OPTION = 'force';
    const PORT_OPTION = 'port';
    const HOST_OPTION = 'host';
    const CANONICAL = "combo-cli";


    /**
     * register options and arguments
     * @param Options $options
     *
     * Note the animal is set in {@link DokuWikiFarmCore::detectAnimal()}
     * via the environment variable `animal` that is passed in the $_SERVER variable
     */
    protected function setup(Options $options)
    {
        $help = <<<EOF
ComboStrap Administrative Commands


Example:
  * Replicate all pages into the database
```bash
php ./bin/plugin.php combo metadata-to-database --host serverHostName  --port 80 :
# or
php ./bin/plugin.php combo metadata-to-database --host serverHostName  --port 80 /
```
  * Replicate only the page `:namespace:my-page`
```bash
php ./bin/plugin.php combo metadata-to-database --host serverHostName  --port 80 :namespace:my-page
# or
php ./bin/plugin.php combo metadata-to-database --host serverHostName  --port 80 /namespace/my-page
```

Animal: If you want to use it for an animal farm, you need to set first the animal directory name in a environment variable
```bash
animal=animal-directory-name php ./bin/plugin.php combo
```

EOF;

        /**
         * Global Options
         */
        $options->setHelp($help);
        $options->registerOption('version', 'print version', 'v');
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $options->registerOption(
            'dry',
            "Optional, dry-run",
            'd', false);
        $options->registerOption(
            'output',
            "Optional, where to store the analytical data as csv eg. a filename.",
            'o',
            true
        );

        /**
         * Command without options
         */
        $options->registerCommand(self::ANALYTICS, "Start the analytics and export optionally the data");
        $options->registerCommand(self::PLUGINS_TO_UPDATE, "List the plugins to update");
        $options->registerCommand(self::BROKEN_LINKS, "Output Broken Links");


        // Metadata to database command
        $options->registerCommand(self::METADATA_TO_DATABASE, "Replicate the file system metadata into the database");
        $options->registerOption(
            self::HOST_OPTION,
            "The http host name of your server. This value is used by dokuwiki in the rendering cache key",
            null,
            true,
            self::METADATA_TO_DATABASE
        );
        $options->registerOption(
            self::PORT_OPTION,
            "The http host port of your server. This value is used by dokuwiki in the rendering cache key",
            null,
            true,
            self::METADATA_TO_DATABASE
        );
        $options->registerOption(
            self::FORCE_OPTION,
            "Replicate with force",
            'f',
            false,
            self::METADATA_TO_DATABASE
        );
        $startPathArgName = 'startPath';
        $startPathHelpDescription = "The start path (a page or a directory). For all pages, type the root directory '/' or ':'";
        $options->registerArgument(
            $startPathArgName,
            $startPathHelpDescription,
            true,
            self::METADATA_TO_DATABASE
        );


        // Metadata Command definition
        $options->registerCommand(self::METADATA_TO_FRONTMATTER, "Replicate the file system metadata into the page frontmatter");
        $options->registerArgument(
            $startPathArgName,
            $startPathHelpDescription,
            true,
            self::METADATA_TO_FRONTMATTER
        );

        // Sync Command Definition
        $options->registerCommand(self::SYNC, "Delete the non-existing pages in the database");
        $options->registerArgument(
            $startPathArgName,
            $startPathHelpDescription,
            true,
            self::SYNC
        );

    }

    /**
     * The main entry
     * @param Options $options
     */
    protected function main(Options $options)
    {


        if (isset($_REQUEST['animal'])) {
            // on linux
            echo "Animal detected: " . $_REQUEST['animal'] . "\n";
        } else {
            // on windows
            echo "No Animal detected\n";
            echo "Conf: " . DOKU_CONF . "\n";
        }

        $args = $options->getArgs();


        $depth = $options->getOpt('depth', 0);
        $cmd = $options->getCmd();

        try {
            switch ($cmd) {
                case self::METADATA_TO_DATABASE:
                    $startPath = $this->getStartPath($args);
                    $force = $options->getOpt(self::FORCE_OPTION, false);
                    $hostOptionValue = $options->getOpt(self::HOST_OPTION, null);
                    if ($hostOptionValue === null) {
                        fwrite(STDERR, "The host name is mandatory");
                        return;
                    }
                    $_SERVER['HTTP_HOST'] = $hostOptionValue;
                    $portOptionName = $options->getOpt(self::PORT_OPTION, null);
                    if ($portOptionName === null) {
                        fwrite(STDERR, "The host port is mandatory");
                        return;
                    }
                    $_SERVER['SERVER_PORT'] = $portOptionName;
                    $this->index($startPath, $force, $depth);
                    break;
                case self::METADATA_TO_FRONTMATTER:
                    $startPath = $this->getStartPath($args);
                    $this->frontmatter($startPath, $depth);
                    break;
                case self::BROKEN_LINKS:
                    $this->brokenLinks();
                    break;
                case self::ANALYTICS:
                    $startPath = $this->getStartPath($args);
                    $output = $options->getOpt('output', '');
                    //if ($output == '-') $output = 'php://stdout';
                    $this->analytics($startPath, $output, $depth);
                    break;
                case self::SYNC:
                    $this->deleteNonExistingPageFromDatabase();
                    break;
                case self::PLUGINS_TO_UPDATE:
                    $this->pluginToUpdate();
                    break;
                default:
                    if ($cmd !== "") {
                        fwrite(STDERR, "Combo: Command unknown (" . $cmd . ")");
                    } else {
                        echo $options->help();
                    }
                    exit(1);
            }
        } catch (Exception $exception) {
            fwrite(STDERR, "An internal error has occurred. " . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            exit(1);
        }


    }

    /**
     * @param array $namespaces
     * @param bool $rebuild
     * @param int $depth recursion depth. 0 for unlimited
     * @throws ExceptionCompile
     */
    private function index($namespaces = array(), $rebuild = false, $depth = 0)
    {

        /**
         * Run as admin to overcome the fact that
         * anonymous user cannot see all links and backlinks
         */
        global $USERINFO;
        $USERINFO['grps'] = array('admin');
        global $INPUT;
        $INPUT->server->set('REMOTE_USER', "cli");

        $pages = FsWikiUtility::getPages($namespaces, $depth);

        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            global $ID;
            $ID = $id;
            /**
             * Indexing the page start the database replication
             * See {@link action_plugin_combo_indexer}
             */
            $pageCounter++;
            $executionContext = ExecutionContext::getActualOrCreateFromEnv();
            try {
                /**
                 * If the page does not need to be indexed, there is no run
                 * and false is returned
                 */
                $indexedOrNot = idx_addPage($id, true, true);
                if ($indexedOrNot) {
                    LogUtility::msg("The page {$id} ($pageCounter / $totalNumberOfPages) was indexed and replicated", LogUtility::LVL_MSG_INFO);
                } else {
                    LogUtility::msg("The page {$id} ($pageCounter / $totalNumberOfPages) has an error", LogUtility::LVL_MSG_ERROR);
                }
            } catch (ExceptionRuntime $e) {
                LogUtility::msg("The page {$id} ($pageCounter / $totalNumberOfPages) has an error: " . $e->getMessage(), LogUtility::LVL_MSG_ERROR);
            } finally {
                $executionContext->close();
            }
        }
        /**
         * Process all backlinks
         */
        echo "Processing Replication Request\n";
        Event::dispatchEvent(PHP_INT_MAX);

    }

    private function analytics($namespaces = array(), $output = null, $depth = 0)
    {

        $fileHandle = null;
        if (!empty($output)) {
            $fileHandle = @fopen($output, 'w');
            if (!$fileHandle) $this->fatal("Failed to open $output");
        }

        /**
         * Run as admin to overcome the fact that
         * anonymous user cannot see all links and backlinks
         */
        global $USERINFO;
        $USERINFO['grps'] = array('admin');
        global $INPUT;
        $INPUT->server->set('REMOTE_USER', "cli");

        $pages = FsWikiUtility::getPages($namespaces, $depth);


        if (!empty($fileHandle)) {
            $header = array(
                'id',
                'backlinks',
                'broken_links',
                'changes',
                'chars',
                'external_links',
                'external_medias',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'internal_links',
                'internal_medias',
                'words',
                'score'
            );
            fwrite($fileHandle, implode(",", $header) . PHP_EOL);
        }
        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            $page = MarkupPath::createMarkupFromId($id);


            $pageCounter++;
            /**
             * Analytics
             */
            echo "Analytics Processing for the page {$id} ($pageCounter / $totalNumberOfPages)\n";
            $executionContext = ExecutionContext::getActualOrCreateFromEnv();
            try {
                $analyticsPath = $page->fetchAnalyticsPath();
            } catch (ExceptionNotExists $e) {
                LogUtility::error("The analytics document for the page ($page) was not found");
                continue;
            } catch (ExceptionCompile $e) {
                LogUtility::error("Error when get the analytics.", self::CANONICAL, $e);
                continue;
            } finally {
                $executionContext->close();
            }

            try {
                $data = \ComboStrap\Json::createFromPath($analyticsPath)->toArray();
            } catch (ExceptionBadSyntax $e) {
                LogUtility::error("The analytics json of the page ($page) is not conform");
                continue;
            } catch (ExceptionNotFound|ExceptionNotExists $e) {
                LogUtility::error("The analytics document ({$analyticsPath}) for the page ($page) was not found");
                continue;
            }

            if (!empty($fileHandle)) {
                $statistics = $data[renderer_plugin_combo_analytics::STATISTICS];
                $row = array(
                    'id' => $id,
                    'backlinks' => $statistics[BacklinkCount::getPersistentName()],
                    'broken_links' => $statistics[renderer_plugin_combo_analytics::INTERNAL_LINK_BROKEN_COUNT],
                    'changes' => $statistics[renderer_plugin_combo_analytics::EDITS_COUNT],
                    'chars' => $statistics[renderer_plugin_combo_analytics::CHAR_COUNT],
                    'external_links' => $statistics[renderer_plugin_combo_analytics::EXTERNAL_LINK_COUNT],
                    'external_medias' => $statistics[renderer_plugin_combo_analytics::EXTERNAL_MEDIA_COUNT],
                    PageH1::PROPERTY_NAME => $statistics[renderer_plugin_combo_analytics::HEADING_COUNT][PageH1::PROPERTY_NAME],
                    'h2' => $statistics[renderer_plugin_combo_analytics::HEADING_COUNT]['h2'],
                    'h3' => $statistics[renderer_plugin_combo_analytics::HEADING_COUNT]['h3'],
                    'h4' => $statistics[renderer_plugin_combo_analytics::HEADING_COUNT]['h4'],
                    'h5' => $statistics[renderer_plugin_combo_analytics::HEADING_COUNT]['h5'],
                    'internal_links' => $statistics[renderer_plugin_combo_analytics::INTERNAL_LINK_COUNT],
                    'internal_medias' => $statistics[renderer_plugin_combo_analytics::INTERNAL_MEDIA_COUNT],
                    'words' => $statistics[renderer_plugin_combo_analytics::WORD_COUNT],
                    'low' => $data[renderer_plugin_combo_analytics::QUALITY]['low']
                );
                fwrite($fileHandle, implode(",", $row) . PHP_EOL);
            }

        }
        if (!empty($fileHandle)) {
            fclose($fileHandle);
        }

    }


    /**
     * @throws ExceptionSqliteNotAvailable
     */
    private function deleteNonExistingPageFromDatabase()
    {
        LogUtility::msg("Starting: Deleting non-existing page from database");
        $sqlite = Sqlite::createOrGetSqlite();
        /** @noinspection SqlNoDataSourceInspection */
        $request = $sqlite
            ->createRequest()
            ->setQuery("select id as \"id\" from pages");
        $rows = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while getting the id pages. {$e->getMessage()}");
            return;
        } finally {
            $request->close();
        }
        $counter = 0;

        foreach ($rows as $row) {
            /**
             * Context
             * PHP Fatal error:  Allowed memory size of 268435456 bytes exhausted (tried to allocate 20480 bytes)
             * in /opt/www/datacadamia.com/inc/ErrorHandler.php on line 102
             */
            $executionContext = ExecutionContext::getActualOrCreateFromEnv();
            try {
                $counter++;
                $id = $row['id'];
                if (!page_exists($id)) {
                    echo 'Page does not exist on the file system. Delete from the database (' . $id . ")\n";
                    try {
                        $dbRow = DatabasePageRow::getFromDokuWikiId($id);
                        $dbRow->delete();
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                }
            } finally {
                $executionContext->close();
            }

        }
        LogUtility::msg("Sync finished ($counter pages checked)");

    }

    private function frontmatter($namespaces, $depth)
    {
        $pages = FsWikiUtility::getPages($namespaces, $depth);
        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        $pagesWithChanges = [];
        $pagesWithError = [];
        $pagesWithOthers = [];
        $notChangedCounter = 0;
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            global $ID;
            $ID = $id;
            $page = MarkupPath::createMarkupFromId($id);
            $pageCounter++;
            LogUtility::msg("Processing page $id ($pageCounter / $totalNumberOfPages) ", LogUtility::LVL_MSG_INFO);
            $executionContext = ExecutionContext::getActualOrCreateFromEnv();
            try {
                $message = MetadataFrontmatterStore::createFromPage($page)
                    ->sync();
                switch ($message->getStatus()) {
                    case syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_NOT_CHANGED:
                        $notChangedCounter++;
                        break;
                    case syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_DONE:
                        $pagesWithChanges[] = $id;
                        break;
                    case syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_ERROR:
                        $pagesWithError[$id] = $message->getPlainTextContent();
                        break;
                    default:
                        $pagesWithOthers[$id] = $message->getPlainTextContent();
                        break;

                }
            } catch (ExceptionCompile $e) {
                $pagesWithError[$id] = $e->getMessage();
            } finally {
                $executionContext->close();
            }

        }

        echo "\n";
        echo "Result:\n";
        echo "$notChangedCounter pages without any frontmatter modifications\n";

        if (sizeof($pagesWithError) > 0) {
            echo "\n";
            echo "The following pages had errors\n";
            $pageCounter = 0;
            $totalNumberOfPages = sizeof($pagesWithError);
            foreach ($pagesWithError as $id => $message) {
                $pageCounter++;
                LogUtility::msg("Page $id ($pageCounter / $totalNumberOfPages): " . $message);
            }
        } else {
            echo "No error\n";
        }

        if (sizeof($pagesWithChanges) > 0) {
            echo "\n";
            echo "The following pages had changed:\n";
            $pageCounter = 0;
            $totalNumberOfPages = sizeof($pagesWithChanges);
            foreach ($pagesWithChanges as $id) {
                $pageCounter++;
                LogUtility::msg("Page $id ($pageCounter / $totalNumberOfPages) ");
            }
        } else {
            echo "No changes\n";
        }

        if (sizeof($pagesWithOthers) > 0) {
            echo "\n";
            echo "The following pages had an other status";
            $pageCounter = 0;
            $totalNumberOfPages = sizeof($pagesWithOthers);
            foreach ($pagesWithOthers as $id => $message) {
                $pageCounter++;
                LogUtility::msg("Page $id ($pageCounter / $totalNumberOfPages) " . $message, LogUtility::LVL_MSG_ERROR);
            }
        }
    }

    private function getStartPath($args)
    {
        $sizeof = sizeof($args);
        switch ($sizeof) {
            case 0:
                fwrite(STDERR, "The start path is mandatory and was not given");
                exit(1);
            case 1:
                $startPath = $args[0];
                if (!in_array($startPath, [":", "/"])) {
                    // cleanId would return blank for a root
                    $startPath = cleanID($startPath);
                }
                break;
            default:
                fwrite(STDERR, "Too much arguments given $sizeof");
                exit(1);
        }
        return $startPath;
    }

    /**
     *
     * Print the extension/plugin to update
     *
     * Note, there is also an Endpoint:
     * self::EXTENSION_REPOSITORY_API.'?fmt=php&ext[]='.urlencode($name)
     * `http://www.dokuwiki.org/lib/plugins/pluginrepo/api.php?fmt=php&ext[]=`.urlencode($name)
     *
     * @noinspection PhpUndefinedClassInspection
     */
    private function pluginToUpdate()
    {

        if (class_exists(Local::class)) {
            /**
             * Release 2025-05-14 "Librarian"
             * https://www.dokuwiki.org/changes#release_2025-05-14_librarian
             * https://www.patreon.com/posts/new-extension-116501986
             * ./bin/plugin.php extension list
             * @link lib/plugins/extension/cli.php
             * Code based on https://github.com/giterlizzi/dokuwiki-template-bootstrap3/pull/617/files
             */
            try {
                $extensions = (new Local())->getExtensions();
                Repository::getInstance()->initExtensions(array_keys($extensions));
                foreach ($extensions as $extension) {
                    if ($extension->isEnabled() && $extension->isUpdateAvailable()) {
                        echo "The extension {$extension->getDisplayName()} should be updated";
                    }
                }
            } /** @noinspection PhpUndefinedClassInspection */ catch (ExtensionException $ignore) {
                // Ignore the exception
            }
            return;
        }


        $pluginList = plugin_list('', true);
        $extension = $this->loadHelper('extension_extension');
        foreach ($pluginList as $name) {

            /* @var helper_plugin_extension_extension $extension
             * old extension manager until Kaos
             */
            $extension->setExtension($name);
            /** @noinspection PhpUndefinedMethodInspection */
            if ($extension->updateAvailable()) {
                echo "The extension $name should be updated";
            }
        }


    }

    /**
     * @return void
     * Print the broken Links
     * @throws ExceptionSqliteNotAvailable
     */
    private function brokenLinks()
    {
        LogUtility::msg("Broken Links Started");
        $sqlite = Sqlite::createOrGetSqlite();
        $request = $sqlite
            ->createRequest()
            ->setQuery("with validPages as (select path, analytics
                     from pages
                     where json_valid(analytics) = 1)
select path,
       json_extract(analytics, '$.statistics.internal_broken_link_count') as broken_link,
       json_extract(analytics, '$.statistics.media.internal_broken_count') as broken_media
from validPages
where json_extract(analytics, '$.statistics.internal_broken_link_count') is not null
   or json_extract(analytics, '$.statistics.media.internal_broken_count') != 0");
        $rows = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while getting the id pages. {$e->getMessage()}");
            return;
        } finally {
            $request->close();
        }
        if (count($rows) == 0) {
            LogUtility::msg("No Broken Links");
            exit();
        }
        LogUtility::msg("Broken Links:");
        foreach ($rows as $row) {
            $path = $row["path"];
            $broken_link = $row["broken_link"];
            $broken_media = $row["broken_media"];
            echo "$path (Page: $broken_link, Media: $broken_media)    \n";
        }
        if (count($rows) != 0) {
            exit(1);
        }
    }
}
