window.addEventListener("load", function(event) {

    jQuery("[data-hover-class]").hover(function(){
        const $element = jQuery(this);
        $element.toggleClass($element.attr("data-hover-class"));
    });

});
