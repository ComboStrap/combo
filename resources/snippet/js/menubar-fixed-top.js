(function IIFE() {

    let done = false;
    let fixedMenuSelector = `.navbar[data-type="fixed-top"]`;

    /**
     * Add the target style before anchor navigation
     */
    window.addEventListener("DOMContentLoaded", function () {

        let fixedNavbar = document.querySelector(fixedMenuSelector)
        if (fixedNavbar == null) {
            return;
        }
        let offsetHeight = fixedNavbar.offsetHeight;
        // correct direct navigation via fragment to heading
        let style = document.createElement("style");
        style.classList.add("menubar-fixed-top")
        // textContent and not innerText (it adds br elements)
        style.textContent = `:target {
  scroll-margin-top: ${offsetHeight}px;
}`;
        document.head.appendChild(style);
    })

    /**
     * We do the work after the first scroll
     * to prevent a bad cls (content layout shift) metrics
     * from Google search
     */
    window.addEventListener("scroll", function () {

        if (done) {
            return;
        }
        done = true;

        /**
         * The request animation frame is there to
         * update the class on the navbar and the padding on the
         * body at the same time to not have any layout shift
         */
        window.requestAnimationFrame(function () {
            let fixedNavbar = document.querySelector(fixedMenuSelector)
            if (fixedNavbar == null) {
                return;
            }
            let offsetHeight = fixedNavbar.offsetHeight;
            fixedNavbar.classList.add("fixed-top")
            // correct body padding
            document.body.style.setProperty("padding-top", offsetHeight + "px");
        });

    });
})();
