window.addEventListener("DOMContentLoaded", function () {
    let navigation = JSINFO["navigation"];
    let acronym = 'lqpp';
    document.querySelectorAll('[data-lqpp-link="warning"], [data-lqpp-link="login"]').forEach(element => {
        let tooltipHtml = "";
        let pageId = element.dataset.wikiId;
        let linkType = element.dataset.lqppLink;
        switch (linkType) {
            case "warning":
                tooltipHtml = `<h3>Warning: Low Quality Page</h3>
<p>This page has been detected as being of low quality. (${acronym})</p>`;
                break
            case "login":
                if (navigation === "anonymous") {
                    element.style.setProperty("pointer-events", "none"); // not clickable
                    tooltipHtml = `<h3>Login Required</h3>
<p>This page has been detected as being of low quality. To follow this link (${pageId}), you need to log in (${acronym}).</p>`;
                }
                break;

        }
        new bootstrap.Tooltip(element, {
            "html": true,
            "placement": "top",
            "title": tooltipHtml,
            "customClass": "lqpp"
        });
    });
});
