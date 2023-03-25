<?php

namespace ComboStrap;

use Doku_Form;
use dokuwiki\Form\Form;
use dokuwiki\Form\InputElement;

class IdentityFormsHelper
{

    const CANONICAL = "identity-forms-helper";

    public static function toBoostrapInputElements(Form $form, string $formName)
    {
        for ($i = 0; $i < $form->elementCount(); $i++) {
            $inputElement = $form->getElementAt($i);
            if ($inputElement instanceof InputElement) {
                $i = self::toBootStrapInputElementAndGetNewLoopingPosition($form, $i, $formName);
            }
        }
    }

    /**
     * Tansfrom the Dokuwiki {@link InputElement} into a Boostrap layout
     * @param Form $form - the form
     * @param int $elementPosition - the position of the element (that's how works {@link Form}
     * @param string $formName - the form name to create unique id (as the profile page has 2 forms)
     * @return int - the new position that should be used in a loop (the remove, add action reset the whole array indexes)
     */
    public static function toBootStrapInputElementAndGetNewLoopingPosition(Form $form, int $elementPosition, string $formName): int
    {
        $inputElement = $form->getElementAt($elementPosition);
        if (!($inputElement instanceof InputElement)) {
            LogUtility::internalError("The element should be an input element");
            return $elementPosition;
        }
        $inputType = $inputElement->getType();
        $inputName = $inputElement->attr("name");
        $labelObject = $inputElement->getLabel();
        $label = "";
        if ($labelObject !== null) {
            $label = $labelObject->val();
        }
        $inputId = $inputElement->attr("id");
        if (empty($inputId)) {
            $inputId = "user__$formName-input-$elementPosition";
            $inputElement->id($inputId);
        }
        $placeholder = $inputElement->attr("placeholder");
        if (empty($placeholder) && !empty($label)) {
            $inputElement->attr("placeholder", $label);
        }
        $newInputField = new InputElement($inputType, $inputName);
        foreach ($inputElement->attrs() as $keyAttr => $valueAttr) {
            $newInputField->attr($keyAttr, $valueAttr);
        }
        $newInputField->addClass("form-control");
        $form->replaceElement($newInputField, $elementPosition);
        $form->addHTML('<div class="form-control-row">', $elementPosition);
        $form->addHTML("<label for=\"$inputId\" class=\"form-label\">$label</label>", $elementPosition + 1);
        $form->addHTML('</div>', $elementPosition + 3);
        return $elementPosition + 3;

    }

    /**
     * @param Doku_Form|Form $form
     * @param string $classPrefix
     * @param bool $includeLogo
     * @return string
     */
    public static function getHeaderHTML($form, string $classPrefix, bool $includeLogo = true): string
    {
        $class = get_class($form);
        switch ($class) {
            case Doku_Form::class:
                /**
                 * Old one
                 * @var Doku_Form $form
                 */
                $legend = $form->_content[0]["_legend"];
                if (!isset($legend)) {
                    return "";
                }

                $title = $legend;
                break;
            case Form::class;
                /**
                 * New One
                 * @var Form $form
                 */
                $pos = $form->findPositionByType("fieldsetopen");
                if ($pos === false) {
                    return "";
                }

                $title = $form->getElementAt($pos)->val();
                break;
            default:
                LogUtility::msg("Internal Error: Unknown form class " . $class);
                return "";
        }

        /**
         * Logo
         */
        $logoHtmlImgTag = "";
        if (
            $includeLogo === true
        ) {
            try {
                $logoPath = self::getLogoPath();
            } catch (ExceptionNotFound $e) {
                $logoPath = WikiPath::createComboResource(":images:home.svg");
            }
            $logoHtmlImgTag = self::getLogoHtml($logoPath);
        }
        /**
         * Don't use `header` in place of
         * div because this is a HTML5 tag
         *
         * On php 5.6, the php test library method {@link \phpQueryObject::htmlOuter()}
         * add the below meta tag
         * <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
         *
         */
        return <<<EOF
<div class="$classPrefix-header">
    $logoHtmlImgTag
    <h1>$title</h1>
</div>
EOF;
    }

