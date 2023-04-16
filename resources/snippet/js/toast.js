/* global bootstrap */
window.addEventListener("DOMContentLoaded",function(){
    const toastElements = [].slice.call(document.querySelectorAll('.toast'));
    toastElements.map(function (toastElement) {
        let toast = new bootstrap.Toast(toastElement);
        toast.show();
        if(toastElement.dataset.bsAutohide==="false"){
            toastElement.querySelector("button").focus();
        }
    });
});
