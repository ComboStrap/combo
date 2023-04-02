<?php

namespace ComboStrap\Template;

use ComboStrap\TemplateEngine;

class TemplateForComponent
{



    private string $templateName;

    public function __construct(string $templateName)
    {
        $this->templateName = $templateName;
    }

    public static function create(string $templateName): TemplateForComponent
    {
        return new TemplateForComponent($templateName);
    }

    public function render(array $data): string
    {
        return TemplateEngine::createFromContext()
            ->renderWebComponent($this->templateName,$data);
    }


}
