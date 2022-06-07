import { DATA_BAG_KEY } from './constants';

const createInput = (field, value) => {
    const input = document.createElement('input');
    input.setAttribute('type', 'hidden');
    input.setAttribute('name', field);
    input.setAttribute('value', value);

    return input;
};

export const createTokenInput = (token) => {
    return createInput(`${DATA_BAG_KEY}[token]`, token);
};
