# Sengera U2F Module
This is an integration of the FIDO U2F Standard as registration for the Zend Framework 3. 

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
- `/register-u2f-do` - AJAX Action for FIDO U2F Javascript Call

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
##### 1.0.0
- Base Version
- Registration works basically

## Contact
Marvin Sengera  
work@marvin-sengera.de  
Visit: marvin-sengera.de

##### Copyright
&copy;  Marvin Sengera - 2018