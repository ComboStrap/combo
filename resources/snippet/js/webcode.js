let WEBCODE = (function () {

    /**
     * Adjust the height of an iframe to his content
     * @param iframeElement
     */
    let adjustHeightToFitContent = function (iframeElement) {
        let htmlIFrameElement = iframeElement.contentWindow.document.querySelector("html");
        let calculatedHeight = htmlIFrameElement.offsetHeight;
        let defaultHtmlElementHeight = 150;
        if (calculatedHeight === defaultHtmlElementHeight) {
            // body and not html because html has a default minimal height of 150
            calculatedHeight = iframeElement.contentWindow.document.querySelector("body").offsetHeight;
            // After setting the height, there is a recalculation and the padding of a descendant phrasing content element
            // may ends up in the html element. The below code corrects that
            requestAnimationFrame(function () {
                if (calculatedHeight !== htmlIFrameElement.offsetHeight) {
                    iframeElement.height = htmlIFrameElement.offsetHeight;
                }
            });
        }
        iframeElement.height = calculatedHeight;
    }
    return {adjustHeightToFitContent: adjustHeightToFitContent}
})();


window.addEventListener('load', function () {
    const IframeObserver = new MutationObserver(function (mutationList) {
        mutationList
            .filter(mutation => {
                // in a iframe, you need to test against the browsing content, not
                // mutation.target instanceof HTMLElement but ...
                return mutation.target instanceof mutation.target.ownerDocument.defaultView.HTMLElement
            })
            .forEach((mutation) => {
                let iframe = mutation.target.ownerDocument.defaultView.frameElement;
                WEBCODE.adjustHeightToFitContent(iframe);
            });
    })
    document.querySelectorAll("iframe.webcode-cs").forEach(iframe => {
        // if height is not set manually
        const height = iframe.getAttribute('height');
        if (height === null) {
            // Set the height of the iframe to be the height of the internal iframe
            WEBCODE.adjustHeightToFitContent(iframe);
            IframeObserver.observe(iframe.contentWindow.document, {attributes: true, childList: true, subtree: true});
        }
    });

});

