window.addEventListener('load', function () {

    document.querySelectorAll('form.edit-button-cs').forEach(editButtonFormElement => {

        /**
         * @type {HTMLElement}
         */
        let parent = null;
        let classNameFunction = "edit-button-highlight-cs";
        editButtonFormElement.addEventListener('mouseover', function (event) {
            if (parent === null) {
                parent = event.target.parentNode;
            }
            parent.classList.add(classNameFunction);
        });
        editButtonFormElement.addEventListener('mouseout', function () {
            if (parent !== null) {
                parent.classList.remove(classNameFunction);
            }
        });

    });

    // Disable the highlight function of pages.js
    dw_page.sectionHighlight = function () {
    };

});
