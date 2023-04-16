window.addEventListener("load", function () {
    [...document.querySelectorAll('.height-toggle-cs')].forEach(element => {
        let parent = element.parentElement;
        let parentBorderColor = window.getComputedStyle(parent).getPropertyValue("color");
        if (parentBorderColor != null) {
            element.style.color = parentBorderColor;
        }
    });
});
