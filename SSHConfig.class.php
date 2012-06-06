<?php

class SSHConfig
{
    /**********************
     * Connection basics. *
     **********************/
    
    /**
     * Hostname of the server to connect to 
     */
    //const host = "199.167.199.172"; //"hdlsim.bumoodle.com";
    const host = "hwcode.com";
    
    /**
     * Port to connect to, on the given host.
     */
    const port = 8051;
    
    /**
     * Username, for connection
     */
    const username='iseremote';
    
    /**********************
     * RSA Configuration  *
     **********************/
    
    /**
     * Expected fingerprint, which the host must match.
     */
    //const fingerprint = '000000077373682d727361000000030100010000010100a2733fe3db982a3ca06b0ac6b7035384a250caf3aa679fcee32ec15e78b2d1b4ffee26fda6aa70850402b4afe6eca06db8254290bfadcee8282e39d1ff7200ec9842589f869bd015bbd4b3c454910bf5d0e5f2b44e5ce9e32dc59512db59c37922b288ee6c0ea9886b470c9fd3688d82a5cb763dd0b2323af0afc86d5e9d625d822d228484ad36af43720c657f5d01fe69550b5580fa0a13f37d61b6ba510b21f9efb7db4fc29ea37d04081bde0adb0920addcc80fff4d7d8af2bb60130b6dc75b2be0bbd96bc8808190efb8399c1614bbd51fe75dc0577095de87831a250e13f3f18c34be62f514ebd4f4cfe73a44b50eca79b835169324b1c243e80974b605';
    const fingerprint = '000000077373682d72736100000001230000010100acc1bfcca42d0ff4547f3e5a1f05577c4f7b5f9c64dfe08fd5dfac30681aade5a1e2b21d7f0be69c3f34b42652066596d21aad6c4a72896a6a999654a1aec426cd2d3138e8d92af5edd3f7dc045496e50ebd0494f6133a5c3ce7a2b811ca1ab3b3a4733ef54c0d7b600f23101dcfe85987e24eef8188a9990618613702135519c1c95493bab46057db17ee4f73fae39b57b7f081903ea95c83ad1c105a538f04ebc34ab0e04b576d7353c83363b946573a45eb8a527e959935dae65b93adc047b12d5f538db712ce76b44dd78b3a886fe7fd9cb87352e9507202ce4a327618fba6cebdf267b371ff8ebb8c0a8093439b6ae69194481a1abaf556c2bf6c444c15';
    
    /**
     * Public key used for identification, and to allow the remote host to encode responses.
     */
    const public_key = '/etc/autolab/id_rsa.pub';
    
    /**
     * Private key, used to decrypt communications from the server.
     */
    const private_key_file = '/etc/autolab/id_rsa';
    
    /**********************
     * Cipher Information *
     **********************/
    
    public static function methods()
    {
        //create a simple array of communication specifics
        $comm_specifics =  array
            (
                //we're not doing anything that requires the utmost of speed
                //or uses a ton of bandwidth, so we'll use the very secure aes-128
                'crypt' => 'aes128-cbc',

                //If, for whatever reason, you believe quantum computers will be trying to
                //break into this SSH session, you can use aes256-cbc.
                //If this is the case, god help you.
            
                //paired with SHA1, for pretty damned good security
                'mac' => 'hmac-sha1',
                
                //to save bandwidth, enable zlib compression of the datastream
                'comp' => 'zlib'
            );
            
        return array
            (
                //we're using RSA, as specified above
                'hostkey' => 'rss',
                
                //use our comm specifics for both communication directions 
                'client_to_server' => $comm_specifics,
                'server_to_client' => $comm_specifics
            );
    }
    
    
    
}
