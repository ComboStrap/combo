<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Field\Aliases;
use ComboStrap\Meta\Field\FacebookImage;
use ComboStrap\Meta\Field\FeaturedRasterImage;
use ComboStrap\Meta\Field\FeaturedSvgImage;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Field\PageImages;
use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Meta\Field\Region;
use ComboStrap\Meta\Field\TwitterImage;
use ComboStrap\Meta\Form\FormMeta;
use ComboStrap\Meta\Form\FormMetaTab;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

class MetaManagerForm
{

    public const TAB_PAGE_VALUE = "page";
    public const TAB_TYPE_VALUE = "type";
    public const TAB_CACHE_VALUE = "cache";
    public const TAB_REDIRECTION_VALUE = "redirection";
    public const TAB_LANGUAGE_VALUE = "language";
    public const TAB_INTEGRATION_VALUE = "integration";
    public const TAB_QUALITY_VALUE = "quality";
    public const TAB_IMAGE_VALUE = "image";
    private MarkupPath $page;

    private const FORM_METADATA_LIST = [ResourceName::PROPERTY_NAME,
        PageTitle::PROPERTY_NAME,
        PageH1::PROPERTY_NAME,
        PageDescription::PROPERTY_NAME,
        PageKeywords::PROPERTY_NAME,
        PagePath::PROPERTY_NAME,
        Canonical::PROPERTY_NAME,
        Slug::PROPERTY_NAME,
        PageUrlPath::PROPERTY_NAME,
        PageTemplateName::PROPERTY_NAME,
        ModificationDate::PROPERTY_NAME,
        PageCreationDate::PROPERTY_NAME,
        FeaturedRasterImage::PROPERTY_NAME,
        FeaturedSvgImage::PROPERTY_NAME,
        TwitterImage::PROPERTY_NAME,
        FacebookImage::PROPERTY_NAME,
        Aliases::PROPERTY_NAME,
        PageType::PROPERTY_NAME,
        PagePublicationDate::PROPERTY_NAME,
        StartDate::PROPERTY_NAME,
        EndDate::PROPERTY_NAME,
        LdJson::PROPERTY_NAME,
        LowQualityPageOverwrite::PROPERTY_NAME,
        QualityDynamicMonitoringOverwrite::PROPERTY_NAME,
        Locale::PROPERTY_NAME,
        Lang::PROPERTY_NAME,
        Region::PROPERTY_NAME,
        ReplicationDate::PROPERTY_NAME,
        PageId::PROPERTY_NAME,
        CacheExpirationFrequency::PROPERTY_NAME,
        CacheExpirationDate::PROPERTY_NAME,
        PageLevel::PROPERTY_NAME
    ];

    /**
     * @var MetadataFormDataStore
     */
    private $targetFormDataStore;

    /**
     * MetaManager constructor.
     */
    public function __construct($page)
    {
        $this->page = $page;
        $this->targetFormDataStore = MetadataFormDataStore::getOrCreateFromResource($page);
    }

    public static function createForPage(MarkupPath $page): MetaManagerForm
    {
        return new MetaManagerForm($page);
    }

    /**
     * @return FormMeta
     */
    function toFormMeta(): FormMeta
    {

        /**
         * Case when the page was changed externally
         * with a new frontmatter
         * The frontmatter data should be first replicated into the metadata file
         */
        $fetcherMarkup = $this->page->getInstructionsDocument();
        $fetcherMarkup->getInstructions();


        /**
         * Creation
         */
        $name = $this->page->getPathObject()->toAbsoluteString();
        $formMeta = FormMeta::create($name)
            ->setType(FormMeta::FORM_NAV_TABS_TYPE);


        /**
         * The manager
         */
        $dokuwikiFsStore = MetadataDokuWikiStore::getOrCreateFromResource($this->page);
        foreach (self::FORM_METADATA_LIST as $formsMetaDatum) {

            try {
                $metadata = Metadata::getForName($formsMetaDatum);
            } catch (ExceptionNotFound $e) {
                LogUtility::msg("The metadata ($formsMetaDatum} was not found");
                continue;
            }
            $metadata
                ->setResource($this->page)
                ->setReadStore($dokuwikiFsStore)
                ->buildFromReadStore()
                ->setWriteStore($this->targetFormDataStore);
            $formMeta->addFormFieldFromMetadata($metadata);
        }


        /**
         * Tabs (for whatever reason, javascript keep the order of the properties
         * and therefore the order of the tabs)
         */
        $formMeta
            ->addTab(
                FormMetaTab::create(self::TAB_PAGE_VALUE)
                    ->setLabel("Page")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_TYPE_VALUE)
                    ->setLabel("Page Type")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_REDIRECTION_VALUE)
                    ->setLabel("Redirection")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_IMAGE_VALUE)
                    ->setLabel("Images")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_QUALITY_VALUE)
                    ->setLabel("Quality")
                    ->setWidthLabel(6)
                    ->setWidthField(6)
            )->addTab(
                FormMetaTab::create(self::TAB_LANGUAGE_VALUE)
                    ->setLabel("Language")
                    ->setWidthLabel(2)
                    ->setWidthField(10)
            )->addTab(
                FormMetaTab::create(self::TAB_INTEGRATION_VALUE)
                    ->setLabel("Integration")
                    ->setWidthLabel(4)
                    ->setWidthField(8)
            )->addTab(
                FormMetaTab::create(self::TAB_CACHE_VALUE)
                    ->setLabel("Cache")
                    ->setWidthLabel(6)
                    ->setWidthField(6)
            );


        return $formMeta;

    }


    public function toFormData(): array
    {
        return $this->toFormMeta()->toFormData();
    }


}
