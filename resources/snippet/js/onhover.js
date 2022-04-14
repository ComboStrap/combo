window.addEventListener("load", function () {

    let inOutOnHover = function (event) {
        const element = event.currentTarget;
        let dataHoverClass = element.getAttribute("data-hover-class");
        let classes = dataHoverClass.split(' ');
        for(let classValue of classes) {
            if (element.classList.contains(classValue)) {
                element.classList.remove(classValue);
            } else {
                element.classList.add(classValue);
            }
        }
    };

    /**
     * Bind hover class to a toggle element
     * @param event
     */
    document.querySelectorAll('[data-hover-class]').forEach(dataHoverElement => {
        dataHoverElement.addEventListener("mouseenter",inOutOnHover);
        dataHoverElement.addEventListener("mouseleave",inOutOnHover);
    });


    /**
     * Add binding when node are added
     * @type {MutationObserver}
     */
    const observer = new MutationObserver(function (mutationList) {
        // noinspection JSUnfilteredForInLoop
        mutationList.forEach((mutation) => {

            for (let index in mutation.addedNodes) {
                let node = mutation.addedNodes[index];
                if (node instanceof HTMLElement) {
                    if ("hoverClass" in node.dataset) {
                        node.addEventListener("mouseenter",inOutOnHover);
                        node.addEventListener("mouseleave",inOutOnHover);
                    }
                }

            }

        });
    });
    observer.observe(document, {childList: true, subtree: true});

});




