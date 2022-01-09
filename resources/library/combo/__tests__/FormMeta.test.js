/**
 * @jest-environment jsdom
 * https://jestjs.io/docs/configuration#testenvironment-string
 */


import FormMeta from "../FormMeta";
import Xml from "../Xml";

/**
 * Test tabs and field children
 */
test('Json to Form Object', () => {

    let formId = "formId";
    let firstFieldName = "first";
    let firstFieldLabel = "Youpla";
    let firstFieldTab = "TheTab";
    let firstFieldType = "text";
    let firstFieldMutable = true;
    let firstFieldUrl = "https:\/\/combostrap.com\/first";
    let firstFieldDescription = "The big youpla";
    let firstFieldDefaultValue = "Meta Manager";
    let firstFieldValue = 1;
    let firstFieldDomainValues = [
        "holy",
        "median",
        "landing"
    ];
    let secondFieldName = "second";
    let firstFieldWidth = 8;
    let secondFieldValue = [
        null,
        null,
        null,
        null,
        null
    ];
    let secondFieldDefaultValue = [
        ":illustration.png",
        null,
        null,
        null,
        null
    ];
    let firstTabLabel = "label first tab";
    let firstTabWidthField = 8;
    let firstTabWidthLabel = 4;
    let thirdFieldName = "third";
    let thirdValueDateTime = "3 value";
    let thirdDefaultValue = "3 default";
    let formMetadata = {
        "fields": {
            "first": {
                "name": firstFieldName,
                "label": firstFieldLabel,
                "tab": firstFieldTab,
                "type": firstFieldType,
                "mutable": firstFieldMutable,
                "url": firstFieldUrl,
                "description": firstFieldDescription,
                "default": firstFieldDefaultValue,
                "value": firstFieldValue,
                "domain-values": firstFieldDomainValues,
                "width": firstFieldWidth,
            },
            "second": {
                "name": secondFieldName,
                "value": secondFieldValue,
                "default": secondFieldDefaultValue,
                "children": {
                    "third": {
                        "name": thirdFieldName,
                        "value": thirdValueDateTime,
                        "default": thirdDefaultValue
                    }
                }
            }
        },
        "tabs": {
            firstFieldTab:
                {
                    "name": firstFieldTab,
                    "label": firstTabLabel,
                    "width-field": firstTabWidthField,
                    "width-label": firstTabWidthLabel
                }
        }
    };
    let formMeta = FormMeta.createFromJson(formId, formMetadata);
    expect(formMeta.getId()).toBe(formId);
    let fields = formMeta.getFields();
    expect(fields.length).toBe(2);

    /**
     * @type {FormMetaField}
     */
    let field = fields[0];
    expect(field.getName()).toBe(firstFieldName);
    expect(field.getLabel()).toBe(firstFieldLabel);
    expect(field.getTab()).toBe(firstFieldTab);
    expect(field.getType()).toBe(firstFieldType);
    expect(field.isMutable()).toBe(firstFieldMutable);
    expect(field.getUrl()).toBe(firstFieldUrl);
    expect(field.getDescription()).toBe(firstFieldDescription);
    expect(field.getDescription()).toBe(firstFieldDescription);
    expect(field.getDefaultValue()).toBe(firstFieldDefaultValue);
    expect(field.getValue()).toBe(firstFieldValue);
    expect(field.getDomainValues()).toBe(firstFieldDomainValues);
    expect(field.getControlWidth()).toBe(firstFieldWidth);

    let field2 = fields[1];
    expect(field2.getName()).toBe(secondFieldName);
    expect(field2.getValues()).toEqual(secondFieldValue);
    expect(field2.getDefaultValues()).toEqual(secondFieldDefaultValue);

    /**
     * Tab
     */
    let tabs = formMeta.getTabs()
    expect(tabs.length).toBe(2); // a default tab was created for the second field
    let firstTab = tabs[0];
    expect(firstTab.getName()).toBe(firstFieldTab);
    expect(firstTab.getLabel()).toBe(firstTabLabel);
    expect(firstTab.getLabelWidth()).toBe(firstTabWidthLabel);
    expect(firstTab.getFieldWidth()).toBe(firstTabWidthField);

    // The default tab
    let secondTab = tabs[1];
    expect(secondTab.getName()).toBe("unknown");
    expect(secondTab.getLabel()).toBe("unknown");
    expect(secondTab.getLabelWidth()).toBe(3);
    expect(secondTab.getFieldWidth()).toBe(9);

    /**
     * To html
     * This test is a sort of snapshot test
     * where the snapshot is the form html in the heredoc
     */
    let htmlForm = formMeta.toHtmlElement("formId")
    /**
     * {@link Xml.createFromHtmlString} and not from {@link Xml.createFromXmlString}
     * because the form has an input element and therefore does not pass XML
     * because an input element does not close
     * @type {string}
     */
    let actual = Xml.createFromHtmlString(htmlForm.outerHTML).normalize();
    let expected = `<form id="formId">
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <button class="nav-link active" id="formId-tab-nav-TheTab" type="button" role="tab" aria-selected="true" aria-controls="formId-tab-pane-TheTab" data-bs-toggle="tab" data-bs-target="#formId-tab-pane-TheTab">
      label first tab
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link " id="formId-tab-nav-unknown" type="button" role="tab" aria-selected="false" aria-controls="formId-tab-pane-unknown" data-bs-toggle="tab" data-bs-target="#formId-tab-pane-unknown">
      unknown
      </button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane active" id="formId-tab-pane-TheTab" role="tabpanel" aria-labelledby="formId-tab-nav-TheTab">
      <div class="row mb-3 text-center">
        <div class="col-sm-12">
          <label for="formId-control-1" class="col-form-label">
            <a href="https://combostrap.com/first" title="The big youpla" data-bs-toggle="tooltip" style="text-decoration:none" target="_blank">
            Youpla
            </a>
          </label>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-sm-12">
          <select class="form-select" aria-label="Youpla" name="first" id="formId-control-1">
            <option value>
            Default (Meta Manager)
            </option>
            <option value="holy">
            holy
            </option>
            <option value="median">
            median
            </option>
            <option value="landing">
            landing
            </option>
          </select>
        </div>
      </div>
    </div>
    <div class="tab-pane " id="formId-tab-pane-unknown" role="tabpanel" aria-labelledby="formId-tab-nav-unknown">
      <div class="row mb-3 text-center">
      Second
      </div>
      <div class="row mb-3">
        <div class="col-sm-12 text-center">
        Third
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-sm-12">
          <input type="text" name="third" class="form-control" id="formId-control-2" placeholder="3 default" value="3 value">
        </div>
      </div>
    </div>
  </div>
</form>`;
    expect(actual).toEqual(expected);

});
