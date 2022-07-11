<?php

namespace ComboStrap;

/**
 * Fetcher that create their output from a source file
 */
interface IFetcherSource extends IFetcher
{

    public function getOriginalPath(): WikiPath;

}
