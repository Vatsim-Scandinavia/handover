<?php

require('OAuth.php');
require('SSO.class.php');

class Authentication{

  private $SSOHandler;
  private $loggedIn;
  private $User;
  private $returnURL;
  
  function __construct( $returnURL="/login" ){
    
    $this->Base = "http://sso.hardern.net/server/";
    $this->Key = "SSO_DEMO_VACC";
    $this->Secret = "04i_~ruVUE.1-do1--sc";
    $this->Method = "RSA";
    $this->Cert = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEpgIBAAKCAQEA2S5RckDw7SnEoZDmjaQHAQGajVlb7iwKIAX6nXbZBO7Uo3pN
ItjmAbfkMqKBgWDVowM3UjbKivZNWGzkmxirArpbw9q7JhcX2LW6RfXx+5zn2+zW
m58nQtnEgZtj8U9z3yjJEwfGbiJHEt56pNY0VFV5sDbEiQ52d/bPHlH17j/SUfm6
eWCbUWW5S8kI8LDuN40qtxCZ0InTfRvcI3bx0+UBf9T6SYQWK2DsS+bz2YtKxVom
Os9NdLbcPDK1rKPCJ+gvvmhCCt7jDbf1oFUzhPb6hjsIl1uRyjdtjhDb5FIokH+O
3LuZdvSGF/SkoBnkfnqg5yTjC0GrnPg+Dr++1QIDAQABAoIBAQDIAisJwJrgnx2x
+WMKQGwe1h5CXHAYOMCeW0NBLsmQDG8RmrldBUlVfcgPha8kukwlEvooocMIFOqI
K8iguSgMnBmUlmTSIGRatIm2kljm8spotIWzze93VlvtTHDPM++vLb135CovFSxF
SVTDZ23L2Of3i4iV/BbIRijacHq/jJ605OBcHhgW0ONCPUxL+uUd7siD68Y/BcYu
km1OfQaxxryKdnE4UWzVKm0fwIzGvS/Baraek3kQCqOs7+OixV2YWFw6Xafq3WAp
Pe5I/pJSevu90dGN01k84fVS6q3q419Z+VxarPYYznLrGGgUxM5zKlU4VHGwvA2p
857ydg3hAoGBAPFuOulYQW8DIas4rlPPGofQI+dT0w8xf/YB1WmCtlt0GkSmEzd+
JJZtcJiQSlTC4BuACvTBoIgo3vUC2wM5gZLz9NCeUHrwW0558q1YnGx1GNKcWgKK
LrYvWPCrOKVnDvfhSQ4P3CPeUyks4OUTiPHY+5QlBpY7c1hSBnJWSNKZAoGBAOZJ
dtle62ZK6S3TlIgbElaa1h8J5QyEFmcCPl47B4+SUNIljccO55OQhe89paMD2EH6
Tbz9eP/s4U7X1tTb2onYtd7g3ldod/RBhrRHg7oXTmQj9wXopJsHwgNnYG59BPt2
xpnB7aTmMZCXTO2YRxR4CCTtnOO/TZeNZV/xIK+dAoGBAJQ2sJHZ7WmiSYQcquCm
jsn7nF8CFdsI715uJ767UQn5z7p/HeL+XKXAj9QJGKjKbdxUEeXKDKwqMx3E4AEt
x38Ypx1/Yzbl4Zfew31pnbXzeQaql5Nhk2Wi0X4GDyNzjjvcoQWx9NpMPU9Uzsey
42pdY6zBwjZuTtRUnsKId/JZAoGBALzXVXyfF85Ec76+mDicaodWZWwCgy+mSXCj
KF3BbkvPojMR1Jd9o20gwJQVK3ToPDiud30ZJlZH++LZoDPhLe6IJWvlXq6y3lsQ
ONQxKNY7Mm9wBqtzwTfYPsLnzO4N2z4Sgn2nx6bHlbGKQO09SFyCqbsOlu8z+v7i
VlU8uJ8JAoGBAOmzlKBcEjJdlD0ZxkgMxp+YqpKkC+ojzf4tORn6jo2d/aKUOIAR
bfRCMTmDmqyVoUH/SYgQWzD36zAy8HyHEz0U1k6+QMzWPbsEGQSQrk0DgnlOBPWo
O0gQ0RDS3gD8C5XHvy5vryYjUOB10rUn9A2xLQw4sqKv2suHvIhc0Eit
-----END RSA PRIVATE KEY-----
EOD;
    
    $this->returnURL = $returnURL . "?return";
    $this->loggedIn = false;
    $this->User = array();
    
    if ( session_status() === PHP_SESSION_NONE ){
      session_start();
    }
    
    if( isset( $_SESSION['AuthHandler'] ) ){
    
      $StoredHandler = unserialize( $_SESSION['AuthHandler'] );
      $this->User = $StoredHandler->getUserDetails();
      $this->loggedIn = $StoredHandler->isLoggedIn();
      
    }
    
    $this->SSO = new SSO( $this->Base, $this->Key, $this->Secret, $this->Method, $this->Cert );
    
  }

  public function Login(){
    
    $token = $this->SSO->requestToken( $this->returnURL, false, false );
    if ( $token ){
      
        $_SESSION['oauth'] = array(
            'key' => (string)$token->token->oauth_token,
            'secret' => (string)$token->token->oauth_token_secret
        );
        
        return $this->SSO->sendToVatsim();
        
    } else {
      
        return false;
        
    }
    
  }
  
  public function checkLogin(){
    
    if ( isset( $_GET['return'] ) && isset( $_GET['oauth_verifier'] ) && !isset( $_GET['oauth_cancel'] ) ){
      
      if ( isset( $_SESSION['oauth'] ) && isset( $_SESSION['oauth']['key'] ) && isset( $_SESSION['oauth']['secret'] ) ){
        
        if ( @$_GET['oauth_token'] != $_SESSION['oauth']['key'] ){
          
          return false;
          
        }
        if (@!isset($_GET['oauth_verifier'])){
          
          return false;
          
        }
        
        $user = $this->SSO->checkLogin( $_SESSION['oauth']['key'], $_SESSION['oauth']['secret'],  @$_GET['oauth_verifier'] );
        
        if( $user ){
          
          unset( $_SESSION['oauth'] );
          $this->User = $user->user;
          $this->loggedIn = true;
          return true;
          
        } else {
          
          return false;
          
        }
      } 
    } else if ( isset( $_GET['return'] ) && isset( $_GET['oauth_cancel'] ) ){
      
        return false;
        
    }      
    
  }
  
  public function shouldCheckLogin(){
    
    return isset( $_GET['return'] ) && isset( $_GET['oauth_verifier'] ) && !isset( $_GET['oauth_cancel'] );
    
  }
  
  public function getUserDetails(){
    
    if( $this->loggedIn ){
      
      return $this->User;
      
    }
    
    return false;
    
  }
    
  public function isLoggedIn(){
    
    return $this->loggedIn;
    
  }
  
  public function Logout(){
    
    $this->User = Array();
    $this->loggedIn = false;
    unset( $_SESSION["AuthHandler"] );
    
  }
  
}
