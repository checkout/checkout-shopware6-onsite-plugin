import DomAccess from 'src/helper/dom-access.helper';
import { DATA_BAG_KEY } from './constants';

const createInput = (field, value) => {
    const input = document.createElement('input');
    input.setAttribute('type', 'hidden');
    input.setAttribute('name', field);
    input.setAttribute('value', value);

    return input;
};

export const createSourceIdInput = (sourceId) => {
    return createInput(`${DATA_BAG_KEY}[sourceId]`, sourceId);
};

export const createTokenInput = (token) => {
    return createInput(`${DATA_BAG_KEY}[token]`, token);
};

export const createShouldSaveSourceInput = (shouldSaveSource) => {
    return createInput(`${DATA_BAG_KEY}[shouldSaveSource]`, shouldSaveSource);
};

export const createSourceInput = (field, value) => {
    return createInput(`${DATA_BAG_KEY}[source][${field}]`, value);
};

export const getInputValue = (rootElement, elementId) => {
    const input = DomAccess.querySelector(rootElement, elementId, false);
    if (!input || !input.value) {
        throw new Error(`No ${elementId} found`);
    }

    return input.value;
};

/**
 * Using URL to load script into a page
 *
 * @param {string} src
 * @returns {Promise<unknown>}
 */
export const loadScript = (src) => {
    return new Promise((resolve, reject) => {
        // Create script
        const script = document.createElement('script');
        script.src = src;
        script.async = true;

        // Script event listener callbacks for load and error
        const onScriptLoad = () => {
            resolve();
        }

        const onScriptError = () => {
            cleanup();
            script.remove();

            reject(new Error(`Unable to load script ${src}`));
        }


        script.addEventListener('load', onScriptLoad);
        script.addEventListener('error', onScriptError);

        // Add script to document body
        document.body.appendChild(script);

        // Remove event listeners on cleanup
        const cleanup = () => {
            script.removeEventListener('load', onScriptLoad);
            script.removeEventListener('error', onScriptError);
        }
    });
}
