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

namespace ComboStrap;


/**
 * @package ComboStrap
 *
 * Context Class
 */
class ContextManager
{


    /**
     * @var ContextManager array that contains one element (one {@link ContextManager} scoped to the requested id
     */
    private static $globalContext;

    /**
     * @var array
     */
    private $contextData;
    /**
     * @var array
     */
    private $defaultContextData;

    /**
     * @param array $defaultContextData
     */
    public function __construct(array $defaultContextData = [])
    {
        $this->defaultContextData = $defaultContextData;
    }


    /**
     * @return ContextManager - the global context manager
     * that is set for every run at the end of this file
     */
    public static function getOrCreate(): ContextManager
    {

        try {
            $wikiRequestedPath = WikiPath::createRequestedPagePathFromRequest();
        } catch (ExceptionNotFound $e) {
            if (!PluginUtility::isTest()) {
                LogUtility::error("The requested Id could not be found, the context may not be scoped properly");
            }
            $wikiRequestedPath = WikiPath::createMarkupPathFromId("test_dynamic_context_execution");
        }

        $wikiId = $wikiRequestedPath->getWikiId();
        $context = self::$globalContext[$wikiId];
        if ($context === null) {
            self::$globalContext = null; // delete old snippet manager for other request

            $defaultContextData = MarkupPath::createPageFromPathObject($wikiRequestedPath)
                ->getMetadataForRendering();
            $context = new ContextManager($defaultContextData);
            self::$globalContext[$wikiId] = $context;
        }
        return $context;
    }

    public function getContextData(): array
    {
        if ($this->contextData === null) {
            return $this->defaultContextData;
        }
        return $this->contextData;
    }


    public function reset()
    {
        $this->contextData = null;
    }

    public function setContextArrayData(array $contextData)
    {
        $this->contextData = $contextData;
    }

    public function getAttribute(string $name)
    {
        return $this->getContextData()[$name];
    }

    public function setContextData($name, $value)
    {
        $this->contextData[$name] = $value;
    }


}
