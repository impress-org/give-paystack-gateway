import { __ } from '@wordpress/i18n';
import logo from './images/logo.png';

let paystackPublicKey: string;

const paystackGateway = {
    id: 'paystack',
    initialize() {
        paystackPublicKey = this.settings.publicKey;
    },
    async beforeCreatePayment(values: any) {
        return {
            ...values,
        };
    },
    Fields() {
        return (
              <div style={{textAlign: 'center'}}>
                <img src={logo} alt="Paystack" style={{ maxWidth: '200px' }} />
                <br />
                <br />
                <p style={{fontSize: '0.9rem'}}>
                    <strong>{__('Make your donation quickly and securely with Paystack', 'give-paystack')}</strong>
                </p>
                <p style={{fontSize: '0.8rem'}}>
                    <strong>{__('How it works:', 'give-paystack')}</strong>{' '}
                    {__(
                        'A Paystack window will open after you click the Donate button where you can securely make your donation. You will then be brought back to this page to view your receipt. ',
                        'give-paystack'
                    )}
                </p>
            </div>
        );
    },
};

//@ts-ignore
window.givewp.gateways.register(paystackGateway);
