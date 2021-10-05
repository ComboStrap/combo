window.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".combo_metadata_item").forEach((node, key) => {

        node.addEventListener("click", function (event) {
            event.preventDefault();

            const url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);
            url.searchParams.set("call", "combo-meta-manager");
            url.searchParams.set("id", JSINFO.id);
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
                        response.json().then(function (data) {

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
                            modalBody.innerHTML = JSON.stringify(data) ;
                            modalContent.appendChild(modalBody);
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

