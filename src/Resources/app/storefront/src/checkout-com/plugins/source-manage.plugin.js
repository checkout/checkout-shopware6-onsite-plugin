import Plugin from 'src/plugin-system/plugin.class';
import StoreApiClient from 'src/service/store-api-client.service';
import DomAccess from 'src/helper/dom-access.helper';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

/**
 * This plugin manage the source card of the customer
 */
export default class CheckoutComSourceManage extends Plugin {
    static options = {
        deleteSourceEndpoint: null,
        checkoutComSourceContainer: '.checkout-com-source',
        checkoutComDataSourceId: 'data-checkout-com-source-id',
        checkoutComRemoveSourceButton: '.checkout-com-remove-source-button',
    };

    init() {
        const { deleteSourceEndpoint } = this.options;
        if (!deleteSourceEndpoint) {
            throw new Error(`The "deleteSourceEndpoint" option for the plugin "${this._pluginName}" is not defined.`);
        }

        this.storeApiClient = new StoreApiClient();
        this.registerEvents();
    }

    registerEvents() {
        const {
            checkoutComRemoveSourceButton,
        } = this.options;
        const removeSourceButtons = DomAccess.querySelectorAll(document, checkoutComRemoveSourceButton, false);
        if (!removeSourceButtons) {
            return;
        }

        removeSourceButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();

                this.onRemoveSourceButtonClick(button);
            });
        });
    }

    onRemoveSourceButtonClick(button) {
        const {
            checkoutComSourceContainer,
            checkoutComDataSourceId,
        } = this.options;

        const sourceContainer = button.closest(checkoutComSourceContainer);
        if (!sourceContainer) {
            return;
        }

        const sourceId = sourceContainer.getAttribute(checkoutComDataSourceId);
        if (!sourceId) {
            return;
        }

        PageLoadingIndicatorUtil.create();
        this.deleteSource(sourceId).then(({ success }) => {
            PageLoadingIndicatorUtil.remove();
            if (!success) {
                return;
            }

            sourceContainer.remove();
        });
    }

    deleteSource(sourceId) {
        const {
            deleteSourceEndpoint,
        } = this.options;

        return new Promise((resolve) => {
            this.storeApiClient.delete(deleteSourceEndpoint, JSON.stringify({
                sourceId,
            }), (result) => {
                if (!result) {
                    resolve({ success: false });
                    return;
                }

                resolve(JSON.parse(result));
            });
        });
    }
}
