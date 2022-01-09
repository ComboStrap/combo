window.addEventListener('load', function () {

    let searchBox = document.getElementById("internal-search-box");
    let autoCompletionUlElement = searchBox.nextElementSibling;
    const popperInstance = Popper.createPopper(
        searchBox,
        autoCompletionUlElement,
        {
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

    searchBox.addEventListener("input", debounce(
        async function () {
            await buildAutoCompletionList(this)
        },
        500
    ));

    searchBox.addEventListener("blur", function (event) {
        let relatedTarget = event.relatedTarget;
        if (relatedTarget === null) {
            return;
        }
        let form = relatedTarget.closest("form");
        if (form === null) {
            return;
        }
        // Only if it's not a node of the search form
        // ie deleting show will prevent click navigation from a page list suggestion
        if (!form.classList.contains("search")) {
            autoCompletionUlElement.classList.remove("show");
        }
    });


let buildAutoCompletionList = async function (searchBox) {

    let searchTerm = searchBox.value;
    if (searchTerm.length < 3) {
        return;
    }
    let formData = new URLSearchParams();
    formData.append('call', 'combo-search');
    formData.append('q', searchTerm);
    let response = await fetch(DOKU_BASE + 'lib/exe/ajax.php',
        {
            method: "POST",
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
        });
    let data = await response.json();
    while (autoCompletionUlElement.firstChild) {
        autoCompletionUlElement.firstChild.remove()
    }
    autoCompletionUlElement.classList.add("show");
    popperInstance.update();
    for (let index in data) {
        if (!data.hasOwnProperty(index)) {
            continue;
        }
        let anchor = data[index];
        let li = document.createElement("li");
        li.classList.add("dropdown-item");
        li.setAttribute("tabindex", "0");
        li.innerHTML = anchor;
        autoCompletionUlElement.append(li);
    }

}

})
;
