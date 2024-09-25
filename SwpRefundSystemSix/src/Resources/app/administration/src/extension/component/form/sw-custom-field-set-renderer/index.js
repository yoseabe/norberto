import template from './sw-custom-field-set-renderer.html.twig'
import './sw-custom-field-set-renderer.scss'

import deDE from '../snippet/de-DE.json'
import enGB from '../snippet/en-GB.json'

const { Component } = Shopware;

Component.override('sw-custom-field-set-renderer', {
    template,

    inject: {
        /** @var {RefundSystemApiService} RefundSystemApiService */
        refundsystemtoproductsService: 'refundsystemtoproductsService',
        repositoryFactory: 'repositoryFactory',
        acl: 'acl'
    },

    data() {
        return {
            showRefundSystemSixModal: false,
        };
    },

    computed: {
        categoryRepository() {
            return this.repositoryFactory.create('category');
        },
        categoryNotSaved() {
            return this.entity && this.categoryRepository.hasChanges(this.entity);
        }
    },

    methods: {
        showProcessRefundSystem(active) {
            console.log(active);
            const activeSetName = this.sets.filter((set) => {
                return set.id === active;
            }).first().name;

            return (this.$route.name === 'sw.category.detail.base'
                && activeSetName === 'refund_system');
        },

        openProcessRefundSystemSixModal(){
            this.showRefundSystemSixModal = true;
        },

        closeRefundSystemSixModal() {
            this.showRefundSystemSixModal = false;
        },

        saveRefundSystemSix() {
            const categoryObj = Shopware.State.get('swCategoryDetail').category;

            this.refundsystemtoproductsService.processRefundSystem(
                categoryObj.id,
                'refund_system'
            ).then((res) => {
                this.showRefundSystemSixModal = false;
            });
        },
    }
});
