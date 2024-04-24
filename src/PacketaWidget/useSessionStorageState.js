import {useCallback, useState} from "react";

export const useSessionStorageState = (key, initialState, forceInitialValue = false) => {
    const [storedValue, setStoredValue] = useState(() => {
        if (forceInitialValue) {
            return initialState;
        }

        try {
            const item = window.sessionStorage.getItem(key);
            return item ? JSON.parse(item) : initialState;
        } catch (error) {
            return initialState;
        }
    });

    const setValue = useCallback((value) => {
        try {
            if (value === null) {
                window.sessionStorage.removeItem(key);
            } else {
                window.sessionStorage.setItem(key, JSON.stringify(value));
            }
            setStoredValue(value);
        } catch (error) {
            console.log(error);
        }
    }, []);

    return [storedValue, setValue];
}
