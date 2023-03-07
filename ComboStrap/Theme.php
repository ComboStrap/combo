<?php

namespace ComboStrap;

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class Theme
{

    private string $stringTemplate;
    private Handlebars $fileHandlebars;
    private Handlebars $stringHandleBars;
    private array $model = [];

    /**
     * @param string $stringTemplate
     */
    public function __construct()
    {
        # Set the partials files
        try {
            $partialsDir = WikiPath::createComboResource(":layout:")->toLocalPath();

            $partialsLoader = new FilesystemLoader($partialsDir, ["extension" => "html"]);
            $this->fileHandlebars = new Handlebars([
                "loader" => $partialsLoader,
                "partials_loader" => $partialsLoader
            ]);
            $this->stringHandleBars = new Handlebars();
            $this->stringHandleBars->addHelper("share",
                function ($template, $context, $args, $source) {
                    $attributes = $context->get($args);
                    $knownType = ShareTag::getKnownTypes();
                    $tagAttributes = TagAttributes::createFromTagMatch("<share $attributes/>", [], $knownType);
                    return ShareTag::render($tagAttributes, DOKU_LEXER_SPECIAL);
                }
            );
            $this->stringHandleBars->addHelper("runner",
                function ($template, $context, $args, $source) {
                    return "runner";
                }
            );
        } catch (\Exception $e) {
            throw ExceptionRuntimeInternal::withMessageAndError("Error while initiating handlebars", $e);
        }
    }


    public static function create(): Theme
    {
        return new Theme();
    }

    public function render(): string
    {

        if (isset($this->stringTemplate)) {
            return $this->stringHandleBars->render($this->stringTemplate, $this->model);
        } else {
            $template = "main";
            return $this->fileHandlebars->render($template, $this->model);
        }

    }

    public function setTemplateAsString(string $template): Theme
    {
        $this->stringTemplate = $template;
        return $this;
    }

    public function setModel(array $model): Theme
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax - if the xml is not html compliant
     */
    public function renderAsDom(): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->render());
    }
}
