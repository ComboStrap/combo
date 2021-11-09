
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
        /**
         * Removed by parcel when build
         * https://parceljs.org/features/production/#development-branch-removal
         * And set by Jest to test
         * https://jestjs.io/docs/environment-variables#node_env
         */
        if (process.env.NODE_ENV !== 'production') {
            throw new Error(value);
        }
    }


}
