window.combos = (function (combos) {

    combos.searchBox = class SearchBox {

        debounceInterval = 500;
        debounceLeadingExecution = false;
        searchResultContainer;

        /**
         *
         * @param idSelector - an element id to select the input box
         * @param dataFunction - the data function that should return a content
         * @returns {combos.SearchBox}
         */
        static create(idSelector, dataFunction) {
            return new SearchBox(idSelector, dataFunction);
        }

        constructor(idSelector, dataFunction) {
            this.idSelector = idSelector;
            this.searchFunction = dataFunction;
        }

        setDebounceInterval(debounceInterval) {
            this.debounceInterval = debounceInterval;
            return this;
        }

        /**
         * In test in node, the execution should be immediate
         * This function permits to set the immediate execution
         * @param debounceLeadingExecution
         * @returns {Window.combos.SearchBox}
         */
        setDebounceLeadingExecution(debounceLeadingExecution) {
            this.debounceLeadingExecution = debounceLeadingExecution;
            return this;
        }

        /**
         * Permits to pass a specific popper
         * @param popper
         * @returns {Window.combos.SearchBox}
         */
        setPopper(popper) {
            this.popper = popper;
            return this;
        }

        getPopper() {
            if (typeof this.popper != 'undefined') {
                return this.popper;
            }
            if (typeof Popper != 'undefined') {
                return Popper
            }
            throw Error("Popper was not found");
        }

        init() {

            let searchBoxInstance = this;
            this.searchBoxElement = document.getElementById(this.idSelector);
            if (this.searchBoxElement === null) {
                throw Error(`The search box ${this.idSelector} was not found`);
            }
            this.searchResultContainer = document.createElement("ul");
            this.searchResultContainer.classList.add("dropdown-menu");
            this.searchBoxElement.insertAdjacentElement('afterend', this.searchResultContainer);


            this.popperInstance = this.getPopper().createPopper(
                this.searchBoxElement,
                this.searchResultContainer,
                {
                    placement: 'bottom',
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

            this.searchBoxElement.addEventListener("input", function () {
                combos.debounce(
                    async function () {
                        let searchTerm = searchBoxInstance.searchBoxElement.value;
                        await searchBoxInstance.buildAutoCompletionList(searchTerm)
                    },
                    searchBoxInstance.debounceInterval,
                    searchBoxInstance.debounceLeadingExecution
                )();
            });

            this.searchBoxElement.addEventListener("blur", function (event) {
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
                searchBoxInstance.hideAutoComplete()
            });


        }

        hideAutoComplete() {
            this.searchResultContainer.classList.remove("show");
            while (this.searchResultContainer.firstChild) {
                this.searchResultContainer.firstChild.remove()
            }
        }

        async buildAutoCompletionList(searchTerm) {

            if (searchTerm.length < 3) {
                return;
            }
            let data = await this.searchFunction(searchTerm);
            this.searchResultContainer.classList.add("show");
            for (let index in data) {
                if (!data.hasOwnProperty(index)) {
                    continue;
                }
                let anchor = data[index];
                let li = document.createElement("li");
                li.classList.add("dropdown-item");
                li.setAttribute("tabindex", "1");
                li.innerHTML = anchor;
                this.searchResultContainer.appendChild(li);
            }

            await this.popperInstance.update();
        }

    };

    return combos;

})(window.combos || {});

