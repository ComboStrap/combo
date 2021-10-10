window.addEventListener("DOMContentLoaded", function () {


    /**
     * A pointer to the created modals
     */
    let comboModals = {};

    let getComboModal = function (modalId) {
        return comboModals[modalId];
    }
    /**
     * Create a modal and return the modal content element
     * @return ComboModal
     */
    let createComboModal = function (modalId) {

        let modal = new ComboModal(modalId);
        comboModals[modalId] = modal;
        return modal;
    }

    /**
     * Create a ajax call
     * @return ComboAjaxCall
     */
    let createGetCall = function (call) {

        let comboCall = new ComboAjaxCall(call);
        comboCall.setMethod("GET");
        return comboCall;
    }

    class ComboModal {

        /**
         * @type HTMLDivElement
         */
        modalFooter;

        constructor(modalId) {
            this.modalId = modalId;

            this.modalRoot = document.createElement("div");

            document.body.appendChild(this.modalRoot);
            this.modalRoot.setAttribute("id", modalId);
            this.modalRoot.classList.add("modal", "fade");
            // Uncaught RangeError: Maximum call stack size exceeded caused by the tabindex
            // modalRoot.setAttribute("tabindex", "-1");
            this.modalRoot.setAttribute("aria-hidden", "true");

            const modalManagerDialog = document.createElement("div");
            modalManagerDialog.classList.add(
                "modal-dialog",
                "modal-dialog-scrollable",
                "modal-fullscreen-md-down",
                "modal-lg");
            // Get the modal more central but fix as we have tab and
            // we want still the mouse below the tab when we click
            modalManagerDialog.style.setProperty("margin", "5rem auto");
            modalManagerDialog.style.setProperty("height","calc(100% - 9rem)");
            this.modalRoot.appendChild(modalManagerDialog);
            this.modalContent = document.createElement("div");
            this.modalContent.classList.add("modal-content");
            modalManagerDialog.appendChild(this.modalContent);

            this.modalBody = document.createElement("div");
            this.modalBody.classList.add("modal-body");
            this.modalContent.appendChild(this.modalBody);

            /**
             * The modal can only be invoked when the body has been defined
             *
             */
            let options = {
                "backdrop": true,
                "keyboard": true,
                "focus": true
            };
            this.bootStrapModal = new bootstrap.Modal(this.modalRoot, options);
        }

        setHeader(headerText) {
            let html = `
<div class="modal-header">
    <h5 class="modal-title">${headerText}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
`;
            this.modalContent.insertAdjacentHTML('afterbegin', html);
        }

        addBody(htmlBody) {

            this.modalBody.innerHTML = htmlBody;

        }

        createModalFooter() {
            this.modalFooter = document.createElement("div");
            this.modalFooter.classList.add("modal-footer");
            this.modalContent.appendChild(this.modalFooter);
        }

        /**
         *
         * @type HTMLButtonElement|string htmlFooter
         */
        addFooterButton(htmlFooter) {


            if (this.modalFooter === undefined) {
                this.createModalFooter();
            }
            if (typeof htmlFooter === 'string' || htmlFooter instanceof String) {
                this.modalFooter.insertAdjacentHTML('beforeend', htmlFooter);
            } else {
                this.modalFooter.appendChild(htmlFooter);
            }


        }

        /**
         *
         * @return HTMLButtonElement the close button
         */
        addFooterCloseButton(label = "Close") {
            let closeButton = document.createElement("button");
            closeButton.classList.add("btn", "btn-secondary")
            closeButton.innerText = label;
            let modal = this;
            closeButton.addEventListener("click", function () {
                modal.bootStrapModal.hide();
            });
            this.addFooterButton(closeButton);
            return closeButton;
        }

        show() {


            this.bootStrapModal.show();
            /**
             * Init the tooltip if any
             */
            document.querySelectorAll(`#${this.modalId} [data-bs-toggle="tooltip"]`).forEach(el => new bootstrap.Tooltip(el));
        }

        dismiss() {
            this.bootStrapModal.hide();
        }
    }


    class ComboAjaxCall {


        method = "GET";

        constructor(call = undefined) {
            this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);
            if(call===undefined){
                call = "combo-meta-manager";
            }
            this.url.searchParams.set("call", call);
            this.id = JSINFO.id;
        }

        setPageId(id) {
            this.id = id;
            return this;
        }

        setProperty(key, value) {
            this.url.searchParams.set(key, value);
            return this;
        }

        async getJson() {

            this.url.searchParams.set("id", this.id);
            let response = await fetch(this.url.toString(), {method: this.method});

            if (response.status !== 200) {
                console.log('Bad request, status Code is: ' + response.status);
                return {};
            }

            // Parses response data to JSON
            //   * response.json()
            //   * response.text()
            // are promise, you need to pass them to a callback to get the value
            return response.json();

        }

        setMethod(method) {
            this.method = method;
        }
    }

    /**
     *
     * @type ComboModal modalManager
     */
    async function openMetaViewer(modalManager) {
        let modalViewerId = `combo_metadata_viewer`;
        let modalViewer = getComboModal(modalViewerId);
        if (modalViewer === undefined) {
            modalViewer = createComboModal(modalViewerId);
            modalViewer.setHeader("Metadata Viewer");
            let viewerCall = createGetCall();
            viewerCall.setProperty("type","viewer");
            let json = JSON.stringify(await viewerCall.getJson(), null, 2);

            modalViewer.addBody(`
<p>The metadata viewer shows you the content of the metadadata file (ie all metadata managed by ComboStrap or not):</p>
<pre>${json}</pre>
`);
            let closeButton = modalViewer.addFooterCloseButton("Close and Return to the Metadata Manager");
            closeButton.addEventListener("click", function () {
                modalManager.show();
            });
        }
        modalViewer.show();

    }

    let openMetadataManager = async function () {


        let modalManagerId = `combo_metadata_manager`;
        let managerModal = getComboModal(modalManagerId);

        if (managerModal === undefined) {

            managerModal = createComboModal(modalManagerId);

            let call = createGetCall();
            let jsonMetaDataObject = await call.getJson();


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
             * Header
             */
            managerModal.setHeader("Metadata Manager");

            /**
             * Creating the Body
             * (Starting with the tabs)
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

            let formId = modalManagerId+"_form";
            managerModal.addBody(`<form id="${formId}">${htmlTabNavs} ${htmlTabPans} </form>`);

            /**
             * Footer
             */
            let viewerButton = document.createElement("button");
            viewerButton.classList.add("btn", "btn-link", "text-primary", "text-decoration-bone", "fs-6", "text-muted");
            viewerButton.style.setProperty("font-weight", "300");
            viewerButton.textContent = "Viewer";
            viewerButton.addEventListener("click", function (event) {
                managerModal.dismiss();
                openMetaViewer(managerModal);
            });
            managerModal.addFooterButton(viewerButton);
            managerModal.addFooterCloseButton();
            managerModal.addFooterButton(`<button type="submit" form="${formId}" class="btn btn-primary">Submit</button>`);
        }
        managerModal.show();

    }


    document.querySelectorAll(".combo_metadata_item").forEach((metadataControlItem, key) => {

        metadataControlItem.addEventListener("click", function (event) {
            event.preventDefault();
            void openMetadataManager().catch(console.error);
        });

    });
})

