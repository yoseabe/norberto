Shopware.Component.register('swp-refund-system-detail', () => import('./page/swp-refund-system-detail'));
Shopware.Component.register('swp-refund-system-list', () => import('./page/swp-refund-system-list'));

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('swp-refund-system', {
    type: 'plugin',
    name: 'RefundSystem',
    title: 'refundsystem.general.mainMenuEntry',
    description: '',
    color: '#42b8c5',
    icon: 'regular-shopping-bag',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        overview: {
            component: 'swp-refund-system-list',
            path: 'overview'
        },
        create: {
            component: 'swp-refund-system-detail',
            path: 'create',
            meta: {
                parentPath: 'swp.refund.system.overview'
            }
        },
        detail: {
            component: 'swp-refund-system-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'swp.refund.system.overview'
            },
            props: {
                default(route) {
                    return {
                        refundSystemId: route.params.id
                    };
                }
            }
        }
    },

    navigation: [{
        label: 'refundsystem.general.mainMenuEntry',
        path: 'swp.refund.system.overview',
        parent: 'sw-catalogue',
        icon: 'default-os-layers',
        color: '#22d93e'
    }]
});
