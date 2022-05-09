<?php

namespace ComboStrap;


class TreeSystem
{

    public static function getDescendantCount($treeNode): int
    {
        $counterClass = new class {

            private $counter = -1;

            public function increment()
            {
                $this->counter++;
            }

            public function getCounter(): int
            {
                return $this->counter;
            }

        };
        TreeVisit::visit($treeNode, array($counterClass, 'increment'));
        return $counterClass->getCounter();
    }

    public static function print(TreeNode $tree)
    {
        $printRecursively = function (TreeNode $treeNode, int $level) {
            for ($i = 0; $i < $level - 1; $i++) {
                echo "  ";
            }
            echo "- $treeNode\n";
        };
        TreeVisit::visit($tree, $printRecursively);
    }
}
