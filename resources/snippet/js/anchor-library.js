/* global anchors */
document.addEventListener('DOMContentLoaded', () => {
    anchors.options = {
        placement: 'right',
        icon: '#',
        class: 'anchor-combo'
    };
    anchors
        .add(".outline-heading")
        .add("main section > h2")
        .add("main section > h3")
        .add("main section > h4")
        .add("main section > h5")
        .add("main section > h6")
});
