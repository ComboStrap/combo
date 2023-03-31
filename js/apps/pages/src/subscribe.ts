import { Html } from "combo";
import { ComboModal } from "combo";

/**
 * Select all subscribe forms
 */
const subscribeComponentSelector = '.subscribe-cs';
const forms = document.querySelectorAll<HTMLFormElement>(`${subscribeComponentSelector} form`)


/**
 * Loop over them, prevent submission and take over
 */
Array.from(forms).forEach(form => {
    form.addEventListener('submit', async event => {

        event.preventDefault();
        event.stopPropagation();
        if (form.checkValidity()) {

            let url = new URL(form.getAttribute("action")!);
            let formData = new FormData(form);
            let response = await fetch(url, {
                body: formData,
                method: "post",
            });

            /**
             * Modal
             */
            let idResultModal = Html.createRandomIdWithPrefix("subscribe");
            let modal = ComboModal.getOrCreate(idResultModal);

            const button = event.target as HTMLButtonElement;
            let componentElement = button.closest(subscribeComponentSelector)!;

            let componentRect = componentElement.getBoundingClientRect();
            /**
             * Position the modal just below the button via the modal-content element
             */
            let resultModalDialog = modal.getModalContentElement();
            resultModalDialog.style.margin = '0';
            resultModalDialog.style.left = componentRect.left + 'px';
            resultModalDialog.style.top = (componentRect.top + componentRect.height) + 'px';

            let message;
            try {
                let data = await response.json();
                message = data.message;
            } catch (e) {
                // in case of network error
            }
            debugger;
            if (response.status !== 200) {
                modal.setHeader("Error");
                if (typeof message === 'undefined') {
                    message = "Sorry. The server seems to be down.";
                }
            } else {
                modal.setHeader("Success");
                if (typeof message === 'undefined') {
                    message = "A validation email has been send. <br>Check your mailbox and click on the validation link.<br>If you don't find our email, check your spambox.";
                }
            }
            modal
                .addBody(message)
                .show();

        }
    }, false)
});


