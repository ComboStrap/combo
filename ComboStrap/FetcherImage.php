<?php

namespace ComboStrap;

/**
 * An interface on top of the {@link Fetcher} to be able to:
 *   * store common constant
 *   * return the {@link FetcherImage Type} on set method such as {@link FetcherTraitImage::setRequestedAspectRatio()}
 *
 * TODO: made it abstract and merge with {@link FetcherTraitImage}
 */
interface FetcherImage extends Fetcher
{

    const TOK = "tok";
    const CANONICAL_IMAGE = "image";

    function setRequestedHeight(int $height): FetcherImage;
    function setRequestedWidth(int $width): FetcherImage;
    function setRequestedAspectRatio(string $requestedRatio): FetcherImage;



}
