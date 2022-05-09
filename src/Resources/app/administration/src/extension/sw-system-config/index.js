import { CHECKOUT_DOMAIN } from '../../constant/settings';

const { Component } = Shopware;

/**
 * Fetch the latest config data to make sure the data shown on the plugin page is correctly
 */
Component.override('sw-system-config', {
    computed: {
        isCheckoutDomain() {
            return this.domain === CHECKOUT_DOMAIN;
        },
    },

    methods: {
        async saveAll() {
            if (!this.isCheckoutDomain) {
                return this.$super('saveAll');
            }

            const saveAllResponse = await this.$super('saveAll');

            // Fetch new configuration values
            await this.loadCurrentSalesChannelConfig();

            return saveAllResponse;
        },

        getElementBind(element, mapInheritance) {
            const bind = this.$super('getElementBind', element, mapInheritance);

            if (this.isCheckoutDomain && bind && bind.hasOwnProperty('config')) {
                bind.config.inheritedValue = this.getInheritedValue(element);
                bind.config.actualConfigData = this.actualConfigData[this.currentSalesChannelId][element.name];
                bind.config.isNotDefaultSalesChannel = this.isNotDefaultSalesChannel;
            }

            return bind;
        },
    },
});
