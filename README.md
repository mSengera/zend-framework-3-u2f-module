# Sengera U2F Module
This is an integration of the FIDO U2F Standard as registration for the Zend Framework 3. 

Current version: **1.1.0**

## Installation
1. Download or clone repository
2. Copy all files to your project
3. Copy `module/SengeraU2F` folder into your module folder
4. Copy `public/js/u2f-api.js` file into same direction in your project
5. Check requirements. Install modules if needed
6. Check configurations for required modules. (e.g. doctrine database credentials, zend cache configuration [...])

## URLs
New Urls to reach:
- `/register` - Register Form with Emailadress and Password
- `/register-u2f` - Register Form for an FIDO U2F Key
- `/register-u2f-do` - AJAX Action for FIDO U2F Javascript Call#
- `/login` - Login Form with Emailadress and Password
- `/login-u2f` - Second factor login with FIDO U2F Key
- `/login-u2f-do` - AJAX Action for FIDO U2F Javascript Call

## Requirements
- `Doctrine\ORM\Mapping`
- `Interop\Container`
- `Zend\Form`
- `Zend\Mvc\Controller`
- `Zend\ServiceManager`
- `Zend\Session`
- `Zend\Validator`
- `Zend\View`

## Changelog
##### 1.1.0
- Add login functions
- Login after registrations works basically

##### 1.0.0
- Base Version
- Registration works basically

## ToDo
- Exception Handling
- Simple backend with logout button
- New counter write after login in database
- Clean U2fServerService

## Contact
Marvin Sengera  
work@marvin-sengera.de  
Visit: http://marvin-sengera.de

##### Copyright
&copy;  Marvin Sengera - 2018