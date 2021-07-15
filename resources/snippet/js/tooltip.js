window.addEventListener('load', function () {
    let namespace = "bs"
    let version = 5;
    if (typeof jQuery.fn.tooltip.Constructor.VERSION !== 'undefined') {
        version = parseInt(jQuery.fn.tooltip.Constructor.VERSION.substr(0, 1),10);
    } else if (typeof bootstrap.Tooltip.VERSION !== 'undefined') {
        version = parseInt(bootstrap.Tooltip.VERSION.substr(0, 1),10);
    }
    if (version < 5) {
        namespace = "";
    }
    document.querySelectorAll(`[data${namespace}-toggle="tooltip"]`).forEach(el => el.tooltip());
});
