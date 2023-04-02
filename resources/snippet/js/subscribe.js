(function (combo) {
    'use strict';

    let componentName = "subscribe";
    const subscribeComponentSelector = `.${componentName}-cs`;
    const forms = document.querySelectorAll(`${subscribeComponentSelector} form`);
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (form.checkValidity()) {
                let url = new URL(form.getAttribute("action"));
                let formData = new FormData(form);
                let response = await fetch(url, {
                    body: formData,
                    method: "post",
                });
                /**
                 * Modal
                 */
                let idResultModal = combo.Html.createRandomIdWithPrefix(componentName);
                let modal = combo.Modal.getOrCreate(idResultModal);
                const button = event.target;
                let componentElement = button.closest(subscribeComponentSelector);
                modal.setPlacementBottomToElement(componentElement);
                let message;
                try {
                    let data = await response.json();
                    message = data.message;
                }
                catch (e) {
                    // in case of network error
                }
                if (response.status !== 200) {
                    modal.setHeader("Error");
                    if (typeof message === 'undefined') {
                        message = "Sorry. The server seems to be down.";
                    }
                }
                else {
                    let header = form.getAttribute("data-success-header");
                    if (header === null) {
                        header = "Hurray!";
                    }
                    modal.setHeader(header);
                    message = form.getAttribute("data-success-content");
                    if (message === null) {
                        message = "A validation email has been send. <br>Check your mailbox and click on the validation link.<br>If you don't find our email, check your spambox.";
                    }
                }
                modal
                    .addBody(message)
                    .show();
            }
        }, false);
    });

})(combo);
