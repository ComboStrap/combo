window.onbeforeprint = function() {

    const observer = lozad();
    observer.observe();

    document.querySelectorAll('.lazy-cs').forEach(element => {
            observer.triggerLoad(element);
        }
    )

}
