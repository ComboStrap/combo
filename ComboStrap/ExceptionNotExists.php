<?php

namespace ComboStrap;

/**
 * To make a difference between a value that was not found
 * and a value that was not created because the resource does not exists
 *
 * Generally, if you have not found in a function, you should throw a not exist
 *
 * It permits to control that the {@link ExceptionNotFound} exception does not
 * come from a lower/called function
 *
 * A {@link ExceptionNotExists} should be not be thrown with a {@Link ExceptionNotFound}
 * on the same method
 *
 * Example: {@link PageId} {@link ModificationDate} should be found if the resource exists
 */
class ExceptionNotExists extends ExceptionCompile
{

}
