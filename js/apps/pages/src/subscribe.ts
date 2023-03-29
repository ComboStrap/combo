import {Modal} from "bootstrap";


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

            let formData = new FormData(form);
            let response = await fetch("/combo/api/v1.0/list/registration", {
                body: formData,
                cache: 'no-cache',
                method: "post",
                mode: 'no-cors',
                redirect: 'follow',
                credentials: 'same-origin'
            });

            /**
             * Modal
             */
            let idResultModal = 'resultModal';
            let modal = `
<div class="modal fade" id="${idResultModal}" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="resultModalLabel">Thank you</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Message
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>
</div>
`
            document.body.insertAdjacentHTML('beforeend', modal);
            const button = event.target as HTMLButtonElement;
            let componentElement = button.closest(subscribeComponentSelector)!;

            let componentRect = componentElement.getBoundingClientRect();
            // ! at the end to tell typescript that it's not null
            let resultModalElement = document.getElementById(idResultModal)!;
            /**
             * Position the modal just below the button via the modal-content element
             */
            let resultModalDialog = resultModalElement.firstElementChild! as HTMLDivElement;
            resultModalDialog.style.margin = '0';
            resultModalDialog.style.left = componentRect.left + 'px';
            resultModalDialog.style.top = (componentRect.top + componentRect.height) + 'px';

            const resultModal = new Modal(resultModalElement);
            let title = "Success";
            // ! at the end to tell typescript that it's not null
            let modalCloseButton = resultModalElement.querySelector(".modal-footer button")! as HTMLButtonElement;
            let message;

            try {
                let data = await response.json();
                message = data.message;
            } catch (e) {
                // in case of network error
            }
            if (response.status !== 200) {
                title = "Error";
                if (typeof message === 'undefined') {
                    message = "Sorry. The server seems to be down.";
                }
                modalCloseButton.onclick = function () {
                    resultModal.hide();
                };
            } else {

                if (typeof message === 'undefined') {
                    message = "A validation email has been send. <br>Check your mailbox and click on the validation link.<br>If you don't find our email, check your spambox.";
                }
            }

            let resultModalLabel = resultModalElement.querySelector("#resultModalLabel")!;
            resultModalLabel.innerHTML = title;

            let resultModalBody = resultModalElement.querySelector(".modal-body")!;
            resultModalBody.innerHTML = message;
            resultModal.show();

        }
        form.classList.add('was-validated')
    }, false)
});


