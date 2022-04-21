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
        style.classList.add("menubar-fixed-top")
        // textContent and not innerText (it adds br elements)
        style.textContent = `:target {
  scroll-margin-top: ${offsetHeight}px;
}`;
        document.head.appendChild(style);
    });

});
