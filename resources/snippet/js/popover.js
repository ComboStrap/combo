// Enable popovers
window.addEventListener('load', function () {
    let namespace = "-bs"
    let version = 5;
    let options = {};
    options.sanitize = false;
    if (typeof jQuery != 'undefined' && typeof jQuery.fn.tooltip.constructor.VERSION !== 'undefined') {
        version = parseInt(jQuery.fn.tooltip.constructor.VERSION.substr(0, 1), 10);
        if (version < 5) {
            namespace = "";
        }
        jQuery(`[data${namespace}-toggle="popover"]`).popover(options);
    } else if (typeof bootstrap.Popover.VERSION !== 'undefined') {
        version = parseInt(bootstrap.Popover.VERSION.substr(0, 1), 10);
        if (version < 5) {
            namespace = "";
        }
        document.querySelectorAll(`[data${namespace}-toggle="popover"]`)
            .forEach(el => {
                new bootstrap.Popover(el, options);
                // to not navigate on a anchor
                el.onclick = (event => event.preventDefault());
            });
    }
});
