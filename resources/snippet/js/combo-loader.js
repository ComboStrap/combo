// noinspection ES6ConvertVarToLetConst
var combos = (function (module) {
    module.loader = {
        loadExternalScript: function (src, integrity, clazz, callback) {
            let script = document.createElement('script');
            script.src = src; // Set the location of the script
            script.integrity = integrity;
            script.crossOrigin = "anonymous";
            script.referrerPolicy = "no-referrer";
            script.classList.add(clazz);
            script.addEventListener("load", callback);
            let head = document.querySelector("head");
            head.appendChild(script);
        },
        loadExternalStylesheet: function (href, integrity, clazz, callback) {
            let link = document.createElement('link');
            link.rel = "stylesheet"
            link.href = href;
            link.integrity = integrity;
            link.crossOrigin = "anonymous";
            link.classList.add(clazz);
            let head = document.querySelector("head");
            head.appendChild(link);
            link.addEventListener("load", callback);
        }
    };
    return module;
})(combos || {});
