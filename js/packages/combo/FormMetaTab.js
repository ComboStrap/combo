import Logger from "./Logger";


export default class FormMetaTab {


    constructor(name) {
        this.name = name;
    }

    getName() {
        return this.name;
    }

    setWidth(width) {
        this.width = width;
    }

    /**
     * The width of the tab
     */
    getWidth() {
        return this.width;
    }

    static createFromJson(json) {
        if (!json.hasOwnProperty("name")) {
            Logger.getLogger().error("A name property is mandatory to create a tab and was not found in the json provided")
        }
        let name = json["name"];
        let tab = new FormMetaTab(name);
        for (let property in json) {
            if (!json.hasOwnProperty(property)) {
                continue;
            }
            let jsonValue = json[property];
            switch (property) {
                case "name":
                    continue;
                case "label":
                    tab.setLabel(jsonValue);
                    continue;
                case "width-field":
                    tab.setWidthField(jsonValue);
                    continue;
                case "width-label":
                    tab.setWidthLabel(jsonValue);
                    continue;
                default:
                    Logger.getLogger().error(`The property (${property}) of the tab (${name}) is unknown`);
            }
        }
        return tab;
    }

    setLabel(label) {
        this.label = label;
        return this;
    }

    getLabel(){
        if(this.label===undefined){
            return this.name;
        }
        return this.label;
    }

    setWidthField(width) {
        this.widthField = width;
        return this;
    }

    setWidthLabel(width) {
        this.widthLabel = width;
        return this;
    }

    getLabelWidth(){
        if(this.widthLabel===undefined){
            return 3;
        }
        return this.widthLabel;
    }

    getFieldWidth(){
        if(this.widthField===undefined){
            return 12 - this.getLabelWidth();
        }
        return this.widthField;
    }

    static createFromName(name) {
        return new FormMetaTab(name);
    }
}
