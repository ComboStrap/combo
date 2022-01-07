import Browser from "../../library/combo/Browser";

window.addEventListener('load', function () {

    let searchBox = document.getElementById("internal-search-box");

    searchBox.addEventListener("input", function () {

        debounce(
            function () {
                rebuildAutoCompletionList(this)
            },
            50
        );
    });

    let rebuildAutoCompletionList = function (searchBox) {

        let searchTerm = searchBox.value;
        fetch(DOKU_BASE + 'lib/exe/ajax.php',
            {
                method: "POST",
                body: JSON.stringify(Browser.formDataToObject(formData)),
                headers: {
                    'Content-Type': 'application/json'
                },
            });

    }

});
