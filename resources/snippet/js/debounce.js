function debounce(callBack, interval, leadingExecution) {

    // the schedule identifier, if it's not null/undefined, a callBack function was scheduled
    let timerId;

    return function () {

        // Does the previous run has schedule a run
        let wasFunctionScheduled = (typeof timerId === 'number');

        // Delete the previous run (if timerId is null, it does nothing)
        clearTimeout(timerId);

        // Capture the environment (this and argument) and wraps the callback function
        let funcToDebounceThis = this, funcToDebounceArgs = arguments;
        let funcToSchedule = function () {

            // Reset/delete the schedule
            clearTimeout(timerId);
            timerId = null;

            // trailing execution happens at the end of the interval
            if (!leadingExecution) {
                // Call the original function with apply
                callBack.apply(funcToDebounceThis, funcToDebounceArgs);
            }

        }

        // Schedule a new execution at each execution
        timerId = setTimeout(funcToSchedule, interval);

        // Leading execution
        if (!wasFunctionScheduled && leadingExecution) callBack.apply(funcToDebounceThis, funcToDebounceArgs);

    }

}
