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
if (!defined('DOKU_INC')) die();

use ComboStrap\Analytics;
use splitbrain\phpcli\Options;

require_once(__DIR__ . '/class/Analytics.php');

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
 * ./bin/plugin.php combo -c
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

    /**
     * register options and arguments
     * @param Options $options
     */
    protected function setup(Options $options)
    {
        $options->setHelp('Run the analytics process');
        $options->registerOption('version', 'print version', 'v');
        $options->registerArgument(
            'namespaces',
            "If no namespace is given, the root namespace is assumed.",
            false);
        $options->registerOption(
            'output',
            "Optional, where to store the analytical data as csv eg. a filename.",
            'o', 'file');
        $options->registerOption(
            'cache',
            "Optional, returns from the cache if set",
            'c', false);

    }

    /**
     * The main entry
     * @param Options $options
     */
    protected function main(Options $options)
    {

        $namespaces = array_map('cleanID', $options->getArgs());
        if (!count($namespaces)) $namespaces = array(''); //import from top

        $output = $options->getOpt('output', '');
        //if ($output == '-') $output = 'php://stdout';
        $cache = $options->getOpt('cache', false);


        $this->process($namespaces, $output, $cache);


    }

    /**
     * @param $namespaces
     * @param $output
     * @param bool $cache
     * @param int $depth recursion depth. 0 for unlimited
     */
    private function process($namespaces, $output, $cache = false, $depth = 0)
    {
        global $conf;

        $fileHandle = null;
        if (!empty($output)) {
            $fileHandle = @fopen($output, 'w');
            if (!$fileHandle) $this->fatal("Failed to open $output");
        }

        // find pages
        $pages = array();
        foreach ($namespaces as $ns) {

            search(
                $pages,
                $conf['datadir'],
                'search_universal',
                array(
                    'depth' => $depth,
                    'listfiles' => true,
                    'listdirs' => false,
                    'pagesonly' => true,
                    'skipacl' => true,
                    'firsthead' => false,
                    'meta' => false,
                ),
                str_replace(':', '/', $ns)
            );

            // add the ns start page
            if ($ns && page_exists($ns)) {
                $pages[] = array(
                    'id' => $ns,
                    'ns' => getNS($ns),
                    'title' => p_get_first_heading($ns, false),
                    'size' => filesize(wikiFN($ns)),
                    'mtime' => filemtime(wikiFN($ns)),
                    'perm' => 16,
                    'type' => 'f',
                    'level' => 0,
                    'open' => 1,
                );
            }

        }


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
        while ($page = array_shift($pages)) {
            $id = $page['id'];

            // Run as admin to overcome the fact that
            // anonymous user cannot set all links and backlinnks
            global $USERINFO;
            $USERINFO['grps'] = array('admin');


            echo 'Processing the page ' . $id ."\n";

            $data = Analytics::getDataAsArray($id, $cache);
            if (!empty($fileHandle)) {
                $statistics = $data[Analytics::STATISTICS];
                $row = array(
                    'id' => $id,
                    'backlinks' => $statistics[Analytics::INTERNAL_BACKLINKS_COUNT],
                    'broken_links' => $statistics[Analytics::INTERNAL_LINKS_BROKEN_COUNT],
                    'changes' => $statistics[Analytics::EDITS_COUNT],
                    'chars' => $statistics[Analytics::CHARS_COUNT],
                    'external_links' => $statistics[Analytics::EXTERNAL_LINKS_COUNT],
                    'external_medias' => $statistics[Analytics::EXTERNAL_MEDIAS],
                    'h1' => $statistics[Analytics::HEADERS_COUNT]['h1'],
                    'h2' => $statistics[Analytics::HEADERS_COUNT]['h2'],
                    'h3' => $statistics[Analytics::HEADERS_COUNT]['h3'],
                    'h4' => $statistics[Analytics::HEADERS_COUNT]['h4'],
                    'h5' => $statistics[Analytics::HEADERS_COUNT]['h5'],
                    'internal_links' => $statistics[Analytics::INTERNAL_LINKS_COUNT],
                    'internal_medias' => $statistics[Analytics::INTERNAL_MEDIAS_COUNT],
                    'words' => $statistics[Analytics::WORDS_COUNT],
                    'low' => $data[Analytics::QUALITY]['low']
                );
                fwrite($fileHandle, implode(",", $row) . PHP_EOL);
            }
        }
        if (!empty($fileHandle)) {
            fclose($fileHandle);
        }

    }
}
