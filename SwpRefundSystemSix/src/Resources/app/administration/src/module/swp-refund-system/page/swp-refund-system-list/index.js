import template from './swp-refund-system-list.html.twig';

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

export default {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],

    data() {
        return {
            refundSystem: null,
            sortBy: 'name',
            sortDirection: 'ASC',
            isLoading: false,
            showDeleteModal: false,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {

        RefundSystemRepository() {
            return this.repositoryFactory.create('refund_system'); // <- muss wie die Tabelle heißen
        }
    },
    methods: {

        onInlineEditSave(promise, refundSystem) {
            return promise.then(() => {
                this.createNotificationSuccess({
                    title: Shopware.Snippet.tc('refundsystem.detail.titleSaveSuccess'),
                    message: Shopware.Snippet.tc('refundsystem.detail.messageSaveSuccess', 0, { name: refundSystem.name })
                });
            }).catch(() => {
                this.getList();
                this.createNotificationError({
                    title: Shopware.Snippet.tc('refundsystem-stream.detail.titleSaveError'),
                    message: Shopware.Snippet.tc('refundsystem.detail.messageSaveError' , 0, { name: refundSystem.name })
                });
            });
        },



        getList() {
            this.isLoading = true;
            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            const naturalSort = this.sortBy === 'createdAt';
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, naturalSort));

            return this.RefundSystemRepository.search(criteria, Shopware.Context.api).then((items) => {
                this.total = items.total;
                this.refundSystem = items;
                this.isLoading = false;

                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        updateTotal({ total }) {
            this.total = total;
        },

        getRefundSystemColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                inlineEdit: 'string',
                routerLink: 'swp.refund.system.detail',
                label: Shopware.Snippet.tc('refundsystem.list.columnName'),
                width: '15%',
                primary: true,

            }, {
                property: 'description',
                inlineEdit: 'string',
                label: Shopware.Snippet.tc('refundsystem.list.columnDescription'),
                width: '50%',

            }, {
                property: 'active',
                inlineEdit: 'boolean',
                label: Shopware.Snippet.tc('refundsystem.list.columnStatus'),
                width: '35%',
            }];
        }
    }
};

