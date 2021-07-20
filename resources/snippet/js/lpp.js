window.addEventListener("DOMContentLoaded", function () {
    let navigation = JSINFO["navigation"];
    let acronym = "lpp";
    document.querySelectorAll(`[data-${acronym}-link]`).forEach(element => {
            if (navigation === "anonymous") {
                element.addEventListener('click', function (event) {
                    // not pointer-events: none because we need to show a tooltip
                    event.preventDefault();
                });
                let tooltipHtml = `<h4>Login Required</h4>
<p>To follow this link, you need to log in (${acronym})</p>`;

                // An element may already have a informational tooltip
                let tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip != null) {
                    tooltip.dispose();
                }
                element.setAttribute("title", tooltipHtml);
                new bootstrap.Tooltip(element, {
                    html: true,
                    placement: "top",
                    customClass: acronym
                });
            }
        });
});
