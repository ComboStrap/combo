<?php

namespace ComboStrap;


class TreeVisit
{

    public static function visit(TreeNode $tree, Callable $function, int $level = 0)
    {
        call_user_func($function, $tree, $level);
        if ($tree->hasChildren()) {
            $childLevel = $level + 1;
            foreach ($tree->getChildren() as $child) {
                self::visit($child, $function, $childLevel);
            }
        }

    }

}
