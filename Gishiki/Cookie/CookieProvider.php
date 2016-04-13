<?php
/**************************************************************************
Copyright 2015 Benato Denis

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*****************************************************************************/

namespace Gishiki\Cookie;

/**
 * The class that, once instantiated, provides cookie management ability
 * 
 * @author Benato Denis <benato.denis96@gmail.com>
 */
class CookieProvider
{
    
    /**
     * Retrive the list of all cookies stored by the client 
     */
    public function RestoreCookies()
    {
        $cookies = [];
        
        //cache the cookie prefix in order to avoid calling GetConfigurationProperty for each propery
        $cookiePrefix = \Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_PREFIX');
        
        //filter the cookie array as it is an external input
        $filtered_cookies = filter_input_array(INPUT_COOKIE);
        
        //cycle each cookie
        foreach ($filtered_cookies as $cookieCompleteName => $currentCookie) {
            
            //get the cookie name for the managed object
            $count = 0;
            $cookieName = str_replace($cookiePrefix, "", $cookieCompleteName, $count);
            
            if ($count == 1) {
                //fetch the cookie and store the managed cookie object into the list
   $cookies[] = $this->getCookie($cookieName);
            }
        }
        
        //return the list of fetched cookies
        return $cookies;
    }
    
    /**
     * Retrive a cookie managed object from the client request
     * 
     * @param  string $cookieName the name of the cookie to be retrived from the client request
     * @return mixed  return the cookie if the cookie is on the client, NULL otherwise
     */
    public function RestoreCookie($cookieName)
    {
        //filter the cookie array as it is an external input
        $filtered_cookies = filter_input_array(INPUT_COOKIE);
        
        //get the cookie complete name
        $name = \Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_PREFIX').$cookieName;
        
        //check if the requested cookie exists
        if ((!empty($filtered_cookies[$name])) && (isset($filtered_cookies[$name]))) {
            //create the new managed cookie object
            $cookie = new Cookie($cookieName);
            
            //check if the value is encrypted
            $count = 0;
            $cookieValue = str_replace(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_ENCRYPTION_MARK'), "", $filtered_cookies[$name], $count);
            
            //and, if the value is encrypted, decrypt&check it
            if ($count == 1) {
                //prepare the value digital signer verifier
                $signer = \Gishiki\Security\AsymmetricCipher::LoadPublicKey(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('MASTER_ASYMMETRIC_KEY_NAME'));

                //prepare the value decrypter
                $decrypter = new \Gishiki\Security\SymmetricCipher(\Gishiki\Security\SymmetricCipher::AES128);
                $decrypter->SetKey(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_ENCRYPTION_KEY'));

                //decrypt the cookie value
                $cookieValue = $decrypter->Decrypt($cookieValue);
                
                //$signANDmessage[0] is the digital signature,
                //$signANDmessage[1] is the (unencrypted) message
                $signANDmessage = explode("<-||^||->", $cookieValue, 2);
                
                if ($signer->VerifyDigitalSignature($signANDmessage[1], $signANDmessage[0])) {
                    $cookieValue = $signANDmessage[1];
                } else {
                    throw new CookieException("The cookie named ".$cookieName." cannot be restored, because its content is not secure", 3);
                }
            }
            
            //get the reflected cookie value
            $reflectedCookieValue = new \ReflectionProperty($cookie, 'value');
            $reflectedCookieValue->setAccessible(true);
            
            //and assign the cookie value
            $reflectedCookieValue->setValue($cookie, $cookieValue);
            
            //return the newly created cookie
            return $cookie;
        } else {
            return null;
        }
    }
    
    /**
     * Delete a cookie on the client and the cookie managed object on the server 
     * 
     * @param  Cookie $cookie the cookie to be deleted
     * @return bool   TRUE if success, FALSE otherwise
     */
    public function DeleteCookie(Cookie &$cookie)
    {
        //get the cookie complete name
        $name = \Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_PREFIX').$cookie->getName();
        
        //delete the cookie from the client
        $success = setcookie($name, null, time() - 3600);
        
        //if the cookie deletion was a success.....
        if ($success) {
            //......delete the cookie instance
            $cookie = null;
        }
        
        //and return the operation result
        return $success;
    }
    
    /**
     * Send a cookie to the client, the cookie can also be encrypted to get
     * higher security
     * 
     * @param  Cookie $cookie    the cookie to be sent
     * @param  int    $expireIn  the life time (in second) of the cookie on the client
     * @param  bool   $encrypted encrypt the cookie?
     * @param  bool   $secure    a cookie that can be usen only with HTTP connections
     * @return bool   the 
     */
    public function StoreCookie(Cookie &$cookie, $expireIn = null, $encrypted = false, $secure = false)
    {
        //get the cookie complete name
        $name = \Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_PREFIX').$cookie->getName();
        
        //get the cookie serialized value
        $reflectedCookieValueInspection = new \ReflectionMethod($cookie, "inspectSerializedValue");
        $reflectedCookieValueInspection->setAccessible(true);
        $value = $reflectedCookieValueInspection->invoke($cookie);
        if ($encrypted) {
            /*          encrypt the cookie value            */
            
            //prepare the value digital signer
            $signer = \Gishiki\Security\AsymmetricCipher::LoadPrivateKey(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('MASTER_ASYMMETRIC_KEY_NAME'), \Gishiki\Core\Enviroment::GetCurrentEnvironment()->GetConfigurationProperty("SECURITY_MASTER_SYMMETRIC_KEY"));
            
            //prepare the value encrypter
            $encrypter = new \Gishiki\Security\SymmetricCipher(\Gishiki\Security\SymmetricCipher::AES128);
            $encrypter->SetKey(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_ENCRYPTION_KEY'));
            
            //generate the digital signature
            $digitalSign = $signer->GenerateDigitalSignature($value);

            //append the digital signature to the cookie
            $value = $digitalSign."<-||^||->".$value;
            
            //encrypt the cookie append the string that mark an encrypted cookie
            $value = \Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_ENCRYPTION_MARK').$encrypter->Encrypt($value);
        }
        
        //get the cookie exiration time
        if (gettype($expireIn) == "NULL") {
            $expireIn = time() + abs(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_DEFAULT_LIFETIME'));
        }
        
        //get the cookie path
        $path = \Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('COOKIE_VALIDITY_PATH');
        
        //return a flag of the cookie export status
        if ($secure) {
            if (\Gishiki\Core\Environment::GetCurrentEnvironment()->SecureConnectionEnabled()) {
                return setcookie($name, $value, $expireIn, $path, $secure, $secure);
            } else {
                return false;
            }
        } else {
            return setcookie($name, $value, $expireIn, $path, $secure, $secure);
        }
    }
}