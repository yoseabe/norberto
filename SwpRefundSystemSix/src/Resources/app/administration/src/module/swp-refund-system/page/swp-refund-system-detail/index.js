import template from './swp-refund-system-detail.html.twig';

const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { Mixin, StateDeprecated } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('placeholder'),
        Mixin.getByName('notification')
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel'
    },

    props: {
        refundSystemId: {
            type: String,
            required: false,
            default: null
        }
    },


    data() {
        return {
            refundSystem: null,
            isLoading: false,
            isSaveSuccessful: false,
            media: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier)
        };
    },

    computed: {
        identifier() {
            return this.placeholder(this.refundSystem, 'name');
        },

        refundSystemIsLoading() {
            return this.isLoading || this.refundSystem == null;
        },

        refundSystemRepository() {
            return this.repositoryFactory.create('refund_system');
        },

        tooltipSave() {
            const systemKey = this.$device.getSystemKey();

            return {
                message: `${systemKey} + S`,
                appearance: 'light'
            };
        },

        tooltipCancel() {
            return {
                message: 'ESC',
                appearance: 'light'
            };
        },

        ...mapPropertyErrors('refundSystem', ['name']),

        // media

        getDefaultFolder() {
            return this.refundSystem.getEntityName();
        },

        mediaItem() {
            if(this.refundSystem)
            {
                if(this.refundSystem.mediaId && !this.media)
                {
                    this.mediaRepository.get(this.refundSystem.mediaId).then((media) => {
                        this.media = media;
                    });
                }
            }
            return (this.media) ? this.media : null;
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },
    },

    watch: {
        refundSystemId() {
            this.createdComponent();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        abortOnLanguageChange() {
            return Shopware.State.getters['swpRefundSystem/hasChanges'];
        },

        saveOnLanguageChange() {
            return this.onSave();
        },

        onChangeLanguage(languageId) {

                Shopware.State.commit('context/setApiLanguageId', languageId)
        },


        createdComponent() {

            if (!this.refundSystemId) {
                if (Shopware.Context.api.languageId !== Shopware.Context.api.systemLanguageId) {
                    Shopware.State.commit('context/setApiLanguageId', Shopware.Context.api.systemLanguageId);
                }

            }

            if (this.refundSystemId) {
                this.loadEntityData();
                return;
            }
            this.refundSystem = this.refundSystemRepository.create(Shopware.Context.api);
        },

        loadEntityData() {
            this.isLoading = true;

            this.refundSystemRepository.get(this.refundSystemId, Shopware.Context.api).then((refundSystem) => {
                this.isLoading = false;
                this.refundSystem = refundSystem;
            });
        },

        onSave() {
            this.isLoading = true;

            this.refundSystemRepository.save(this.refundSystem, Shopware.Context.api).then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;

                const refundSystemName = this.refundSystem.name;

                this.createNotificationSuccess({
                    title: Shopware.Snippet.tc('refundsystem.detail.titleSaveSuccess'),
                    message: Shopware.Snippet.tc(
                        'refundsystem.detail.messageSaveSuccess', 0, { name: refundSystemName }
                    )
                });

                if (this.refundSystemId === null) {
                    this.$router.push({ name: 'swp.refund.system.detail', params: { id: this.refundSystem.id } });
                    return;
                }

                this.loadEntityData();

            }).catch((exception) => {
                this.isLoading = false;
                const refundSystemName = this.refundSystem.name;
                this.createNotificationError({
                    title: Shopware.Snippet.tc('global.default.error'),
                    message: Shopware.Snippet.tc(
                        'global.notification.notificationSaveErrorMessage', 0, { entityName: refundSystemName }
                    )
                });
                throw exception;
            });
        },

        onCancel() {
            this.$router.push({ name: 'swp.refund.system.overview' });
        },

        //media

        onSetMediaItem({ targetId }) {
            this.mediaRepository.get(targetId).then((updatedMedia) => {
                this.refundSystem.mediaId = targetId;
                this.media = updatedMedia;
            });
        },

        onRemoveMediaItem() {
            this.refundSystem.mediaId = null;
            this.media = null;
        },

        onMediaDropped(dropItem) {
            // to be consistent refetch entity with repository
            this.onSetMediaItem({ targetId: dropItem.id });
        },
    }
};
