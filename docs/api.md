# API

For more details, please follow the provider's docs.

- [PL](https://imojeapi.docs.apiary.io/)
- [EN](https://imojeapieng.docs.apiary.io/)

`POST /{merchantId}/payment` does not require `customer.email`. `firstName` and `lastName` remain required. Direct transaction requests still require `customer.email`. Omitted enum fields on API responses hydrate to `null` instead of throwing.
