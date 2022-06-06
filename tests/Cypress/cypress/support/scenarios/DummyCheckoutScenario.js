import storefrontLoginAction from '../actions/storefront/LoginAction';
import checkoutAction from '../actions/storefront/CheckoutAction';

class DummyCheckoutScenario {

    execute() {
        const userEmail = 'test@example.com';
        const userPwd = 'shopware';

        storefrontLoginAction.login(userEmail, userPwd);

        checkoutAction.addFirstProductToCart(1);

        checkoutAction.checkoutFromOffcanvas();

        checkoutAction.checkTermAndCondition();
    }
}

export default new DummyCheckoutScenario();
