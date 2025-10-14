<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Dashboard_Crypt
{
    private $public_key;
    private $sym_key;

    private $rij;
    private $rsa;

    public function __construct($public_key)
    {
        $this->public_key=$public_key;

        if(!class_exists('Crypt_Rijndael'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'methods/Crypt/Rijndael.php';

        if(!class_exists('Crypt_RSA'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'methods/Crypt/RSA.php';

        if(!class_exists('Math_BigInteger'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'methods/Math/BigInteger.php';
        //
        $this->rij= new Crypt_Rijndael();
        $this->rsa= new Crypt_RSA();
    }

    public function generate_key()
    {
        $this->sym_key = crypt_random_string(32);
        $this->rij->setKey($this->sym_key);
    }

    public function encrypt_message($message)
    {
        $this->generate_key();
        $key=$this->encrypt_key();
        $len=str_pad(dechex(strlen($key)),3,'0', STR_PAD_LEFT);
        $message=$this->rij->encrypt($message);
        if($message===false)
            return false;
        $message_len = str_pad(dechex(strlen($message)), 16, '0', STR_PAD_LEFT);
        return $len.$key.$message_len.$message;
    }

    public function encrypt_key()
    {
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($this->sym_key);
    }

    public function decrypt_message($message)
    {
        $len = substr($message, 0, 3);
        $len = hexdec($len);
        $key = substr($message, 3, $len);

        $cipherlen = substr($message, ($len + 3), 16);
        $cipherlen = hexdec($cipherlen);

        $data = substr($message, ($len + 19), $cipherlen);
        $rsa = new Crypt_RSA();
        $rsa->loadKey($this->public_key);
        $key=$rsa->decrypt($key);
        $rij = new Crypt_Rijndael();
        $rij->setKey($key);
        return $rij->decrypt($data);
    }

    public function encrypt_user_token($token)
    {
        $user_info['token']=$token;
        $info=json_encode($user_info);
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($info);
    }


    public function encrypt_token($token)
    {
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($token);
    }
}

/*
class WPvivid_crypt_addon
{
    private $public_key;
    private $sym_key;

    private $rij;
    private $rsa;

    public function __construct($public_key)
    {
        $this->public_key=$public_key;
        include_once WPVIVID_PLUGIN_DIR . '/vendor/autoload.php';
        $this->rij= new Crypt_Rijndael();
        $this->rsa= new Crypt_RSA();
    }

    public function generate_key()
    {
        $this->sym_key = crypt_random_string(32);
        $this->rij->setKey($this->sym_key);
    }

    public function encrypt_message($message)
    {
        $this->generate_key();
        $key=$this->encrypt_key();
        $len=str_pad(dechex(strlen($key)),3,'0', STR_PAD_LEFT);
        $message=$this->rij->encrypt($message);
        if($message===false)
            return false;
        $message_len = str_pad(dechex(strlen($message)), 16, '0', STR_PAD_LEFT);
        return $len.$key.$message_len.$message;
    }

    public function encrypt_key()
    {
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($this->sym_key);
    }

    public function decrypt_message($message)
    {
        $len = substr($message, 0, 3);
        $len = hexdec($len);
        $key = substr($message, 3, $len);

        $cipherlen = substr($message, ($len + 3), 16);
        $cipherlen = hexdec($cipherlen);

        $data = substr($message, ($len + 19), $cipherlen);
        $rsa = new Crypt_RSA();
        $rsa->loadKey($this->public_key);
        $key=$rsa->decrypt($key);
        $rij = new Crypt_Rijndael();
        $rij->setKey($key);
        return $rij->decrypt($data);
    }

    public function encrypt_user_info($pw)
    {
        $user_info['pw']=$pw;
        $info=json_encode($user_info);
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($info);
    }

    public function encrypt_user_token($token)
    {
        $user_info['token']=$token;
        $info=json_encode($user_info);
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($info);
    }

    public function encrypt_token($token)
    {
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($token);
    }
}
*/