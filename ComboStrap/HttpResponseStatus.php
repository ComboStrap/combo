<?php

namespace ComboStrap;

class HttpResponseStatus
{

    public const NOT_MODIFIED = 304;
    public const NOT_AUTHORIZED = 401;
    public const ALL_GOOD = 200;
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    public const INTERNAL_ERROR = 500;
    public const NOT_FOUND = 404;
    public const DOES_NOT_EXIST = 404;
    public const BAD_REQUEST = 400;

    // after a post, to redirect to a response page
    public const FOUND_REDIRECT = 302;

    public const PERMANENT_REDIRECT = 301;

    // 303 example: Used to redirect for tracking analysis
    // For instance, after the user click on a tracking link (in an email or in on page)
    public const SEE_OTHER_REDIRECT = 303;

}
