/* global bootstrap */
// noinspection ES6ConvertVarToLetConst
window.combos = (function (module) {
    module.popover = {
        getDataNamespace: function () {
            let defaultNamespace = "-bs";
            let bootstrapVersion = 5;
            if (typeof bootstrap.Popover.VERSION !== 'undefined') {
                bootstrapVersion = parseInt(bootstrap.Popover.VERSION.substr(0, 1), 10);
                if (bootstrapVersion < 5) {
                    return "";
                }
                return defaultNamespace;
            }
            if (typeof jQuery != 'undefined' && typeof jQuery.fn.tooltip.constructor.VERSION !== 'undefined') {
                bootstrapVersion = parseInt(jQuery.fn.tooltip.constructor.VERSION.substr(0, 1), 10);
                if (bootstrapVersion < 5) {
                    return "";
                }
                return defaultNamespace;
            }
            return defaultNamespace;
        },
        /**
         *
         * @param cssSelector - the popovers css selector to enable, if undefined, all
         * element found with the `data[-bs]-toggle=popover` selector will be used
         */
        enable: function (cssSelector) {
            let options = {};
            if (typeof cssSelector === 'undefined') {
                let namespace = this.getDataNamespace();
                cssSelector = `[data${namespace}-toggle="popover"]`;
            }
            options.sanitize = false;
            if (typeof bootstrap.Popover.VERSION !== 'undefined') {
                document.querySelectorAll(cssSelector)
                    .forEach(el => {
                        let popover = bootstrap.Popover.getInstance(el);
                        if (popover === null) {
                            new bootstrap.Popover(el, options);
                            // to not navigate on `a` anchor
                            el.onclick = (event => event.preventDefault());
                        }
                    });
                return;
            }
            if (typeof jQuery != 'undefined' && typeof jQuery.fn.tooltip.constructor.VERSION !== 'undefined') {
                jQuery(cssSelector).popover(options);
            }
        }
    }
    return module;
})(window.combos || {});
