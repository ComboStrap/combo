window.addEventListener("load", function (event) {
    [...document.querySelectorAll('.height-toggle-combo > span')].forEach(element => {
        let grandParent = element.parentElement.parentElement;
        let grandParentBorderColor = window.getComputedStyle(grandParent).getPropertyValue("color");
        if (grandParentBorderColor != null) {
            element.style.color = grandParentBorderColor;
        }
    });
});
