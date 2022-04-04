import template from "./checkout-plugin-config-section-order-state.html.twig";
import {
    ORDER_STATE_SKIP,
    ORDER_TECHNICAL_NAME,
} from "../../../../constant/state-machine";

const { Component, Data, Context } = Shopware;
const { Criteria } = Data;

Component.register("checkout-plugin-config-section-order-state", {
    template,

    inject: ["repositoryFactory"],

    props: {
        value: {
            type: Object,
            required: false,
        },
    },

    data() {
        return {
            isLoading: false,
            config: {
                orderStateForPaidPayment: this.getConfigPropsValue("orderStateForPaidPayment", ORDER_STATE_SKIP),
                orderStateForFailedPayment: this.getConfigPropsValue("orderStateForFailedPayment", ORDER_STATE_SKIP),
                orderStateForAuthorizedPayment: this.getConfigPropsValue("orderStateForAuthorizedPayment", ORDER_STATE_SKIP),
                orderStateForVoidedPayment: this.getConfigPropsValue("orderStateForVoidedPayment", ORDER_STATE_SKIP),
            },
            orderOptions: [],
        };
    },

    computed: {
        stateMachineStateRepository() {
            return this.repositoryFactory.create("state_machine_state");
        },

        stateMachineStateCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(
                Criteria.equals(
                    "stateMachine.technicalName",
                    ORDER_TECHNICAL_NAME
                )
            );

            return criteria;
        },
    },

    watch: {
        config: {
            handler(configValue) {
                this.$emit("change", configValue);
            },
            deep: true,
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getOrderOptions();
        },

        // We need to use the getConfigPropsValue function to get the value from the config props.
        getConfigPropsValue(field, defaultValue = null) {
            if (!this.value) {
                return defaultValue;
            }

            return this.value[field] ?? defaultValue;
        },

        async getOrderOptions() {
            this.isLoading = true;
            try {
                // We init the state options with the default values
                const orderOptions = [
                    {
                        label: this.$t(
                            "checkout-payments.config.orderState.skipOptionOrder"
                        ),
                        value: ORDER_STATE_SKIP,
                    },
                ];

                const states = await this.stateMachineStateRepository.search(
                    this.stateMachineStateCriteria,
                    Context.api
                );

                states.forEach((state) => {
                    orderOptions.push({
                        label: state.name,
                        value: state.technicalName,
                    });
                });

                this.orderOptions = orderOptions;
            } catch {}

            this.isLoading = false;
        },
    },
});
