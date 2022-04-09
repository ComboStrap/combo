/**
 * Move the side slot in the toc area
 * on media larger than 992
 */
window.addEventListener("load", function () {
    let mediaListener = function (mediaQuery) {
        let side = document.getElementById("main-side");
        if (mediaQuery.matches) {
            if (side.parentElement.getAttribute("id") !== "main-toc") {
                let toc = document.getElementById("main-toc");
                toc.appendChild(side);
            }
        } else {
            if (side.previousElementSibling.getAttribute("id") !== "main-content") {
                let mainContent = document.getElementById("main-content");
                mainContent.insertAdjacentElement('afterend', side)
            }
        }
    }
    let minWidthMediaQuery = window.matchMedia('(min-width:992px)');
    mediaListener(minWidthMediaQuery);
    minWidthMediaQuery.addEventListener("change", mediaListener);
});
