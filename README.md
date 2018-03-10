# Sengera U2F Module
This is an integration of the FIDO U2F Standard as registration for the Zend Framework 3. I programmed it for my 
bachelor thesis "Integration und Beschreibung der U2F Authentifikation in das Zend Framework".

Current version: **1.2.0**

## Installation
1. Download or clone repository
2. Copy all files to your project
3. Copy `module/SengeraU2F` folder into your module folder
4. Copy `public/js/u2f-api.js` file into same direction in your project
5. Check requirements. Install modules if needed
6. Check configurations for required modules. (e.g. doctrine database credentials, zend cache configuration [...])

## URLs
New URLs to reach:
- `/register` - Register Form with Emailadress and Password
- `/register-u2f` - Register Form for an FIDO U2F Key
- `/register-u2f-do` - AJAX Action for FIDO U2F Javascript Call
- `/login` - Login Form with Emailadress and Password
- `/login-u2f` - Second factor login with FIDO U2F Key
- `/login-u2f-do` - AJAX Action for FIDO U2F Javascript Call

## Requirements
- `Doctrine\ORM\Mapping`
- `Interop\Container`
- `Zend\Crypt`
- `Zend\Escaper`
- `Zend\Form`
- `Zend\Mvc\Controller`
- `Zend\ServiceManager`
- `Zend\Session`
- `Zend\Validator`
- `Zend\View`

## Changelog
##### 1.2.0
- Major security fixes
- Small bugfixes
- Basic error handling

##### 1.1.0
- Add login functions
- Login after registrations works basically

##### 1.0.0
- Base Version
- Registration works basically

##### 0.0.1
- Basic installation
- Very basic structure

## ToDo
- Simple backend with logout button
- New counter write after login in database
- Clean U2fServerService
- Main menu bar: check if user is logged in, change login and register to logout button
- Forgot password functionality
- Ability to add a second U2F device to user account. Function: Mark a key as primary
- Ability to delete U2F devices from user account
- Email notification, if "not primary" U2F device is used for authentication, cause of security reasons

## Contact
Marvin Sengera  
work@marvin-sengera.de  
Visit: http://marvin-sengera.de

##### Copyright
&copy;  Marvin Sengera - 2018