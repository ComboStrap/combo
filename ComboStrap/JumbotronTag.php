<?php

namespace ComboStrap;


use Doku_Handler;


class JumbotronTag
{


    public const TAG = 'jumbotron';


    public static function renderEnterXhtml(TagAttributes $tagAttributes): string
    {

        $bsVersion = Bootstrap::getBootStrapMajorVersion();
        switch ($bsVersion) {
            case Bootstrap::BootStrapFourMajorVersion:
                $jumbotronClass = "jumbotron";
                break;
            default:
            case Bootstrap::BootStrapFiveMajorVersion:
                $jumbotronClass = "rounded";
        }

        return $tagAttributes
            ->addClassName($jumbotronClass)
            ->toHtmlEnterTag("div");

    }

    public static function renderExitHtml(): string
    {
        return '</div>';
    }

    public static function getDefault(): array
    {
        return [
            Hero::ATTRIBUTE => "md",
            BackgroundAttribute::BACKGROUND_COLOR => "#e9ecef",
            Spacing::SPACING_ATTRIBUTE => "m-2"
        ];
    }
}

