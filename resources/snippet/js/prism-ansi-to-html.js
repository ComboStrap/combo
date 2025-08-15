// Transform ANSI sequence in Prism Block into color
// https://github.com/drudru/ansi_up
window.addEventListener('load', () => {
    // It's a module wo we need to import it dynamically to use it in the browser (non-module)
    import('https://cdn.jsdelivr.net/npm/ansi_up@6.0.6/ansi_up.min.js').then(m => {
        AnsiUp = m.AnsiUp
        const ansi_up = new AnsiUp();

        // Note sure how does the
        // �: the replacement character () appears when Invalid UTF-8 sequences are encountered
        const elements = document.querySelectorAll('code.language-txt')
        for (const element of elements) {
            const ansiText = element.textContent.replace(/�/g, '\x1B')
            element.innerHTML = ansi_up.ansi_to_html(ansiText)
        }
    })
})
