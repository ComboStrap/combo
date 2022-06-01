

window.addEventListener('load', function () {


    let searchFunction = async function (searchTerm) {

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
        return response.json();

    }
    combos.searchBox
        .create("internal-search-box", searchFunction)
        .init();

});
