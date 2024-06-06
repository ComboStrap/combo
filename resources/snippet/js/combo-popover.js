
window.combos = (function (module) {
    module.popover = {
        getDataNamespace: function () {
            let defaultNamespace = "-bs";
            let bootstrapVersion = 5;
            if (typeof window.bootstrap.Popover.VERSION !== 'undefined') {
                bootstrapVersion = parseInt(window.bootstrap.Popover.VERSION.substr(0, 1), 10);
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
            if (typeof window.bootstrap.Popover.VERSION !== 'undefined') {
                document.querySelectorAll(cssSelector)
                    .forEach(el => {
                        let popover = window.bootstrap.Popover.getInstance(el);
                        if (popover === null) {
                            popover = new window.bootstrap.Popover(el, options);
                        }
                        el.onclick = (event) => {
                            const popoverOnClick = window.bootstrap.Popover.getInstance(el)
                            // to not navigate on `a` anchor
                            event.preventDefault();
                            // https://stackoverflow.com/a/70498530/297420
                            const areaListener = new AbortController();
                            // to dismiss the popover
                            document.addEventListener(
                                'mousedown',
                                event => {
                                    if (el.contains(event.target)) {
                                        return;
                                    }
                                    const rootPopoverDomId = el.getAttribute("aria-describedby");
                                    if (!rootPopoverDomId) {
                                        areaListener.abort();
                                        return;
                                    }
                                    const rootPopOverElement = document.getElementById(rootPopoverDomId);
                                    if (!rootPopOverElement) {
                                        areaListener.abort();
                                        return;
                                    }
                                    if (rootPopOverElement.contains(event.target)) {
                                        return;
                                    }
                                    popoverOnClick.hide();
                                    areaListener.abort();
                                },
                                { signal: areaListener.signal }
                            );
                        };


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
