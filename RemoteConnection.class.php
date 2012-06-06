<?php


require_once("$CFG->dirroot/question/type/vhdl/SSHConfig.class.php");

//add the platform-indepdenent SSH2 library to the path, and include it
set_include_path(get_include_path() . PATH_SEPARATOR . $CFG->dirroot.'/question/type/vhdl/remote');
require_once 'Net/SSH2.php';
require_once 'Net/SFTP.php';
require_once 'Crypt/RSA.php';


class RemoteException extends Exception {}
class RemoteConnectionException extends RemoteException {}
class RemoteSecurityException extends RemoteException {}

/**
 * 
 * Simple wrapper class for PHPSec for moodle use.
 * @author ktemkin
 *
 */
class RemoteConnection
{
    /**
     * Internal PHPSec SSH connection object.
     */
    private $ssh;
    
    private $sftp;
    
    /**
     * Internal PHPSec SFTP connection object.
     */
    
    /**
     * Create a new SSH connection to the server, which will be valid as long as
     * is_valid is true. 
     *
     * @param SSHConfig $config
     */
    public function __construct()
    {
        //create the connection
        $this->connect();

        //check for man-in-the-middle attacks
        $this->check_fingerprint();

        //perform authentication
        //(it's vital you check_fingerprint /first/)
        $this->authenticate();
    }

    public function __destruct()
    {
        //if our session is still valid, disconnect
        if($this->is_valid())
            $this->disconnect();
    }
    
    
    /**
     * Returns true iff the SSH Connection is still valid.
     */
    public function is_valid()
    {
        //a connection is valid iff the session handle is not identical to false
        //(note that a session ID of 0 is valid and == to false, but not === to false)
        return $this->ssh !== false;
    }
    
    /**
     * Disconnects from the remote server.
     */
    public function disconnect()
    {
        $this->ssh->disconnect();
        $this->ssh = false;
        
    }
    
    public function execute($command, &$output = array())
    {
        $result =  $this->ssh->exec($command);
        $output = explode("\n", $result);
        
        while(count($output) && $output[count($output)-1]==='')
            array_pop($output);
        
        //return the last element in the array
        if(count($output))
            return $output[count($output)-1];
        else
            return '';
    }
    
    public function upload_file($source, $dest)
    {
        $this->create_file($dest, file_get_contents($source));
    }
    
    public function create_file($destination, $content)
    {
        //upload the file via SFTP
        $result = $this->sftp->put($destination,$content);

        //if the upload failed, throw an exception with the last error
        if(!$result)
            throw new RemoteConnectionException($this->sftp->getLastSFTPError());
    }
    
    /**
     * Connects to the remote server, but does not authenticate. 

     * @throws SSHConnectionException   Thrown if the SSH connection couldn't be created.
     */
    private function connect()
    {
        //create the base SSH object
        $this->ssh = new Net_SSH2(SSHConfig::host, SSHConfig::port);        
        $this->sftp = new Net_SFTP(SSHConfig::host, SSHConfig::port);
    }
    
    private function check_fingerprint()
    {
        //convert the host key into a binary object
        $known_host = SSHConfig::fingerprint;
        
        //retrieve the fingerprint of the host we're connected to
        $connected_host = bin2hex($this->ssh->getServerPublicHostKey());
        
        //if the two strings don't match, throw a security exception
        if($known_host!==$connected_host)
            throw new RemoteSecurityException("Expected fingerprint $known_host, but got $connected_host. This may indicate a man-in-the-middle attack!");
    }
        
    private function authenticate()
    {
        //get the local server's private key
        $private_key = file_get_contents(SSHConfig::private_key_file);
        
        //and derive the relevant public key
        $keystore = new Crypt_RSA();
        $keystore->loadKey($private_key);
        
        //use the public key to log in
        $result_ssh = $this->ssh->login(SSHConfig::username, $keystore);
        $result_sftp = $this->sftp->login(SSHConfig::username, $keystore);
  
        //if login failed, throw an exception
        if(!$result_ssh)
            /* throw new RemoteConnectionException */ die($this->ssh->getLastError());
        if(!$result_sftp)
            throw new RemoteConnectionException($this->sftp->getLastSFTPError());
        
    }
    
    private function event_disconnect($reason, $message, $language)
    {
        //debug output
        Debug::pre_print(array($reason, $message, $language));
        
        //invalidate the session
        $this->ssh = false;
    }
    
    
}

