window.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".combo_metadata_item").forEach((node, key) => {

        node.addEventListener("click", function (event) {
            event.preventDefault();
            console.log("Metadata was asked");
            const modalRoot = document.createElement("div");
            document.body.appendChild(modalRoot);
            modalRoot.classList.add("modal", "fade");
            let id = `combo_metadata_modal_id`;
            modalRoot.setAttribute("id", id);
            //modalRoot.setAttribute("tabindex", "-1");
            modalRoot.setAttribute("aria-hidden", "true")
            const modalDialog = document.createElement("div");
            modalDialog.classList.add("modal-dialog","modal-dialog-centered","modal-dialog-scrollable");
            modalRoot.appendChild(modalDialog);
            const modalContent = document.createElement("div");
            modalContent.classList.add("modal-content");
            modalDialog.appendChild(modalContent);
            const modalBody = document.createElement("div");
            modalBody.classList.add("modal-body");
            modalBody.textContent = "Show a second modal and hide this one with the button below.";
            modalContent.appendChild(modalBody);
            options = {
                "backdrop": true,
                "keyboard": true,
                "focus": true
            };
            const bootStrapModal = new bootstrap.Modal(modalRoot, options);
            bootStrapModal.show();
        }, false);
    });
});

