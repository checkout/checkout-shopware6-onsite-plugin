import template from './checkout-com-media-compact-upload-v2.html.twig';
import './checkout-com-media-compact-upload-v2.scss';

const { Component } = Shopware;

Component.extend(
    'checkout-com-media-compact-upload-v2',
    'sw-media-compact-upload-v2',
    {
        template,

        props: {
            mediaIsPrivate: {
                type: Boolean,
                required: false,
                default: false,
            },
        },
    }
);
