<?php

namespace ComboStrap;

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class Theme
{


    private Handlebars $handleBars;


    /**
     *
     */
    public function __construct(Handlebars $handlebars)
    {

        $this->handleBars = $handlebars;

    }


    public static function withString(): Theme
    {
        $handlebars = PageTemplateEngine::createForString()->getHandlebars();
        return new Theme($handlebars);
    }

    public static function withTheme(string $name): Theme
    {
        $handlebars = PageTemplateEngine::createForTheme($name)->getHandlebars();
        return new Theme($handlebars);
    }

    public static function withDefaultTheme(): Theme
    {
        return self::withTheme("default");
    }

    /**
     * @param string $template - a template file name if a theme is used otherwise a template string
     * @param $model - the data model
     * @return string
     */
    public function render(string $template, array $model = []): string
    {

        return $this->handleBars->render($template, $model);

    }

    /**
     * @throws ExceptionBadSyntax - if the xml is not html compliant
     */
    public function renderAsDom(string $name, array $model = []): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->render($name, $model));
    }
}
