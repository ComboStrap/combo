window.combos = (function (combos) {

    combos.searchBox = class SearchBox {

        debounceInterval = 500;
        debounceLeadingExecution = false;
        searchResultContainer;
        itemClass = `combo-search-box-item`;

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
            let elementSelected = document.getElementById(this.idSelector);
            if (elementSelected === null) {
                throw Error(`No element was found with the selector ${this.idSelector}`);
            }
            if (elementSelected instanceof HTMLInputElement) {
                this.searchBoxElement = elementSelected;
            } else {
                throw Error(`No search box input element found with the selector ${this.idSelector}`);
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

            /**
             * Build the list when typing
             */
            this.searchBoxElement.addEventListener("input",
                combos.debounce(
                    async function () {
                        let searchTerm = searchBoxInstance.searchBoxElement.value;
                        await searchBoxInstance.buildAutoCompletionList(searchTerm)
                    },
                    searchBoxInstance.debounceInterval,
                    searchBoxInstance.debounceLeadingExecution
                )
            );
            /**
             * Build the list in focus if there is any value already
             */
            this.searchBoxElement.addEventListener("focus",
                async function () {
                    let searchTerm = searchBoxInstance.searchBoxElement.value;
                    await searchBoxInstance.buildAutoCompletionList(searchTerm)
                }
            );

            this.searchBoxElement.addEventListener("blur", function (event) {
                searchBoxInstance.hideAutoComplete(event.relatedTarget);
            });


        }

        hideAutoComplete(relatedTarget) {
            // Only if it's not an item of the list
            // ie deleting the item will prevent click navigation from a page list suggestion
            if (relatedTarget !== null && relatedTarget instanceof Element) {
                // the target may be a link inside a list item
                let closestLi = relatedTarget.closest(`li`);
                if (closestLi != null && closestLi.classList.contains(this.itemClass)) {
                    return;
                }
            }
            this.searchResultContainer.classList.remove("show");
            while (this.searchResultContainer.firstChild) {
                this.searchResultContainer.firstChild.remove()
            }
        }

        async buildAutoCompletionList(searchTerm) {

            if (searchTerm.length < 3) {
                return;
            }
            this.hideAutoComplete();
            let data = await this.searchFunction(searchTerm);
            this.searchResultContainer.classList.add("show");
            let searchBoxInstance = this;
            for (let index in data) {
                if (!data.hasOwnProperty(index)) {
                    continue;
                }
                let anchor = data[index];
                let li = document.createElement("li");
                li.classList.add("dropdown-item");
                li.classList.add(this.itemClass);
                li.innerHTML = anchor;
                // Anchors are added in the tab order, no need to add tabindex - 1
                li.querySelectorAll("a").forEach(anchor => {
                    anchor.addEventListener("blur", function (event) {
                        searchBoxInstance.hideAutoComplete(event.relatedTarget);
                    });
                })
                this.searchResultContainer.appendChild(li);
            }

            await this.popperInstance.update();
        }

    };

    return combos;

})
(window.combos || {});

