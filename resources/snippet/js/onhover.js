window.addEventListener("load", function () {

    let inOutOnHover = function (el) {
        const $element = jQuery(el.target);
        $element.toggleClass($element.attr("data-hover-class"));
    };

    /**
     * Bind hover class to a toogle element
     * @param event
     * https://api.jquery.com/hover/
     */
    jQuery("[data-hover-class]").hover(inOutOnHover,inOutOnHover);


    /**
     * Add binding when node are added
     * @type {MutationObserver}
     */
    const observer = new MutationObserver(function (mutationList) {
        // noinspection JSUnfilteredForInLoop
        mutationList.forEach((mutation) => {

            for (let index in mutation.addedNodes) {
                let node = mutation.addedNodes[index];
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.dataset.hasOwnProperty("hoverClass")) {
                        jQuery(node).hover(inOutOnHover,inOutOnHover);
                    }
                }

            }

        });
    });
    observer.observe(document, {childList: true, subtree: true});

});




