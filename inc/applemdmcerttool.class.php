<?php
class PluginFlyvemdmApplemdmCertTool {

   /** @var string $intermediateCrt WWDR intermediary certificate*/
   private $intermediateCrt = 'https://developer.apple.com/certificationauthority/AppleWWDRCA.cer';

   /** @var string $appleRootCrt Apple root certificate*/
   private $appleRootCrt = 'http://www.apple.com/appleca/AppleIncRootCertificate.cer';

   const PLIST_XML = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
<key>PushCertRequestCSR</key>
<string>
{csr}
</string>
<key>PushCertCertificateChain</key>
<string>
{mdm}
{intermediate}
{root}
</string>
<key>PushCertSignature</key>
<string>
{signature}
</string>';
EOT;

   /**
    * Generate PList file
    * @param string $vendorDir
    * @param string $customerDir
    * @param string $passphrase vendor private key's passphrase
    * @return boolean
    */
   public function generatePlist($vendorDir, $customerDir, $passphrase) {
      $vendorPrivKey = "$vendorDir/vendor_privkey.pem";
      $customerCsr = "$customerDir/customer_request.csr";
      $vendorCrt = "$vendorDir/vendor_crt.pem";
      if (!file_exists($vendorPrivKey)) {
         Session::addMessageAfterRedirect(__('Vendor key is missing', 'flyvemdm'), false, ERROR);
         return false;
      }

      if (!file_exists($customerCsr)) {
         Session::addMessageAfterRedirect(__('Customer CSR is missing', 'flyvemdm'), false, ERROR);
         return false;
      }

      $csr = base64_encode(file_get_contents($customerCsr));
      $mdm = file_get_contents($vendorCrt);
      $intermediate = $this->getCertificateFromApple($this->intermediateCrt);
      $root = $this->getCertificateFromApple($this->appleRootCrt);
      if ($intermediate === false) {
         Session::addMessageAfterRedirect(__('Failed to download intermediate certificate', 'flyvemdm'), false, ERROR);
         return false;
      }
      if ($root === false) {
          Session::addMessageAfterRedirect(__('Failed to download intermediate certificate', 'flyvemdm'), false, ERROR);
          return false;
      }
      $intermediate = $this->der2pem($intermediate);
      $root = $this->der2pem($root);

      $signature = null;
      $privKeyResource = openssl_pkey_get_private("file://$vendorPrivKey", $passphrase);
      if ($privKeyResource === false) {
          Session::addMessageAfterRedirect(__('Failed to read the private key', 'flyvemdm'), false, ERROR);
          return false;
      }

      $customerCsr = file_get_contents("file://$customerCsr");
      $customerCsr = $this->pem2der($customerCsr);
      $signSuccess = openssl_sign(
         $customerCsr,
          $signature,
          $privKeyResource,
         'sha1'
      );
      openssl_free_key($privKeyResource);
      $signature = base64_encode($signature);

      if ($signSuccess === false) {
          Session::addMessageAfterRedirect(__('Failed to sign the customer certificate request', 'flyvemdm'), false, ERROR);
          Session::addMessageAfterRedirect(openssl_error_string(), false, ERROR);
          return false;
      }

      $xml = static::PLIST_XML;
      $placeholders = [
         '{csr}',
         '{mdm}',
         '{intermediate}',
         '{root}',
         '{signature}',
      ];

      $replacements = [
         $csr,
         $mdm,
         $intermediate,
         $root,
         $signature
      ];
      $xml = str_replace($placeholders, $replacements, $xml);
      file_put_contents("$vendorDir/plist.xml", $xml);
      return true;
   }

