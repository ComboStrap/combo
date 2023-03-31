import {Popover} from "bootstrap";


/**
 * Popover enhances the title property of a html element
 *
 *
 */
export default class FluentPopOver {

    private readonly popoverRootHtmlElement: HTMLElement;
    private placement: Popover.Options['placement'] = 'left';

    private bootStrapPopOver: Popover | undefined;
    private content: string = '';
    private isHtml: boolean = true;
    private trigger: Popover.Options['trigger'] = 'focus';
    private dismissOnNextClick: boolean = true;
    private title: string = '';

    constructor(element: HTMLElement) {

        this.popoverRootHtmlElement = element;
    }

    getElement() {
        return this.popoverRootHtmlElement;
    }

    setPlacementToLeft() {
        this.placement = 'left';
        return this;
    }

    setPlacementToRight() {
        this.placement = 'right';
        return this;
    }

    setPlacementToAuto() {
        this.placement = 'auto';
        return this;
    }

    setPlacementToTop() {
        this.placement = 'top';
        return this;
    }

    setPlacementTBottom() {
        this.placement = 'bottom';
        return this;
    }

    setTriggerToClick() {
        this.trigger = 'click';
        return this;
    }


    public static createForElementWithId(id: string) {
        let elementById = document.getElementById(id);
        if (elementById === null) {
            throw new Error(`The element with the id (${id}) was not found. We can't create a popover.`)
        }
        return new FluentPopOver(elementById);
    }

    show() {

        this.build()
            .show();
        return this;

    }

    private build () {

        if (typeof this.bootStrapPopOver !== 'undefined') {
            return this.bootStrapPopOver;
        }


        /**
         * No need to use the global variable access mode (ie `bootstrap.Modal`)
         * It's created at build time
         */
        let bootStrapPopOver = Popover.getInstance(this.popoverRootHtmlElement);
        if (bootStrapPopOver !== null) {
            this.bootStrapPopOver = bootStrapPopOver;
            return this.bootStrapPopOver;
        }
        /**
         * The bootstrap popover function
         * can only be invoked when the body element has been defined
         */
        let options: Partial<Popover.Options> = {
            container: 'body'
        };

        /**
         * https://getbootstrap.com/docs/5.0/components/popovers/#dismiss-on-next-click
         */
        if (this.dismissOnNextClick) {
            options.trigger = 'focus';
            this.popoverRootHtmlElement.setAttribute('tabindex', '0');
        } else {
            options.trigger = this.trigger;
        }

        options.placement = this.placement;
        options.html = this.isHtml;
        options.content = this.content;
        options.title = this.title;

        let dataNamespace = this.getDataNamespace();
        debugger;
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-toggle`, 'popover');
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-trigger`, this.trigger);
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-placement`, String(this.placement));
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-html`, String(this.isHtml));
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-container`, 'body');
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-content`, this.content);

        this.bootStrapPopOver = new Popover(this.popoverRootHtmlElement, options);



        return this.bootStrapPopOver;

    };


    /**
     * From version 5, the data attributes got the `bs` namespace
     * Not used but good to keep if we need to create HTML
     */
    // noinspection JSUnusedLocalSymbols
    // @ts-ignore
    private getDataNamespace() {


        let defaultNamespace = "-bs";
        let bootstrapVersion = 5;

        if ('bootstrap' in window) {
            let bootstrap = window.bootstrap;
            if (typeof bootstrap.Popover.VERSION !== 'undefined') {
                bootstrapVersion = parseInt(bootstrap.Popover.VERSION.substring(0, 1), 10);
                if (bootstrapVersion < 5) {
                    return "";
                }
                return defaultNamespace;
            }
        }

        if ('jQuery' in window) {
            let jQuery = window.jQuery as JQueryStatic;
            // @ts-ignore
            let version = jQuery.fn.tooltip.constructor.VERSION;
            if (typeof version !== 'undefined') {
                bootstrapVersion = parseInt(version.substring(0, 1), 10);
                if (bootstrapVersion < 5) {
                    return "";
                }
                return defaultNamespace;
            }
        }

        return defaultNamespace;

    }

    setTextContent(htmlContent: string) {

        this.content = htmlContent;
        this.isHtml = false;
        return this;


    }

    setHtmlContent(htmlContent: string) {

        this.content = htmlContent;
        this.isHtml = true;
        return this;

    }

    setEnableDismissOnNextClick() {
        this.dismissOnNextClick = true;
        return this;
    }

    setDisableDismissOnNextClick() {
        this.dismissOnNextClick = false;
        return this;
    }

    setTitle(title: string) {
        this.title = title;
        return this;
    }
}
