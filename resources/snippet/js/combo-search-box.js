// noinspection ES6ConvertVarToLetConst
var combos = (function (combos) {

    combos.searchBox = class SearchBox {

        debounceInterval = 500;
        static create(selector, dataFunction) {
            return new SearchBox(selector, dataFunction);
        }

        constructor(selector, dataFunction) {
            this.selector = selector;
            this.searchFunction = dataFunction;
        }

        setDebounceInterval(debounceInterval){
            this.debounceInterval = debounceInterval;
        }

        init() {

            let searchBoxInstance = this;
            let searchBoxElement = document.getElementById(this.selector);
            let autoCompletionUlElement = searchBoxElement.nextElementSibling;
            const popperInstance = Popper.createPopper(
                searchBoxElement,
                autoCompletionUlElement,
                {
                    placement: 'bottom-start',
                    modifiers: [
                        {
                            name: 'offset', // to be below the box-shadow on focus
                            options: {
                                offset: [0, 4],
                            },
                        },
                    ]
                }
            );
            searchBoxElement.addEventListener("input", combos.debounce(
                async function () {
                    let searchTerm = searchBoxElement.value;
                    await buildAutoCompletionList(searchTerm)
                },
                searchBoxInstance.debounceInterval
            ));

            searchBoxElement.addEventListener("blur", function (event) {
                let relatedTarget = event.relatedTarget;
                // Only if it's not a node of the search form
                // ie deleting show will prevent click navigation from a page list suggestion
                if (relatedTarget !== null) {
                    let form = relatedTarget.closest("form");
                    if (form !== null) {
                        if (form.classList.contains("search")) {
                            return;
                        }
                    }
                }
                autoCompletionUlElement.classList.remove("show");
                while (autoCompletionUlElement.firstChild) {
                    autoCompletionUlElement.firstChild.remove()
                }
            });


            let buildAutoCompletionList = async function (searchTerm) {

                if (searchTerm.length < 3) {
                    return;
                }
                let data = await searchBoxInstance.searchFunction(searchTerm);
                autoCompletionUlElement.classList.add("show");
                await popperInstance.update();
                for (let index in data) {
                    if (!data.hasOwnProperty(index)) {
                        continue;
                    }
                    let anchor = data[index];
                    let li = document.createElement("li");
                    li.classList.add("dropdown-item");
                    li.setAttribute("tabindex", "1");
                    li.innerHTML = anchor;
                    autoCompletionUlElement.append(li);
                }

            }

        }

    };

    return combos;

})(combos || {});
