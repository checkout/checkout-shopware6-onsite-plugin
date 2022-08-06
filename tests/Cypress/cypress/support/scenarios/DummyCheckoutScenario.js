import storefrontLoginAction from '../actions/storefront/LoginAction';
import checkoutAction from '../actions/storefront/CheckoutAction';

class DummyCheckoutScenario {

    execute(login = true) {
        const userEmail = 'test@example.com';
        const userPwd = 'shopware';

        if (login) {
            storefrontLoginAction.login(userEmail, userPwd);
        }

        checkoutAction.addFirstProductToCart(1);

        checkoutAction.checkoutFromOffcanvas();

        checkoutAction.checkTermAndCondition();
    }
}

export default new DummyCheckoutScenario();
