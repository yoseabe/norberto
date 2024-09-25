const ApiService = Shopware.Classes.ApiService;

/**
 * Gateway for the API end point "refundsystemtoproducts"
 * @class
 * @extends ApiService
 */
class RefundSystemApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'refundsystemtoproducts') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'refundsystemtoproductsService';
    }

    processRefundSystem(categoryId, fieldset) {
        const apiRoute = `/_action/${this.getApiBasePath()}/processing`;

        return this.httpClient.post(
            apiRoute, {
                categoryId: categoryId,
                fieldset: fieldset
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}
export default RefundSystemApiService;
