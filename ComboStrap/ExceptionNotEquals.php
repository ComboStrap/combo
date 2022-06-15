<?php

namespace ComboStrap;

use Throwable;

class ExceptionNotEquals extends ExceptionCompile
{

    /**
     * @var string|array
     */
    private $left;
    /**
     * @var string|array
     */
    private $right;

    public function __construct($message = "", $canonical = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $canonical, $code, $previous);
    }

    public static function create(string $message, $left, $right): ExceptionNotEquals
    {
        return (new ExceptionNotEquals($message))
            ->setLeft($left)
            ->setRight($right);
    }

    private function setLeft($left): ExceptionNotEquals
    {
        $this->left = $left;
        return $this;
    }

    private function setRight($right): ExceptionNotEquals
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return array|string
     */
    public function getRight()
    {
        return $this->right;
    }



}
