<?php

namespace ComboStrap;

/**
 * An interface on top of the {@link FetcherTraitImage}
 * to be able to:
 *   * store common constant
 *   * return it on set method such as {@link FetcherTraitImage::setRequestedAspectRatio()}
 */
interface FetcherImage
{

    const TOK = "tok";
    const CANONICAL_IMAGE = "image";

}
