/* global combo */
// noinspection JSUnresolvedVariable

window.addEventListener("DOMContentLoaded", function () {


        document.querySelectorAll(".combo-cache-item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", async function (event) {
                event.preventDefault();

                let pageId = JSINFO.id;
                let modalBacklinkId = combo.toHtmlId(`combo-cache-${pageId}`);
                let cacheModal = combo.getOrCreateModal(modalBacklinkId)
                    .resetIfBuild()
                    .addDialogClass("modal-fullscreen-md-down");

                /**
                 * Creating the form
                 */

                let html = `<p>List of <a href="https://combostrap.com/page/cache">cache information</a> for the slots of the page (${pageId}).</p>`;

                /**
                 * Add the page runtime cache metadata field
                 */
                let cachePageInfo = document.querySelector('script[type="application/combo+cache+json"]');
                if (cachePageInfo !== null) {
                    let cachePageJsonString = cachePageInfo
                        .innerText
                        .trim()
                        .slice("/*<![CDATA[*/".length)
                        .slice(0, -("/*!]]>*/".length));

                    html += `<table class="table table-striped table-hover text-nowrap overflow-auto"><thead><th>Slot</th><th>Output</th><th>Cache <br/>Hit</th><th title="Modification time of the cache file">Modification <br/>Time</th><th>Cache Deps</th><th>Cache File</th></thead>`;
                    let cachePageJson = JSON.parse(cachePageJsonString);
                    for (let slot in cachePageJson) {
                        if (!cachePageJson.hasOwnProperty(slot)) {
                            continue;
                        }

                        let formatResults = cachePageJson[slot];
                        let outputCounterBySlot = 0;
                        let slotUrl = combo.DokuUrl.createEdit(slot).toString()
                        let slotLabel = `<a href="${slotUrl}" title="Edit the slot ${slot}">${slot}</a>`;
                        for (let formatResult in formatResults) {
                            if (!formatResults.hasOwnProperty(formatResult)) {
                                continue;
                            }
                            outputCounterBySlot++;
                            if (outputCounterBySlot > 1) {
                                slotLabel = "";
                            }
                            let result = formatResults[formatResult];
                            // Mode
                            let styledFormatResult;
                            if (formatResult === "i") {
                                styledFormatResult = "Parse Instructions"
                            } else {
                                styledFormatResult = formatResult.charAt(0).toUpperCase() + formatResult.slice(1);
                            }
                            let hit = result["result"];
                            let checkedBox = "";
                            if (hit === true) {
                                checkedBox = "checked";
                            }
                            let hitHtml = ` <input type="checkbox" class="form-check-input" disabled ${checkedBox}>`
                            let mtime = combo.comboDate.createFromIso(result["mtime"]).toSqlTimestampString();
                            let file = result["file"];
                            let fileLabel = file.substr(file.indexOf(':') + 1, file.lastIndexOf('.') - 2);
                            let fileUrl = combo.DokuUrl
                                .createFetch(file,'cache')
                                .toString();
                            let fileAnchor = `<a href="${fileUrl}" target="_blank">${fileLabel}</a>`;

                            let dependencies = "";
                            let dependency = result["dependency"];
                            if (typeof dependency !== 'undefined') {
                                dependencies = dependency.join(", ");
                            }
                            html += `<tr><td>${slotLabel}</td><td>${styledFormatResult}</td><td>${hitHtml}</td><td>${mtime}</td><td>${dependencies}</td><td>${fileAnchor}</td></tr>`;
                        }
                    }
                    html += '</table><hr/>';
                }

                /**
                 * The modal
                 */
                cacheModal
                    .setHeader(`Cache Info for the page (${pageId})`)
                    .addBody(html)
                    .addFooterCloseButton()
                    .show();
            });

        });
    }
);

