# Paymob and IBAN Setup

## What each thing is

`IBAN`
Your Egyptian bank account identifier. Customers use this only for a manual bank transfer.

`Paymob`
Your payment gateway. It lets customers pay by card, wallet, and other enabled methods through Paymob's checkout.

These are different:

- `IBAN` tells the customer where to send a bank transfer.
- `Paymob keys` let your app talk to Paymob's API.

## What you can do right now

If you only have a bank account:

1. Fill in `BANK_TRANSFER_BANK_NAME`
2. Fill in `BANK_TRANSFER_ACCOUNT_NAME`
3. Fill in `BANK_TRANSFER_IBAN`
4. Optionally fill in `BANK_TRANSFER_ACCOUNT_NUMBER`, `BANK_TRANSFER_SWIFT`, and `BANK_TRANSFER_BRANCH`

That is enough for the app to show a Bank Transfer payment option.

## What you need for Paymob later

When your Paymob merchant account is ready, fill in:

- `PAYMOB_ENABLED=true`
- `PAYMOB_MODE=test` or `live`
- `PAYMOB_PUBLIC_KEY`
- `PAYMOB_SECRET_KEY`
- `PAYMOB_HMAC_SECRET`
- `PAYMOB_CARD_INTEGRATION_ID`
- `PAYMOB_WALLET_INTEGRATION_ID` if you want wallet payments
- `PAYMOB_IFRAME_ID` if your Paymob flow uses one

## Environment variables

The app already includes placeholders in:

- [.env.example](/Users/davidgeorge/Documents/Medcapsule-online/.env.example)
- [.env.production.example](/Users/davidgeorge/Documents/Medcapsule-online/.env.production.example)

## Recommended first launch path in Egypt

Use this order:

1. `Bank Transfer` with your `IBAN`
2. `InstaPay`
3. `Vodafone Cash`
4. `Fawry`
5. `Paymob` after your merchant keys are issued

This gives you a working local payment stack immediately while keeping the gateway integration path open.
