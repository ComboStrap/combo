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
        $id = PluginUtility::getRequestedWikiId();
        if ($id === null) {
            if (PluginUtility::isTest()) {
                $id = "test_dynamic_context_execution";
            } else {
                LogUtility::msg("The requested Id could not be found, the context may not be scoped properly");
            }
        }

        $context = self::$globalContext[$id];
        if ($context === null) {
            self::$globalContext = null; // delete old snippet manager for other request
            $defaultContextData = PageFragment::createPageFromRequestedPage()->getMetadataForRendering();
            $context = new ContextManager($defaultContextData);
            self::$globalContext[$id] = $context;
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
