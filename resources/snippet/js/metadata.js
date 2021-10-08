window.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".combo_metadata_item").forEach((node, key) => {

        node.addEventListener("click", function (event) {
            event.preventDefault();

            const url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);
            let call = "combo-meta-manager";
            let id = JSINFO.id;
            url.searchParams.set("call", call);
            url.searchParams.set("id", id);
            fetch(url.toString(), {method: 'GET'})
                .then(
                    function (response) {

                        if (response.status !== 200) {
                            console.log('Bad request, status Code is: ' + response.status);
                            return;
                        }

                        // Parses response data to JSON
                        //   * response.json()
                        //   * response.text()
                        // are promise, you need to pass them to a callback to get the value
                        response.json().then(function (jsonMetaDataObject) {

                                let modalId = `combo_metadata_modal_id`;
                                let modalRoot = document.createElement("div");
                                document.body.appendChild(modalRoot);
                                modalRoot.classList.add("modal", "fade");
                                modalRoot.addEventListener('hidden.bs.modal', function (event) {
                                    document.body.removeChild(modalRoot);
                                });

                                modalRoot.setAttribute("id", modalId);
                                // Uncaught RangeError: Maximum call stack size exceeded caused by the tabindex
                                // modalRoot.setAttribute("tabindex", "-1");
                                modalRoot.setAttribute("aria-hidden", "true")
                                const modalDialog = document.createElement("div");
                                modalDialog.classList.add(
                                    "modal-dialog",
                                    "modal-dialog-scrollable",
                                    "modal-fullscreen-md-down",
                                    "modal-lg");
                                modalDialog.style.setProperty("margin", "5rem auto");
                                modalRoot.appendChild(modalDialog);
                                const modalContent = document.createElement("div");
                                modalContent.classList.add("modal-content");
                                modalDialog.appendChild(modalContent);


                                /**
                                 * Parsing the data
                                 * before creating the header and body modal
                                 */
                                let htmlFormElementsByTab = {};
                                let htmlValue;
                                let label;
                                let inputType;
                                let metadataValue;
                                let htmlElement;
                                let metadataValues = [];
                                let defaultValueHtml;
                                let metadataProperties;
                                let metadataMutable;
                                let metadataDefault;
                                let metadataType;
                                let disabled;
                                let metadataTab;
                                for (const metadata in jsonMetaDataObject) {
                                    if (jsonMetaDataObject.hasOwnProperty(metadata)) {
                                        let id = `colForm${metadata}`;
                                        metadataProperties = jsonMetaDataObject[metadata];
                                        metadataValue = metadataProperties["value"];
                                        metadataMutable = metadataProperties["mutable"];
                                        metadataDefault = metadataProperties["default"];
                                        metadataValues = metadataProperties["values"];
                                        metadataType = metadataProperties["type"];
                                        metadataTab = metadataProperties["tab"];
                                        label = metadataProperties["label"];
                                        htmlElement = "";

                                        /**
                                         * The label and the first cell
                                         * @type {string}
                                         */
                                        if (label === undefined) {
                                            label = metadata
                                                .split(/_|-/)
                                                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                                                .join(" ");
                                        }


                                        /**
                                         * The creation of the form element
                                         */
                                        if (metadataValues !== undefined) {

                                            /**
                                             * Select element
                                             * @type {string}
                                             */
                                            htmlElement = "select";
                                            defaultValueHtml = "";
                                            if (metadataDefault !== undefined) {
                                                defaultValueHtml = ` (${metadataDefault})`;
                                            }

                                            htmlElement = `<select class="form-select" aria-label="${label}">`;
                                            let selected = "";
                                            if (metadataValue === null) {
                                                selected = "selected";
                                            }
                                            htmlElement += `<option ${selected}>Default${defaultValueHtml}</option>`;
                                            for (let selectValue of metadataValues) {
                                                if (selectValue === metadataValue) {
                                                    selected = "selected";
                                                } else {
                                                    selected = "";
                                                }
                                                htmlElement += `<option value="${selectValue}" ${selected}>${selectValue}</option>`;
                                            }
                                            htmlElement += `</select>`;


                                        } else {

                                            /**
                                             * Input Element
                                             * @type {string}
                                             */
                                            htmlElement = "input";
                                            let htmlClass = "form-control";
                                            let checked = "";

                                            /**
                                             * Type ?
                                             */
                                            switch (metadataType) {
                                                case "datetime":
                                                    inputType = "datetime-local";
                                                    if (metadataValue !== null) {
                                                        metadataValue = metadataValue.slice(0, 19);
                                                    }
                                                    break;
                                                case "paragraph":
                                                    inputType = "textarea";
                                                    break;
                                                case "boolean":
                                                    inputType = "checkbox";
                                                    htmlClass = "form-check-input";
                                                    if (metadataValue === true) {
                                                        checked = "checked"
                                                    }
                                                    break;
                                                case "line":
                                                default:
                                                    inputType = "text";
                                            }

                                            if (metadataValue !== null) {
                                                htmlValue = `value="${metadataValue}"`;
                                            } else {
                                                htmlValue = `placeholder="${metadataDefault}"`;
                                            }
                                            if (metadataMutable !== undefined && metadataMutable === false) {
                                                disabled = "disabled";
                                            } else {
                                                disabled = "";
                                            }

                                            htmlElement = `<input type="${inputType}" class="${htmlClass}" id="${id}" ${htmlValue} ${checked} ${disabled}>`;

                                        }

                                        if (htmlFormElementsByTab[metadataTab] === undefined) {
                                            htmlFormElementsByTab[metadataTab] = [];
                                        }
                                        htmlFormElementsByTab[metadataTab].push({
                                                "id": id,
                                                "label": label,
                                                "element": htmlElement,
                                                "type": metadataType
                                            }
                                        );

                                    }
                                }

                                /**
                                 * Creating the header with the tab
                                 * @type {HTMLDivElement}
                                 */
                                const modalHeader = document.createElement("div");
                                modalHeader.classList.add("modal-header")
                                modalHeader.innerHTML = `
<h5 class="modal-title">Metadata Manager</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
`;
                                modalContent.appendChild(modalHeader);

                                /**
                                 * Creating the tabs
                                 */
                                let htmlTabNavs = '<ul class="nav nav-tabs mb-3">';
                                let activeClass;
                                let ariaSelected;
                                this.getTabPaneId = function (tab) {
                                    let htmlId = tab.replace(" ", "-");
                                    return `combo-metadata-tab-pane-${htmlId}`;
                                }
                                this.getTabNavId = function (tab) {
                                    let htmlId = tab.replace(" ", "-");
                                    return `combo-metadata-tab-nav-${htmlId}`;
                                }
                                let defaultTab = "Page";
                                for (let tab in htmlFormElementsByTab) {
                                    if (tab === defaultTab) {
                                        activeClass = "active";
                                        ariaSelected = "true";
                                    } else {
                                        activeClass = "";
                                        ariaSelected = "false";
                                    }
                                    let tabPanId = this.getTabPaneId(tab);
                                    let tabNavId = this.getTabNavId(tab);
                                    htmlTabNavs += `
<li class="nav-item">
    <button class="nav-link ${activeClass}" id="${tabNavId}" type="button" role="tab" aria-selected="${ariaSelected}" aria-controls="${tabPanId}" data-bs-toggle="tab" data-bs-target="#${tabPanId}">${tab}</button>
</li>`
                                }
                                htmlTabNavs += '</ul>';


                                /**
                                 * Creating the form
                                 */
                                const modalBody = document.createElement("div");
                                modalBody.classList.add("modal-body");
                                modalContent.appendChild(modalBody);


                                let htmlTabPans = "<div class=\"tab-content\">";
                                let rightColSize;
                                let leftColSize;
                            let classLabel;
                            for (let tab in htmlFormElementsByTab) {
                                let tabPaneId = this.getTabPaneId(tab);
                                let tabNavId = this.getTabNavId(tab);
                                if (tab === defaultTab) {
                                    activeClass = "active";
                                } else {
                                    activeClass = "";
                                }
                                htmlTabPans += `<div class="tab-pane ${activeClass}" id="${tabPaneId}" role="tabpanel" aria-labelledby="${tabNavId}">`;
                                if (tab === "Quality") {
                                    leftColSize = 4;
                                    rightColSize = 8;
                                } else if (tab === "Language") {
                                    leftColSize = 2;
                                    rightColSize = 10;
                                } else {
                                    leftColSize = 3;
                                    rightColSize = 9;
                                }
                                for (let htmlFormElement of htmlFormElementsByTab[tab]) {
                                    let id = htmlFormElement["id"];
                                    let label = htmlFormElement["label"];
                                    let htmlElement = htmlFormElement["element"];
                                    let datatype = htmlFormElement["type"];
                                    if (datatype === "boolean") {
                                        classLabel = "form-check"
                                    } else {
                                        classLabel = "col-form-label";
                                    }
                                    htmlTabPans += `
<div class="row mb-3">
    <label for="${id}" class="col-sm-${leftColSize} ${classLabel}">${label}</label>
    <div class="col-sm-${rightColSize}">${htmlElement}</div>
</div>
`;
                                }
                                htmlTabPans += "</div>"
                            }
                                htmlTabPans += "</div>";

                                let formId = call + modalId;
                                modalBody.innerHTML = `<form id="${formId}">${htmlTabNavs} ${htmlTabPans} </form>`;

                                const modalFooter = document.createElement("div");
                                modalFooter.classList.add("modal-footer");
                                modalFooter.innerHTML = `
 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
<button type="submit" form="${formId}" class="btn btn-primary">Submit</button>
`;
                                modalContent.appendChild(modalFooter);

                                options = {
                                    "backdrop": true,
                                    "keyboard": true,
                                    "focus": true
                                };
                                const bootStrapModal = new bootstrap.Modal(modalRoot, options);
                                bootStrapModal.show();

                                /**
                                 * Init the tooltip
                                 */
                                document.querySelectorAll(`#${modalId} [data-bs-toggle="tooltip"]`).forEach(el => new bootstrap.Tooltip(el));
                            }
                        );
                    }
                )
                .catch(function (err) {
                    console.log('Fetch Error', err);
                });
        }, false);
    });
});

