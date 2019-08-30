# VATSIM Single Sign On

### Current stable version: Version 2.0
Download on [the releases section](https://www.github.com/KiloSierraCharlie/VATSIM-SSO/releases) of GitHub. Feel free to use the non-release, version on the Git, but beware it might not be too stable.



A simple, object-orientated sign-on class for the VATSIM network. If you're new starting out with VATSIM's Single Sign On system, or need to easily embed SSO into your already written application, this class should work perfectly for you.

# Configuration

To configurate the system, you need to edit the contents of Authentication.php. The configuration pre-placed within the class works on the Demo SSO system, as a public organization or webiste. You can find more information about the demo system, and it's key, secret and RSA key [here](https://forums.vatsim.net/viewtopic.php?t=65319 "VATSIM Forums - New Demo Credentials").

To use the demo system, you must use the provided demo credentials available on [this forum post](https://forums.vatsim.net/viewtopic.php?t=64909 "VATSIM Forums - BETA Details"). 

[Need help with the VATSIM SSO system?](http://forums.vatsim.net/viewforum.php?f=134  "VATSIM Forums - Technical Support - SSO")


[Original source created by Kieran Hardern (VATGOV6 vpweb@vatsim.net).](https://bitbucket.org/KHardern/vatsim-sso-demo/ "VATSIM SSO Demo on Bitbucket.")

# Demo Credentials
Here's a quick rundown of the demo credentials:

| Certificate | Password    | Network Rating        |
|-------------|-------------| ----------------------|
| 1299999     | 1299999     | Inactive (-1)         |
| 1300000     | 1300000     | Suspended (0)         |
| 1300001     | 1300001     | Observer/Pilot (1)    |
| 1300002     | 1300002     | Student (2)           |
| 1300003     | 1300003     | Student 2 (3)         |
| 1300004     | 1300004     | Senior Student (4)    |
| 1300005     | 1300005     | Controller (5)        |
| 1300006     | 1300006     | Controller 2 (6)      |
| 1300007     | 1300007     | Senior Controller (7) |
| 1300008     | 1300008     | Instructor (8)        |
| 1300009     | 1300009     | Instructor 2 (9)      |
| 1300010     | 1300010     | Senior Instructor (10)|
| 1300011     | 1300011     | Supervisor (11)       |
| 1300012     | 1300012     | Administrator (12)    |

# Usage
Header of the page.
```php
  require( "Authentication.php" );
  if ( session_status() === PHP_SESSION_NONE ){ session_start(); }
  
  if( isset( $_SESSION['AuthHandler'] ) ){
    
    $AuthHandler = unserialize( $_SESSION['AuthHandler'] );
    
  }else{
  
    $http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
    $AuthHandler = new Authentication( $http.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] );
    
  }
  if( $AuthHandler->shouldCheckLogin() ){
    
    $AuthHandler->checkLogin();
    
  }
  if( !$AuthHandler->isLoggedIn() ){
    
    $_SESSION["AuthHandler"] = serialize( $AuthHandler );
    $AuthHandler->Login();
    
  }
```

Footer of the page, to ensure that the user stays logged-in.
```php
$_SESSION["AuthHandler"] = serialize( $AuthHandler );
```
