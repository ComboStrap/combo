<?php

namespace ComboStrap;

/**
 * To make a difference between a value that was not found
 * and a value that was not created because the resource does not exists
 *
 * Example: {@link PageId} {@link ModificationDate} should be found if the resource exists
 */
class ExceptionNotExists extends ExceptionCompile
{

}
