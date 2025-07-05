FawryPay Online Payments
Start accepting payments on your website and/or mobile apps through Fawry retail network, banking channels, cards, and mobile wallets.

Introduction 
FawryPay is Egypt’s top online payment processing technology service provider. It provides a collection of comprehensive solutions to fulfill the needs of the diverse range of merchants’ core businesses, products, and services nature enabling different payment methods. FawryPay enables both front-end and back-end technologies required to facilitate seamless integration with the wide range of our merchants’ applications.

Depending on the specific needs of your core business, you have the flexibility to choose from our FawryPay’s online payment methods and integration solutions. With our online payment solutions, you can:

Designate your preferred payment methods based on your products or services nature.
Choose your best fit integration method based on your application platform.
Choose between our readily available FawryPay checkout experience or customize your own.
Our online payments solutions also work with other products in FawryPay platform like Tokenization, for example to support recurring payments. FawryPay online payments solutions work across devices and support a wide range of online applications.

Choose Your Best-Fit Integration Solution
FawryPay offers a wide range of integration options to satisfy the different needs of our valued merchants. Our integration options provide UI/UX elements for all of our supported payment methods: from collecting your client’s checkout information information to performing additional actions like presenting a FawryPay reference number that clients can use to pay at any Fawry retail network as per their convenience or to pay it out from their mobile wallets.

Depending on your specific application nature, select one of our readily available integration options below:

Express Checkout
express checkout integration overview
Checkout Button or Redirection Link to start accepting payments; the minimum friction integration solution.

Rest APIs
REST APIs overview
Use our APIs and take full control of your UI/UX elements; customize your own user experience.

SDK Integration
SDK integration overview
Obtain Payment handler components in your mobile app native language; the most risk and hassle free payment solution for mobile apps.

Integration Solutions Comparison
Compare between our readily available integration options. Learn about what is offered at every specific integration solution and know when to decide on a specific one.
Integration Solution	What is offered?	When to decide on this?
Express Checkout	Choose between our Checkout Button and Checkout link for seamless and quick integration. Checkout button facilitates your checkout session with all needed UI elements to create a checkout payment form hosted on your domain. Meanwhile, checkout link integration redirects your clients to a FawryPay-hosted checkout page. With Express Checkout you can:
Create your own review and checkout form.
Insert our Checkout Button OR make your own button to redirect clients to our checkout link.
Get a payment form with your business display name.
Get a comprehensive charge response at your pre-configured notification URL.
If you have your own Web Application and looking for an integration option with minimum friction.
If You're unable to host a payment form, or your business accepts purchases through email or call center orders.
REST APIs	Use our set of comprehensive APIs and then build your own UI for each payment method. With our server-side APIs you can:
Take full control of checkout UI/UX elements.
Design your client’s payment options selection form.
Get notified once the status of any of your transactions has been updated.
Get transaction status whenever needed.
You want to develop a fully customized payments form, with full UI/UX control. You want to design your own checkout forms and display your preferred payment methods to your clients.
SDK Integration	With a secure, yet flexible approach to mobile payment software development and an array of compatible device options, FawryPay Mobile SDK is the right solution to enable different payment methods for your mobile applications. With our Mobile SDKs you can:
Integrate your application with FawryPay payment processing platform in your native application programming language, Android or iOS.
Obtain necessary native UI elements to collect your clients and transactions information.
Eliminate the need for developers to code multiple card types handlers.
Obtain native charge response handlers.
You need to collect payments through your native mobile application, Android or iOS. You need to eliminate the time and cost of implementing a specific handler for each possible payment method.

INTEGRATION GUIDE: 
Build your payments integration with FawryPay
FawryPay is your one stop shop online payment platform. We empower partners with multiple payment methods across their channels.

How would you like to integrate?

Online Payments
FawryPay gives your buyers a simplified and secure checkout experience that keeps them local to your website or mobile app throughout the payment process.


Plugins
We offer wide range of plugins for leading technology solutions to give your clients the best checkout experience.

FawryPay Plugins
FawryPay empowers partners with payments plugins for leading e-commerce technology solutions.

We offer payments plugins for the most leading e-commerce technology solutions, WooCommerce and Shopify, and Magento 2. Our plugins are designed to give your clients the best checkout experience. Our plugins are easy to integrate and come with FawryPay payments platform functionality delivered out of the box.

How would you like to integrate?

WooCommerce
Use WordPress plugins and start accepting payments.


Shopify
Use FawryPay's plugin and accept payments in your Shopify store.


Magento 2
Use FawryPay's plugin and accept payments in your Magento 2 store.


END TO END TESTING

End-to-End Testing
introduction 
Genuine card numbers will produce an error in your test environment. To simulate payments, use any of the following test card details provided in the table below.

Note: Test cards only work in staging environment, and not in live environment. Similarly, you cannot use a real card while your site is in staging environment.

Check Payment Result at FawryPay Dashboard
You can always check the result of any testing payment in your FawryPay dashboard by checking reports and orders

