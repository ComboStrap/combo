window.addEventListener('load', function () {

    let getSuggestedPagesAsAnchor = async function (searchTerm) {

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
        let htmlSuggestedPages = await response.json();
        if (!Array.isArray(htmlSuggestedPages)) {
            throw Error("The received suggest pages are not in a array format");
        }
        let divContainer = document.createElement('div');
        for (let suggestPage of htmlSuggestedPages) {
            // Trim to never return a text node of whitespace as the result
            divContainer.insertAdjacentHTML('beforeend',suggestPage.trim())
        }
        return [...divContainer.childNodes];

    }
    combos.searchBox
        .create("internal-search-box", getSuggestedPagesAsAnchor)
        .init();

});
