/**
 * Testing the combo snippets
 */
test('Carrousel', () => {

    let carrouselClass = "carrousel-combo";
    const fs = require('fs');
    let script = fs.readFileSync('./resources/snippet/js/carrousel.js').toLocaleString();
    const jsdom = require("jsdom");
    const {JSDOM} = jsdom;
    let domOptions = {
        runScripts: "dangerously",
        pretendToBeVisual: true
    };
    let html = `
<!DOCTYPE html>
<div class="${carrouselClass}">
</div>
<script>${script}</script>`;
    const dom = new JSDOM(html, domOptions);
    let document = dom.window.document;
    document.addEventListener('DOMContentLoaded', () => {
        let selectDiv = document.querySelector(`.${carrouselClass}`);
        expect(selectDiv).toHaveClass("glide", "glide--ltr", "glide--swipeable");
        console.log(selectDiv.outerHTML);
    });


});
