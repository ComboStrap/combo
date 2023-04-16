<?php

namespace ComboStrap;

/**
 * A fetcher that return a strings
 * (all ajax call should implements this interface)
 */
interface IFetcherString extends IFetcher
{

    public function getFetchString(): string;

}
