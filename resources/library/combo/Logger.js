
let logger;

// KISS for now
// Otherwise, see https://datacadamia.com/web/javascript/logger#library
export default class Logger {

    static getLogger(){
        if(logger===undefined){
            logger = new Logger();
        }
        return logger;
    }

    error(value){
        console.error(value);
        if (typeof jest !== 'undefined'){
            throw new Error(value);
        }
    }


}