   /**
    *
    *
    * @param string $destinationDir where to put the keys and CSR
    * @param string $filePrefix     prefix of the filenames
    * @param array $options         country_code: coutry code for the CSR
    *                               common_name : common name for the CSR
    *                               email       : email for the CSR
    * @return boolean true on success, false otherwise
    */
   public function generateCsr($destinationDir, $filePrefix, $options) {

      $inputIsValid = true;
      if (!isset($options['email']) || strlen($options['email']) === 0) {
         Session::addMessageAfterRedirect(__('You must provide an email address', 'flyvemdm'), false, ERROR);
         $inputIsValid = false;
      }
      if (!isset($options['country_code']) || strlen($options['country_code']) === 0) {
         Session::addMessageAfterRedirect(__('You must provide a Country Code', 'flyvemdm'), false, ERROR);
         $inputIsValid = false;
      }
      if (!isset($options['common_name']) || strlen($options['common_name']) === 0) {
         Session::addMessageAfterRedirect(__('You must provide a Common Name', 'flyvemdm'), false, ERROR);
         $inputIsValid = false;
      }
      if (!isset($options['passphrase']) || strlen($options['passphrase']) === 0) {
         Session::addMessageAfterRedirect(__('You must provide a passphrase for the private key', 'flyvemdm'), false, ERROR);
         $inputIsValid = false;
      }
      if (!$inputIsValid) {
         return false;
      }

      $sslOptions = [
         'digest_alg'       => 'sha512',
         'private_key_bits' => 4096,
         'private_key_type' => OPENSSL_KEYTYPE_RSA,
         'encrypt_key'      => true
      ];

      // Generate an encrypted key
      $keyPairResource = openssl_pkey_new($options);
      $exportedPrivKey = null;
      if (!openssl_pkey_export($keyPairResource, $exportedPrivKey, $options['passphrase'], $sslOptions)) {
         Session::addMessageAfterRedirect(__('Failed to export the private key', 'flyvemdm'), false, ERROR);
      }
      $exportedPubKey = openssl_pkey_get_details($keyPairResource);
      $exportedPubKey = $exportedPubKey['key'];

      // Generate a CSR
      $dn = [
         'countryName'  => $options['country_code'],
         'commonName'   => $options['common_name'],
         'emailAddress' => $options['email'],
      ];
      $csr = null;
      $csrResource = openssl_csr_new($dn, $keyPairResource);
      openssl_pkey_free($keyPairResource);
      if (!openssl_csr_export($csrResource, $csr)) {
         Session::addMessageAfterRedirect(__('Failed to export the CSR', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Save private key
      $written = PluginFlyvemdmCommon::createPrivateFile("$destinationDir/${filePrefix}_privkey.pem", $exportedPrivKey);
      if ($written !== strlen($exportedPrivKey)) {
         Session::addMessageAfterRedirect(__('Failed to save the private key', 'flyvemdm'), false, ERROR);
         return false;
      }

      $written = PluginFlyvemdmCommon::createPrivateFile("$destinationDir/${filePrefix}_pubkey.pem", $exportedPubKey);
      if ($written !== strlen($exportedPubKey)) {
         Session::addMessageAfterRedirect(__('Failed to save the public key', 'flyvemdm'), false, ERROR);
         return false;
      }

      $written = PluginFlyvemdmCommon::createPrivateFile("$destinationDir/${filePrefix}_request.csr", $csr);
      if ($written !== strlen($csr)) {
         Session::addMessageAfterRedirect(__('Failed to save the certificate signing request key', 'flyvemdm'), false, ERROR);
         return false;
      }

      return true;
   }

   /**
    * Downloads a resource from the givel URL
    * @param string $url
    * @return boolean|string
    */
   private function getCertificateFromApple($url) {
      $ch = curl_init();
      $options = [
         CURLOPT_URL => $url,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_TIMEOUT => 3,
         CURLOPT_RETURNTRANSFER => 1,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTPHEADER => [
            'User-Agent:Flyve MDM Backend/' . PLUGIN_FLYVEMDM_VERSION
         ],
      ];
      curl_setopt_array($ch, $options);
      $body = @curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      if ($http_code !== 200) {
         return false;
      }

      return $body;
   }

   /**
    * https://stackoverflow.com/questions/32596337/load-a-key-file-from-der-format-to-pem-with-php
    * @param string $der_data
    * @param string $type
    * @return string
    */
   private function der2pem($der_data, $type = 'CERTIFICATE') {
       $pem = chunk_split(base64_encode($der_data), 64, "\n");
       $pem = "-----BEGIN ".$type."-----\n".$pem."-----END ".$type."-----\n";
       return $pem;
   }

   /**
    * https://stackoverflow.com/questions/32596337/load-a-key-file-from-der-format-to-pem-with-php
    * @param string $pem_data
    * @param string $type
    * @return string
    */
   private function pem2der($pem_data, $type = 'CERTIFICATE REQUEST') {
       $begin = "$type-----";
       $end   = "-----END";
       $pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
       $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
       $der = base64_decode($pem_data);
       return $der;
   }

}