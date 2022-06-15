<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


/**
 * Class InternalMedia
 * Represent a HTML markup link
 *
 *
 * @package ComboStrap
 *
 *
 *
 * This is a link to a media (pdf, image, ...).
 * (ie img, svg, a)
 */
abstract class MediaLink
{


    const CANONICAL = "image";


    /**
     * @deprecated 2021-06-12
     */
    const LINK_PATTERN = "{{\s*([^|\s]*)\s*\|?.*}}";
    protected MediaMarkup $mediaMarkup;


    public function __construct(MediaMarkup $mediaMarkup)
    {
        $this->mediaMarkup = $mediaMarkup;
    }


    /**
     * @param MediaMarkup $mediaMarkup
     * @return RasterImageLink|SvgImageLink|ThirdMediaLink|MediaLink
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createFromMediaMarkup(MediaMarkup $mediaMarkup)
    {

        /**
         * Processing
         */
        try {
            $mime = FileSystems::getMime($mediaMarkup->getPath());
        } catch (ExceptionNotFound $e) {
            // no mime
            LogUtility::error($e->getMessage());
            return new ThirdMediaLink($mediaMarkup);
        }
        switch ($mime->toString()) {
            case Mime::SVG:
                return new SvgImageLink($mediaMarkup);
            default:
                if (!$mime->isImage()) {
                    return new ThirdMediaLink($mediaMarkup);
                } else {
                    return new RasterImageLink($mediaMarkup);
                }
        }


    }




    /**
     * @return string - the HTML of the image
     */
    public abstract function renderMediaTag(): string;


    public function getFetchUrl(): Url{
        return $this->mediaMarkup->getFetchUrl();
    }



}
