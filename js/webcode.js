

// Webcode
// Will set the height of an iframe to his content
// If the attribute is not set
window.addEventListener("load", function(event) {

    // Select the iframe element with the class webCode
    var webCodeIFrames = document.querySelectorAll("iframe.webCode");

    // Set the height of the iframe to be the height of the internal iframe
    if (webCodeIFrames!=null) {
        for (i = 0; i < webCodeIFrames.length; i++) {
            var webCodeIFrame = webCodeIFrames[i];
            var height = webCodeIFrame.getAttribute('height');
            if (height == null) {
                webCodeIFrame.height = webCodeIFrame.contentWindow.document.querySelector("html").offsetHeight
            }
        }
    }


});
