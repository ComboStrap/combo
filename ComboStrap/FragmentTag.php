<?php

namespace ComboStrap;

use syntax_plugin_combo_variable;


class FragmentTag
{

    public const LOGICAL_TAG = self::FRAGMENT_TAG;
    public const CALLSTACK = "callstack";
    public const TEMPLATE_TAG = "template";
    public const CANONICAL = syntax_plugin_combo_variable::CANONICAL;
    public const FRAGMENT_TAG = "fragment";
    public const TAGS = [FragmentTag::FRAGMENT_TAG, FragmentTag::TEMPLATE_TAG];
}