    public static function addPrimaryColorCssRuleIfSet(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }
        $primaryColor = Site::getPrimaryColor();
        if ($primaryColor !== null) {
            $identityClass = Identity::FORM_IDENTITY_CLASS;
            $cssFormControl = BrandColors::getCssFormControlFocusColor($primaryColor);
            $content .= <<<EOF
.$identityClass button[type="submit"]{
   background-color: {$primaryColor->toCssValue()};
   border-color: {$primaryColor->toCssValue()};
}
$cssFormControl
EOF;
        }
        return $content;
    }

    public static function getHtmlStyleTag(string $componentId): string
    {
        $loginCss = Snippet::createCssSnippetFromComponentId($componentId);
        try {
            $content = $loginCss->getInternalInlineAndFileContent();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The style content should be not null", Identity::CANONICAL);
            $content = "";
        }
        $content = IdentityFormsHelper::addPrimaryColorCssRuleIfSet($content);
        $class = $loginCss->getClass();
        return <<<EOF
<style class="$class">
$content
</style>
EOF;

    }

    public static function addIdentityClass(&$class, string $formClass)
    {

        $formClass = Identity::FORM_IDENTITY_CLASS . " " . $formClass;
        if (isset($class)) {
            $class .= " " . $formClass;
        } else {
            $class = $formClass;
        }

    }

    public static function deleteFieldSetAndBrFromForm(Form $form)
    {
        foreach (Identity::FIELD_SET_TO_DELETE as $type) {
            $field = $form->findPositionByType($type);
            if ($field !== false) {
                $form->removeElement($field);
            }
        }

        for ($i = 0; $i < $form->elementCount(); $i++) {
            $fieldBr = $form->getElementAt($i);
            if (trim($fieldBr->val()) === "<br>") {
                $form->removeElement($i);
                // removing the element, rearrange the array and shift the array index of minus 1
                // to delete two br one after the other, we need to readjust the counter
                $i--;
            }
        }
    }

    public static function toBootStrapSubmitButton(Form $form)
    {
        $submitButtonPosition = $form->findPositionByAttribute("type", "submit");
        if ($submitButtonPosition === false) {
            LogUtility::msg("Internal error: No submit button found");
            return;
        }
        $form->getElementAt($submitButtonPosition)
            ->addClass("btn")
            ->addClass("btn-primary");
    }

    public static function toBootstrapResetButton(Form $form)
    {
        $resetButtonPosition = $form->findPositionByAttribute("type", "reset");
        if ($resetButtonPosition === false) {
            LogUtility::msg("Internal error: No submit button found");
            return;
        }
        $form->getElementAt($resetButtonPosition)
            ->addClass("btn")
            ->addClass("btn-secondary");
    }

    /**
     */
    public static function getLogoHtml(WikiPath $logoImagePath): string
    {

        $tagAttributes = TagAttributes::createEmpty("identity")
            ->addClassName("logo");

        try {
            $imageFetcher = IFetcherLocalImage::createImageFetchFromPath($logoImagePath)
                ->setRequestedHeight(72)
                ->setRequestedWidth(72);

            if ($imageFetcher instanceof FetcherSvg) {
                $imageFetcher->setRequestedType(FetcherSvg::ICON_TYPE);
                $primaryColor = Site::getPrimaryColor();
                if ($primaryColor !== null) {
                    $imageFetcher->setRequestedColor($primaryColor);
                }
            }
            $brand = Brand::create(Brand::CURRENT_BRAND);

            $mediaMarkup = MediaMarkup::createFromFetcher($imageFetcher)
                ->setLazyLoad(false)
                ->setLinking(MediaMarkup::LINKING_NOLINK_VALUE)
                ->buildFromTagAttributes($tagAttributes)
                ->toHtml();
            return <<<EOF
<a href="{$brand->getBrandUrl()}" title="{$brand->getTitle()}">$mediaMarkup</a>
EOF;
        } catch (\Exception $e) {
            LogUtility::error("Error while creating the logo html", self::CANONICAL, $e);
            return "";
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getLogoPath(): WikiPath
    {
        $logoImagesPath = Site::getLogoImagesAsPath();
        foreach ($logoImagesPath as $logoImagePath) {

            if (!Identity::isReader($logoImagePath->getWikiId())) {
                continue;
            }
            return $logoImagePath;

        }
        throw new ExceptionNotFound("No logo image could be found");
    }
}
