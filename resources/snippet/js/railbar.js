window.addEventListener('load', async function () {

    let pageToolContainer = document.getElementById("page-tool");
    if (pageToolContainer === null) {
        throw Error("No page tool element found");
    }

    let formData = new URLSearchParams();
    formData.append('call', 'combo');
    formData.append('fetcher', 'railbar');
    formData.append('q', searchTerm);
    let response = await fetch(DOKU_BASE + 'lib/exe/ajax.php',
        {
            method: "POST",
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
        });
    let htmlFragment = await response.text();


    // Trim to never return a text node of whitespace as the result
    pageToolContainer.insertAdjacentHTML('beforeend', trim(htmlFragment));


});
