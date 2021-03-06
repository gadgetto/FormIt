<?php
/**
 * @package formit
 */
class FormItForm extends xPDOSimpleObject
{
    private $encryptKey;
    private $ivKey;
    private $method = 'AES-256-CBC';

    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
        $this->setSecretKeys();
    }

    public function encrypt($value)
    {
        // $value = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->encryptKey), $value, MCRYPT_MODE_CBC, md5(md5($this->encryptKey))));
        $value = base64_encode(openssl_encrypt($value, $this->method, $this->encryptKey, 0, $this->ivKey));
        return $value;
    }
    public function decrypt($value, $type = 2)
    {
        /* Check for encryption type; 1 = old mcrypt method */
        if ($type === 1) {
            return rtrim(
                mcrypt_decrypt(
                    MCRYPT_RIJNDAEL_256,
                    md5($this->encryptKey),
                    base64_decode($value),
                    MCRYPT_MODE_CBC,
                    md5(md5($this->encryptKey))
                ),
                "\0"
            );
        }
        /* Return default openssl decrypted values */
        return openssl_decrypt(base64_decode($value), $this->method, $this->encryptKey, 0, $this->ivKey);
    }
    public function generatePseudoRandomHash($bytes = 16)
    {
        $hash = bin2hex(openssl_random_pseudo_bytes($bytes, $strong));
        if (!$strong) {
            $hash = $this->generatePseudoRandomHash($bytes);
        }
        return $hash;
    }

    public function setSecretKeys()
    {
        $encryptkey = $this->xpdo->getOption('formit.form_encryptkey', null, null, false);
        if (!$encryptkey) {
            $encryptkey = $this->xpdo->site_id;
            $setting = $this->xpdo->getObject(
                'modSystemSetting',
                array('key' => 'formit.form_encryptkey', 'namespace' => 'formit')
            );
            if (!$setting) {
                $setting = $this->xpdo->newObject('modSystemSetting');
                $setting->set('key', 'formit.form_encryptkey');
                $setting->set('namespace', 'formit');
            }
            $setting->set('value', $encryptkey);
            $setting->save();
        }
        $this->encryptKey = hash('sha256', $encryptkey);
        $this->ivKey = substr(hash('sha256', md5($encryptkey)), 0, 16);
    }
}