Card Schemes
Card type	Number	CVV	Expiry Date	Card Holder	Description
Visa	4242 4242 4242 4242	123	05/25	Fawry Test	This card will always return successful payment
Visa	4263 9700 0000 5262	123	05/25	Fawry Test	This card will always return failure due invalid expiry date
Visa	4222 0000 0672 4235	123	05/25	Fawry Test	This card will always return failure due to insufficient fund
Mastercard	5436 0310 3060 6378	123	05/25	Fawry Test	This card will always return successful payment
Mastercard	5425 2300 0000 4415	123	05/25	Fawry Test	This card will always return failure due invalid expiry date
Mastercard	5114 6100 0000 4778	123	05/25	Fawry Test	This card will always return failure due to insufficient fund
Meeza	5078 0362 4660 0381	123	02/25	Fawry Test	This card will always return successful payment
Meeza	5078 0362 4278 3546	123	02/25	Fawry Test	This card will always return failure due to invalid expiry date
Meeza	5078 0362 3198 5581	123	02/25	Fawry Test	This card will always return failure due to insufficient fund
In Staging environment, the aforementioned cards are the ONLY accepted cards, other cards will be rejected

Please don’t forget to switch to Live environment when you’re ready to start accepting real payments.

Was this page helpful?
Yes No
Thank you for helping improve FawryPay's documentation. If you need help or have any questions, please consider contacting support.

At this point, you have successfully integrated FawryPay checkout button. It remains to learn how to handle FawryPay Checkout Plugin Response.

Handling Charge Response
Whether you choose to go with FawryPay Checkout Button or Checkout Link integration solutions, you will end up with sending us your charge request. The exact details of the response of our FawryPay plugin response are explained in this page. Two possible outcomes are to happen whenever a client issue a payment through FawryPay checkout form, Successful or Failed payment process.

Successful Payment Response
In case of successful payment process, FawryPay checkout plugin shall redirect the user to the merchant’s defined return URL associated with the charge request (Learn More) with the following sample response at the header of the redirected page:

https://returnUrl/?type=ChargeResponse&referenceNumber=963797667&
merchantRefNumber=555222666777&orderAmount=703&paymentAmount=706&fawryFees=3&
expirationTime=1608381812142&customerName=customer%20name&customerProfileId=1212&
signature=a4a46ca0ef5eea44aa985362d370e61081b55183ed718ccfaccba30a0c9ecbba&statusCode=200&
statusDescription=Operation%20done%20successfully
HTTP
Illustration of the URL parameters:

{
     "type": "ChargeResponse",
     "referenceNumber": "963455678",
     "merchantRefNumber": "9990d0642040",
     "orderAmount": 20.00,
     "paymentAmount": 20.00,
     "fawryFees": 1.00,
     "paymentMethod": "CARD",
     "orderStatus": "PAID",
     "paymentTime": 1607879720568,
     "customerProfileId": "1212",
     "signature": "b0ef178e2f06b215b18cfc7d82fb5d1f7b95dfcc91e33f8a6ce1e1251fdd04ec",
     "statusCode": 200,
     "statusDescription": "Operation done successfully"
 }
JSON
Detailed Description of Charge Response Parameters

Arguments	type	Description	example
type	String	Type of response.	ChargeResponse
referenceNumber	String	FawryPay issued transaction reference number.	963455678
merchantRefNumber	String	Merchant issued transaction reference number. This is the same as the reference number you have set in your charge request.	9990d0642040
orderAmount	Decimal	Order amount in two decimal places format.	20.00
paymentAmount	Decimal	The paid amount in two decimal places format.	20.00
fawryFees	Decimal	The payment processing fees.	1.00
paymentMethod	String	Payment Method Selected by your client.	'CashOnDelivery', 'PayAtFawry', 'MWALLET', 'CARD' or 'VALU'
orderStatus	String	Order Status.	PAID
paymentTime	Integer	Timestamp to record when the payment has been processed.	1607879720568
customerMobile	String	Customer Mobile Number.	01234567891
customerMail	String	Customer E-mail address.	example@email.com
customerProfileId	String	Customer Profile ID in the merchant's system.	1212
signature	String	Response Signature generated as the SHA-256 of the following concatenated string (referenceNumber (if exist) + merchantRefNum + paymentAmount (in two decimal places format 10.00) + orderAmount (in two decimal places format 10.00) + orderStatus + paymentMethod + fawryFees (if exist) (in two decimal places format 10.00)) + shippingFees (if exist) (in two decimal places format 10.00)) + authNumber (if exists) + customerMail (if exist) + customerMobile (if exist) + secureKey)	2df2943c6704176809ba6d559e2906b3d4df14916d6
statusCode	String	Response status code.	200
statusDescription	String	Response status description.	Operation done successfully
Failed Payment Response
In case of failed payment process, FawryPay checkout plugin shall redirect the user to the merchant’s return URL, as defined in the charge request, URL with only the status code and description at the header of the redirected page:

https://returnUrl/?type=ChargeResponse&statusCode=9946&statusDescription=Blank%20or%20invalid%20signature
HTTP
Illustration of the URL parameters:

{
     "type": "ChargeResponse",
     "statusCode": 9946,
     "statusDescription": "Blank or invalid signature"
 }
JSON
