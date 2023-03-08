<?php

namespace ComboStrap;

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class ThemeBuilder
{


    private string $partialsDirectory;



    public function setThemeName(string $themeName): ThemeBuilder
    {
        try {
            $this->partialsDirectory = WikiPath::createComboResource(":theme:$themeName")->toLocalPath()->toAbsoluteString();
        } catch (ExceptionCast $e) {
            // should not happen as combo resource is a known directory
        }
        return $this;
    }


    public function build(): Theme
    {

        try {


            if (isset($this->partialsDirectory)) {
                /**
                 * Handlebars Files
                 */
                $partialsLoader = new FilesystemLoader($this->partialsDirectory, ["extension" => "html"]);
                $handleBars = new Handlebars([
                    "loader" => $partialsLoader,
                    "partials_loader" => $partialsLoader
                ]);
            } else {

                /**
                 * Handlebars String
                 */
                $handleBars = new Handlebars();

            }


            $handleBars->addHelper("share",
                function ($template, $context, $args, $source) {
                    $attributes = $context->get($args);
                    $knownType = ShareTag::getKnownTypes();
                    $tagAttributes = TagAttributes::createFromTagMatch("<share $attributes/>", [], $knownType);
                    return ShareTag::render($tagAttributes, DOKU_LEXER_SPECIAL);
                }
            );
            $handleBars->addHelper("echo",
                function ($template, $context, $args, $source) {
                    return "echo";
                }
            );
            return new Theme($handleBars);
        } catch (\Exception $e) {
            throw ExceptionRuntimeInternal::withMessageAndError("Error while initiating handlebars", $e);
        }
    }

}
