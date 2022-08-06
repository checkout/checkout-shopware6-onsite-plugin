import storefrontLoginAction from '../actions/storefront/LoginAction';
import checkoutAction from '../actions/storefront/CheckoutAction';

class DummyCheckoutScenario {

    execute(login = true, quantity = 1) {
        const userEmail = 'test@example.com';
        const userPwd = 'shopware';

        if (login) {
            storefrontLoginAction.login(userEmail, userPwd);
        }

        checkoutAction.addFirstProductToCart(quantity);

        checkoutAction.checkoutFromOffcanvas();

        checkoutAction.checkTermAndCondition();
    }
}

export default new DummyCheckoutScenario();
