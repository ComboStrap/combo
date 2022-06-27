<?php

namespace ComboStrap;

/**
 * Fetcher that create their output from a source file
 */
interface FetcherSource extends Fetcher
{

    public function getOriginalPath(): DokuPath;

}
