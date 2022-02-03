window.addEventListener("DOMContentLoaded",function(){
    let fixedNavbar = document.querySelector(".navbar.fixed-top")
    // correct body padding
    let offsetHeight = fixedNavbar.offsetHeight;
    let marginTop = offsetHeight - 16; // give more space at the top (ie 1rem)
    document.body.style.setProperty("padding-top",offsetHeight+"px");
    // correct direct navigation via fragment to heading
    let style = document.createElement("style");
    style.classList.add("menubar-fixed-top")
    // no main > h1, we never jump on h1 and it would add a space with the main header
    style.innerText = `main > h2, main > h3, main > h4, main > h5, #dokuwiki__top, .fn_top {
    padding-top: ${offsetHeight}px;
    margin-top: -${marginTop}px;
    z-index: -1;
}`;
    document.head.appendChild(style);
});
