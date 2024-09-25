import './refundsystem-doku-button.scss';
import template from './refundsystem-doku-button.html.twig';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Component } = Shopware;

/**
 * @status ready
 * @description The <u>doku-button</u> component replaces the standard html button or anchor element with a custom button
 * and a multitude of options.
 * @example-type dynamic
 * @component-example
 * <doku-button>
 *     Button
 * </doku-button>
 */
Component.register('refundsystem-doku-button', {
    template,

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    props: {
        variant: {
            type: String,
            required: false,
            default: '',
            validValues: ['primary', 'ghost', 'danger', 'ghost-danger', 'contrast', 'context', 'hidden'],
            validator(value) {
                if (!value.length) {
                    return true;
                }
                return ['primary', 'ghost', 'danger', 'ghost-danger', 'contrast', 'context', 'hidden'].includes(value);
            },
        },
        linken: {
            type: String,
            required: false,
            default: null,
        },
        linkde: {
            type: String,
            required: false,
            default: null,
        },
    },

    computed: {
        dokuLink() {
            const locale = Shopware.State.getters.adminLocaleLanguage;

            if (locale == 'de') {
                return (this.linkde) ? this.linkde : "none";
            } else {
                return (this.linken) ? this.linken : "none";
            }
        },

        dokuButtonClasses() {
            return {
                [`refundsystem-doku-button--${this.variant}`]: this.variant,
            };
        },
    }
});
