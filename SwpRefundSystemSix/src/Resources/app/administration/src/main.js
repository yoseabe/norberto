import './module/swp-refund-system';
import './extension/component/form/sw-custom-field-set-renderer';
import './app/component/custom-component/refundsystem-doku-button'
import RefundSystemApiService from "./core/service/refundsystem.api.service";

const { Application } = Shopware;

Application.addServiceProvider('refundsystemtoproductsService', (container) => {
    const initContainer = Application.getContainer('init');
    return new RefundSystemApiService(initContainer.httpClient, container.loginService);
});
