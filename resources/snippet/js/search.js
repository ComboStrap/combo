window.addEventListener('load', function () {

    let searchBox = document.getElementById("internal-search-box");


    searchBox.addEventListener("input", debounce(
        async function () {
            await buildAutoCompletionList(this)
        },
        500
    ));


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
        debugger;
        let autoCompletionUlElement = searchBox.nextElementSibling;
        while (autoCompletionUlElement.firstChild) {
            autoCompletionUlElement.firstChild.remove()
        }
        for (let id in data) {
            if(!data.hasOwnProperty(id)){
                continue;
            }
            let title = data[id];
            let li = document.createElement("li");
            li.classList.add("dropdown-item");
            li.setAttribute("tabindex","0");
            li.innerText = `${id} - ${title}`;
            autoCompletionUlElement.append(li);
        }

    }

});
