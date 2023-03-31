import {Html, Modal} from "combo";



let componentName = "subscribe";
const subscribeComponentSelector = `.${componentName}-cs`;


const forms = document.querySelectorAll<HTMLFormElement>(`${subscribeComponentSelector} form`)
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
            let idResultModal = Html.createRandomIdWithPrefix(componentName);
            let modal = Modal.getOrCreate(idResultModal);

            const button = event.target as HTMLButtonElement;
            let componentElement = button.closest(subscribeComponentSelector)!;
            modal.setPlacementBottomToElement(componentElement);

            let message;
            try {
                let data = await response.json();
                message = data.message;
            } catch (e) {
                // in case of network error
            }
            if (response.status !== 200) {
                modal.setHeader("Error");
                if (typeof message === 'undefined') {
                    message = "Sorry. The server seems to be down.";
                }
            } else {
                modal.setHeader("Hurray!");
                if (typeof message === 'undefined') {
                    message = "A validation email has been send. <br>Check your mailbox and click on the validation link.<br>If you don't find our email, check your spambox.";
                }
                message += '. <br> You can contact me if you have any trouble.<br>Nico';
            }
            modal
                .addBody(message)
                .show();

        }
    }, false)
});


