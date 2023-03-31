import Html from "./Html";
import {Popover} from "bootstrap";

export default class FluentPopOver {

    private readonly popoverRootHtmlElement: HTMLDivElement;
    private placement: string = 'left';
    private isBuild: Boolean = false;
    private bootStrapPopOver: Popover;

    constructor(id: string) {

        this.popoverRootHtmlElement = document.createElement("div");
        this.popoverRootHtmlElement.setAttribute("id", id);

        /**
         * No need to use the global variable access mode (ie `bootstrap.Modal`)
         * It's created at build time
         */
        let bootStrapPopOver = Popover.getInstance(this.popoverRootHtmlElement);
        if (bootStrapPopOver !== null) {
            this.bootStrapPopOver = bootStrapPopOver;
        } else {
            /**
             * The bootstrap popover function
             * can only be invoked when the body element has been defined
             */
            let options = {};
            this.bootStrapPopOver = new Popover(this.popoverRootHtmlElement, options);
        }
    }

    getElement() {
        return this.popoverRootHtmlElement;
    }

    setPlacementToLeft() {
        this.placement = 'left';
        return
    }

    static createTemporaryWithPrefix(prefix: string) {
        return this.createFromId(Html.createRandomIdWithPrefix(prefix));
    }

    private static createFromId(id: string) {
        return new FluentPopOver(id);
    }

    show() {

        if (!this.isBuild) {
            this.build();
        }
        this.bootStrapPopOver.show();

    }

    private build = () => {
        if (this.isBuild) {
            return this;
        }
        this.isBuild = true;

        let dataNamespace = this.getDataNamespace();
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-toggle`, 'popover');
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-placement`, this.placement);
        this.popoverRootHtmlElement.setAttribute(`data${dataNamespace}-html`, 'true');

        document.body.appendChild(this.popoverRootHtmlElement);
        return this;
    };

    /**
     * From version 5, the data attributes got the `bs` namespace
     */
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
}
