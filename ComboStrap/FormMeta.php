<?php


namespace ComboStrap;
/**
 * Class FormMeta
 * @package ComboStrap
 *
 * Represents form metadata sends via an ajx request
 *
 * It makes sure that the data send
 * is coherent
 */
class FormMeta
{
    const FORM_TYPES = [self::FORM_NAV_TABS_TYPE, self::FORM_LIST_GROUP_TYPE];
    const FORM_NAV_TABS_TYPE = "nav-tabs";
    const FORM_LIST_GROUP_TYPE = "list-group";

    private $name;

    /**
     * @var FormMetaField[]
     */
    private $fields;
    /**
     * @var FormMetaTab[]|string[]
     */
    private $tabs;
    /**
     * @var string
     */
    private $type;

    public function __construct($name)
    {
        $this->name = $name;
    }


    public static function create($name): FormMeta
    {
        return new FormMeta($name);
    }

    public function addField(FormMetaField $formField): FormMeta
    {
        $this->fields[] = $formField;
        $tab = $formField->getTab();
        if (!empty($tab) && !isset($this->tabs[$tab])) {
            $this->tabs[$tab] = $tab;
        }
        return $this;
    }

    public function addTab(FormMetaTab $tab): FormMeta
    {
        $this->tabs[$tab->getName()] = $tab;
        return $this;
    }

    public function toAssociativeArray(): array
    {

        $fieldsArray = [];
        foreach ($this->fields as $element) {
            /**
             * The order is kept even if we add a key
             */
            $fieldsArray[$element->getName()] = $element->toAssociativeArray();
        }
        $tabs = [];
        foreach ($this->tabs as $element) {
            if (!($element instanceof FormMetaTab)) {
                $tab = FormMetaTab::create($element);
            } else {
                $tab = $element;
            }
            /**
             * The order is kept even if we add a key
             */
            $tabs[$tab->getName()] = $tab->toAssociativeArray();
        }
        return [
            "fields" => $fieldsArray,
            "tabs" => $tabs
        ];
    }

    /**
     * ie nav-tabs versus list-group:
     * https://getbootstrap.com/docs/5.0/components/list-group/#javascript-behavior
     *
     * @param string $type
     * @return FormMeta
     */
    public function setType(string $type): FormMeta
    {
        if (!in_array($type, self::FORM_TYPES)) {
            LogUtility::msg("The form type ($type) is unknown");
            return $this;
        }
        $this->type = $type;
        return $this;
    }
}
