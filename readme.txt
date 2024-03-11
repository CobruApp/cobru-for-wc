License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

# What is Cobru?
Cobru is a web platform made to quickly create and manage online transactions, by providing multiple payment methods both proprietary and third party.

The platform works by combining a Python/Django Backend, a React Native iOS/Android mobile application and some Vue.js web views.

## Cobru’s products
Cobru users have access to:

* Cobru App: The mobile app, where users can request payments (these are called Cobru) from others or pay Cobrus.
* Cobru web: A web page where anyone, even non-users, can pay Cobrus made by others.
* Cobru Panel: A premium web app made for company-wide management of Cobru creation and payment.

## Cobru’s API
Cobru’s API can by used by way of HTTPS requests on these urls:
* Production: https://prod.cobru.co/
* Development and testing: https://dev.cobru.co/
 
With it you can create, look up and update Cobrus and pay a Cobru using a wide variety of payment methods, including Efecty, Baloto, PSE, credit cards or Crypto Currency. You can now also withdraw money from your Cobru account to a bank account or to cash. Details about creating an account, authentication and API usage can be found in this documentation. To start using the API you can go to “Mas” -> “Integracion” in our app, where you’ll find the necessary data to gain access.

### Documentation
https://cobru.stoplight.io

## Cobru Panel
If you have access to it, you can use Cobru Panel by going to the following URL on your web browser:

URL: https://panel.cobru.co/

If we want to conduct tests in the development environment, at the login panel, you will find a toggle or switch that allows u to switch between environments, keeping in mind that the login credentials can only be used in the environment in which they were created.

## The testing app
If you want to perform tests in the development environment with the Cobru application, you can download the app at https://cobru.co/download/. In the app, when you log in, you will find a toggle or switch that allows you to switch between environments, bearing in mind that the login credentials can only be used in the environment in which they were created.

NOTE: Both in the panel and in the app, the default environment is production.

## Copyright and License
This project is licensed under the [GNU GPL](https://www.gnu.org/licenses/gpl-3.0.html), version 3 or later
