window.addEventListener("DOMContentLoaded",function(){


    /**
     * The request animation frame is there to
     * update the class on the navbar and the padding on the
     * body at the same time to not have any layout shift
     */
    window.requestAnimationFrame(function() {
        let fixedNavbar = document.querySelector(".navbar[data-type=\"fixed-top\"]")
        fixedNavbar.classList.add("fixed-top");
        // correct body padding
        let offsetHeight = fixedNavbar.offsetHeight;
        document.body.style.setProperty("padding-top",offsetHeight+"px");
        // correct direct navigation via fragment to heading
        let style = document.createElement("style");
        let marginTop = offsetHeight - 2; // adjustment to not see the text above
        style.classList.add("menubar-fixed-top")
        // no main > h1, we never jump on h1 and it would add a space with the main header
        style.innerText = `.outline-heading, #dokuwiki__top, .fn_top {
    padding-top: ${offsetHeight}px;
    margin-top: -${marginTop}px;
    z-index: -1;
    position: relative;
}`;
        document.head.appendChild(style);
    });

});
