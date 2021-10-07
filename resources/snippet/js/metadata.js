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

                            const modalRoot = document.createElement("div");
                            document.body.appendChild(modalRoot);
                            modalRoot.classList.add("modal", "fade");
                            let id = `combo_metadata_modal_id`;
                            modalRoot.setAttribute("id", id);
                            // Uncaught RangeError: Maximum call stack size exceeded caused by the tabindex
                            // modalRoot.setAttribute("tabindex", "-1");
                            modalRoot.setAttribute("aria-hidden", "true")
                            const modalDialog = document.createElement("div");
                            modalDialog.classList.add(
                                "modal-dialog",
                                "modal-dialog-centered",
                                "modal-dialog-scrollable",
                                "modal-fullscreen-md-down",
                                "modal-lg");
                            modalRoot.appendChild(modalDialog);
                            const modalContent = document.createElement("div");
                            modalContent.classList.add("modal-content");
                            modalDialog.appendChild(modalContent);
                            const modalBody = document.createElement("div");
                            modalBody.classList.add("modal-body");
                            let htmlFormElements = [];
                            let htmlValue;
                            let disabled;
                            let label;
                            let inputType;
                            let metadataValue;
                            let htmlElement;
                            let metadataValues = [];
                            let defaultValueHtml;
                            let metadataProperties;
                            let metadataMutable;
                            let metadataDefault;
                            for (const metadata in jsonMetaDataObject) {
                                if (jsonMetaDataObject.hasOwnProperty(metadata)) {
                                    let id = `colForm${metadata}`;
                                    metadataProperties = jsonMetaDataObject[metadata];
                                    metadataValue = metadataProperties["value"];
                                    metadataMutable = metadataProperties["mutable"];
                                    metadataDefault = metadataProperties["default"];
                                    metadataValues = metadataProperties["values"];
                                    htmlElement = "";

                                    /**
                                     * The label and the first cell
                                     * @type {string}
                                     */
                                    label = metadata.replace("_", " ");
                                    label = label.charAt(0).toUpperCase() + label.slice(1);

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
                                        inputType = "text";

                                        /**
                                         * Date ?
                                         */
                                        if (metadata.slice(0, 4) === "date") {
                                            if (metadataValue !== null) {
                                                metadataValue = metadataValue.slice(0, 19);
                                            }
                                            inputType = "datetime-local";

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

                                        htmlElement = `<input type="${inputType}" class="form-control" id="${id}" ${htmlValue} ${disabled}>`;

                                    }

                                    htmlFormElements.push({
                                            "id": id,
                                            "label": label,
                                            "element": htmlElement
                                        }
                                    );

                                }
                            }
                            /**
                             * Creating the form
                             * @type {string}
                             */
                            let formId = call + id;
                            let htmlForm = `<form id="${formId}">`;
                            for (let htmlFormElement of htmlFormElements) {
                                let id = htmlFormElement["id"];
                                let label = htmlFormElement["label"];
                                let htmlElement = htmlFormElement["element"];
                                htmlForm += `
<div class="row mb-3">
    <label for="${id}" class="col-sm-2 col-form-label">${label}</label>
    <div class="col-sm-10">${htmlElement}</div>
</div>
`;
                            }
                            htmlForm += "</form>"
                            modalBody.innerHTML = htmlForm;
                            modalContent.appendChild(modalBody);

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
                        });
                    }
                )
                .catch(function (err) {
                    console.log('Fetch Error', err);
                });
        }, false);
    });
});

