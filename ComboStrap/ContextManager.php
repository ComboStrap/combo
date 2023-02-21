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
 * @deprecated by {@link ExecutionContext::getContextData()}
 */
class ContextManager
{



    private ExecutionContext $executionContext;

    /**
     * @param array $defaultContextData
     */
    public function __construct(ExecutionContext $executionContext)
    {
        $this->executionContext = $executionContext;
    }


    /**
     * @return ContextManager - the global context manager
     * that is set for every run at the end of this file
     */
    public static function getOrCreate(): ContextManager
    {

        return ExecutionContext::getActualOrCreateFromEnv()
            ->getContextManager();


    }

    /**
     * @return array
     * @deprecated uses {@link ExecutionContext::getContextData instead}
     */
    public function getContextData(): array
    {
        return $this->executionContext->getContextData();
    }


    public function reset()
    {
        $this->contextData = null;
    }

    public function setContextArrayData(array $contextData)
    {
        $this->contextData = $contextData;
    }

    /**
     * @param string $name
     * @return mixed
     * @deprecated
     */
    public function getAttribute(string $name)
    {
        return $this->getContextData()[$name];
    }

    /**
     * @param $name
     * @param $value
     * @return void
     * @deprecated use {@link ExecutionContext::setContextData()} instead
     */
    public function setContextData($name, $value)
    {
        $this->contextData[$name] = $value;
    }


}
