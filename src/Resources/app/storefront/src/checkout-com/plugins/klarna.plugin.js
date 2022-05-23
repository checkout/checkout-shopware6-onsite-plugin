import Plugin from 'src/plugin-system/plugin.class';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import StoreApiClient from 'src/service/store-api-client.service';
import { createTokenInput } from '../helper/utils';

/**
 * This Class is responsible for the Klarna integration
 */
export default class CheckoutComKlarna extends Plugin {
    static options = {
        klarnaCreditSessionsEndpoint: null,
        orderId: null,
        klarnaInstanceId: 'checkoutComKlarnaInstance',
        paymentFormId: '#confirmOrderForm',
        klarnaCheckoutContainerId: '#checkoutComKlarnaCheckoutContainer',
        klarnaWidgetErrorClass: '.checkout-com-klarna-widget-error',
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
        billingAddress: null,
    };

    init() {
        const {
            submitPaymentButtonId,
            paymentFormId,
            klarnaWidgetErrorClass,
        } = this.options;

        this.storeApiClient = new StoreApiClient();

        this.submitPaymentButton = DomAccess.querySelector(document, submitPaymentButtonId, false);
        this.submitButtonLoader = new ButtonLoadingIndicator(this.submitPaymentButton);
        this.paymentForm = DomAccess.querySelector(document, paymentFormId, false);
        this.klarnaWidgetErrorEl = DomAccess.querySelector(this.el, klarnaWidgetErrorClass, false);

        this.loadWidget();
    }

    loadWidget() {
        this.createPageLoading();
        this.initKlarnaSession().then((result) => {
            const {
                success,
                creditSession,
            } = result;

            if (!success || !creditSession) {
                this.klarnaWidgetErrorEl.classList.remove('d-none');
                return;
            }

            this.loadKlarnaWidget(creditSession);
        }).finally(this.removePageLoading);
    }

    initKlarnaSession() {
        const {
            klarnaCreditSessionsEndpoint,
            orderId,
        } = this.options;
        if (!klarnaCreditSessionsEndpoint) {
            throw new Error(`The "klarnaCreditSessionsEndpoint" method for the plugin "${this._pluginName}" is not defined.`);
        }

        return new Promise((resolve) => {
            this.storeApiClient.post(klarnaCreditSessionsEndpoint, JSON.stringify({
                orderId,
            }), (result) => {
                if (!result) {
                    resolve({});
                    return;
                }

                resolve(JSON.parse(result));
            });
        });
    }

    loadKlarnaWidget(creditSession) {
        const {
            klarnaCheckoutContainerId,
            klarnaInstanceId,
            billingAddress,
        } = this.options;

        const {
            client_token,
            payment_method_categories,
        } = creditSession;

        const paymentMethodIdentifies = payment_method_categories.map((paymentMethodCategory) => paymentMethodCategory.identifier);

        try {
            window.Klarna.Payments.init({
                client_token,
            });

            window.Klarna.Payments.load({
                container: klarnaCheckoutContainerId,
                instance_id: klarnaInstanceId,
                payment_method_categories: paymentMethodIdentifies,
            }, {
                billing_address: billingAddress,
            });

            this.klarnaWidgetErrorEl.remove();
            this.registerEvents();
        } catch {
            this.klarnaWidgetErrorEl.classList.remove('d-none');
        }
    }

    registerEvents() {
        // Submit payment form handler
        this.submitPaymentButton.addEventListener('click', this.onSubmitPayment.bind(this));
    }

    onSubmitPayment(event) {
        const {
            klarnaInstanceId,
            billingAddress,
        } = this.options;

        event.preventDefault();

        // checks form validity before submit
        if (!this.paymentForm.checkValidity()) {
            return;
        }

        this.createButtonLoading();

        window.Klarna.Payments.authorize({
            instance_id: klarnaInstanceId,
        }, {
            billing_address: billingAddress,
        }, this.onCardTokenized.bind(this));
    }

    onCardTokenized(res) {
        const {
            approved,
            authorization_token,
        } = res;

        if (!approved) {
            this.removeButtonLoading();
            return;
        }

        const input = createTokenInput(authorization_token);

        // Add the token input to the form
        // It will be sent to the server along with the form.
        this.paymentForm.append(input);

        // Continue to submit shopware payment form
        this.paymentForm.submit();
    }

    createButtonLoading() {
        this.submitButtonLoader.create();
    }

    removeButtonLoading() {
        this.submitButtonLoader.remove();
    }

    createPageLoading() {
        // Create page loading indicator
        PageLoadingIndicatorUtil.create();
    }

    removePageLoading() {
        // Remove page loading indicator
        PageLoadingIndicatorUtil.remove();
    }
}
