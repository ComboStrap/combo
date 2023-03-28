(() => {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    const forms = document.querySelectorAll('.subscribe-cs')

    // Loop over them, prevent submission and take over
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', async event => {

            event.preventDefault()
            event.stopPropagation()

            if (form.checkValidity()) {

                let formData = new FormData(form);
                let response = await fetch("/combo/api/v1.0/list/registration", {
                    body: formData,
                    cache: 'no-cache',
                    method: "post",
                    mode: 'no-cors',
                    redirect: 'follow',
                    credentials: 'same-origin'
                })

                let data = await response.json();

                /**
                 * Modal
                 */
                let idResultModal = 'resultModal';
                let modal = `
<div class="modal fade" id="${idResultModal}" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
                let resultModalElement = document.getElementById(idResultModal);
                const resultModal = new bootstrap.Modal(resultModalElement);
                let title = "Success";
                let modalCloseButton = resultModalElement.querySelector(".modal-footer button")
                if (response.status !== 200) {
                    title = "Error";
                    modalCloseButton.onclick = function () {
                        resultModal.hide();
                    };
                }
                let message = data.message;
                if (typeof message === 'undefined') {
                    message = "A validation email has been send. <br>Check your mailbox and click on the validation link.<br>If you don't find our email, check your spambox.";
                }
                let resultModalLabel = resultModalElement.querySelector("#resultModalLabel");
                resultModalLabel.innerHTML = title;
                let resultModalBody = resultModalElement.querySelector(".modal-body");
                resultModalBody.innerHTML = message;
                resultModal.show();

            }
            form.classList.add('was-validated')
        }, false)
    })
})();
