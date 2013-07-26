# CHANGELOG
* Resolved issue returning the appropriate error message in scenarios where amount is null or undefined
* Removed nested error handling reports where the Simplify Commerce PHP SDK's error messages would be compounded by sequential proceeding requests that also return validation errors via SimplifyJS
* Improved error handling capabilities based upon updated Simplify.com error handling documentation (see link)
    <https://www.simplify.com/commerce/docs/tutorial/index#errors>
* Added API key verification to check validity prior to processing any additional requests through the Simplify SDK
* Improved debugging capabilities for developers with addition of the debug console (appears only with in testing environments when sandbox API keys are in use)
* Status is now shown during processing rather than simply disabling the form's submit input element
* Variable amount functionality added allowing non-technical users to charge different amounts by altering a parameter in the URL's query string