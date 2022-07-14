/* global bootstrap */
// noinspection ES6ConvertVarToLetConst
window.combos = (function (module) {


    let cloneScriptElement = function (element) {
        let clone = document.createElement(element.localName);
        clone.text = element.text
        Array.from(element.attributes).forEach(attr => clone.setAttribute(attr.name, attr.value));
        return clone;
    }

    let executeInlineScript = function (targetElement) {
        let inlineScriptElements = Array.from(targetElement.querySelectorAll("script:not([src])"));
        let executionsError = [];
        for (const scriptElement of inlineScriptElements) {
            try {
                eval(scriptElement.text);
            } catch (e) {
                executionsError.push({"error": e, "element": scriptElement});
            }
        }
        if (executionsError.length > 0) {
            let msg = "";
            for (const error of executionsError) {
                msg += `Railbar error: the script element (${error.element.className}) returns the following error ${error.error.message}\n`;
            }
            throw Error(msg);
        }
    }

    module.html = {

        /**
         * Load a html fragment and executes the script inside if any
         * @param htmlFragment
         * @param targetElement
         */
        "loadFragment": function (htmlFragment, targetElement) {

            // Trim to never return a text node of whitespace as the result
            targetElement.insertAdjacentHTML('beforeend', htmlFragment.trim());

            // Execute the src scripts first (the inline script may depend on it)
            let srcScriptElements = Array.from(targetElement.querySelectorAll("script[src]"));

            // no src script, load all inline and return
            if (srcScriptElements.length === 0) {
                executeInlineScript(targetElement);
                return;
            }

            // We have src script, we need to load all of them, then load all inline and return

            // The loader manager job is to known when all src script have loaded (script with an url)
            //
            // It knows how many script need to be loaded and decrease a counter when it happens.
            // When the counter is null, it will dispatch the all-module-loaded event that will trigger
            // the execution of inline script
            let allModuleLoadedEventType = 'all-module-loaded';
            const allModuleLoaded = new Event(allModuleLoadedEventType);
            let loaderManager = (function (scriptToLoadCount) {
                let loadModule = {};
                let moduleToLoad = scriptToLoadCount;
                // this function is called back when a script src is loaded
                loadModule.decrease = function () {
                    moduleToLoad--;
                    if (moduleToLoad <= 0) {
                        targetElement.dispatchEvent(allModuleLoaded);
                    }
                }
                return loadModule;
            })(srcScriptElements.length);

            // Load all srcScript Element and add the decrease function has callback
            for (const scriptElement of srcScriptElements) {
                let clone = cloneScriptElement(scriptElement);
                // noinspection JSCheckFunctionSignatures
                scriptElement.parentNode.replaceChild(clone, scriptElement);
                clone.addEventListener("load", loaderManager.decrease)
            }

            // Evaluate all inline scripts when all src script have loaded
            // (ie the loader manager dispatch this event when all scripts have loaded)
            targetElement.addEventListener(allModuleLoadedEventType, function () {
                executeInlineScript(targetElement);
            })

        }
    }
    return module;
})(window.combos || {});
