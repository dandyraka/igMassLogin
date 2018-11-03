<?php
class InstagramLogin{
  private $username;
  private $password;
  private $csrftoken;
  private $phone_id;
  private $guid;
  private $uid;
  private $device_id;
  private $cookies;
  private $api_url = 'https://i.instagram.com/api/v1';
  private $ig_sig_key = '673581b0ddb792bf47da5f9ca816b613d7996f342723aa06993a3f0552311c7d'; //NEW V4
  private $sig_key_version = '4';
  private $x_ig_capabilities = '3brTPw=='; //NEW V4
  private $android_version = 18;
  private $android_release = '4.3';
  private $android_manufacturer = "Huawei";
  private $android_model = "EVA-L19";
  private $headers = array();
  private $user_agent = "Instagram 42.0.0.19.95 Android (18/4.3; 320dpi; 720x1280; Huawei; HWEVA; EVA-L19; qcom; en_US)"; //NEW V4
  public function __construct(){
    $this->guid = $this->generateUUID();
    $this->phone_id = $this->generateUUID();
    $this->device_id = $this->generateDeviceId();
    $this->upload_id = $this->generateUploadId();
    $this->headers[] = "X-IG-Capabilities: ".$this->x_ig_capabilities;
    $this->headers[] = "X-IG-Connection-Type: WIFI";
  }
  public function Login($username="", $password=""){
    $this->username = $username;
    $this->password = $password;
    $this->csrftoken = $this->GetToken();
    $arrUidAndCooike = $this->GetLoginUidAndCookie();
	if(!empty($arrUidAndCooike[1])){
		$kokey = base64_encode($arrUidAndCooike[1]);
		print "Login Success, saved to ids.txt\n";
		$judl = fopen("ids.txt", "a");
		fwrite($judl, $username.":".$kokey . "\n");
		fclose($judl);
	}
  }
  private function GetToken(){
    $strUrl = $this->api_url."/si/fetch_headers/?challenge_type=signup";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$strUrl);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close ($ch);
    preg_match_all("|csrftoken=(.*);|U",$result,$arrOut, PREG_PATTERN_ORDER);
    $csrftoken = $arrOut[1][0];
    if($csrftoken != ""){
      return $csrftoken;
    }else{
      print $result;
    }
  }
  private function GetLoginUidAndCookie(){
    $arrPostData = array();
    $arrPostData['login_attempt_count'] = "0";
    $arrPostData['_csrftoken'] = $this->csrftoken;
    $arrPostData['phone_id'] = $this->phone_id;
    $arrPostData['guid'] = $this->guid;
    $arrPostData['device_id'] = $this->device_id;
    $arrPostData['username'] = $this->username;
    $arrPostData['password'] = $this->password;
    $strUrl = $this->api_url."/accounts/login/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$strUrl);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->generateSignature(json_encode($arrPostData)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close ($ch);
    list($header, $body) = explode("\r\n\r\n", $result, 2);
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
    $cookies = implode(";", $matches[1]);
    $arrResult = json_decode($body, true);
    if($arrResult['status'] == "ok"){
      $uid = $arrResult['logged_in_user']['pk'];
      return array($uid, $cookies);
    }else{
		if($arrResult['message'] == "challenge_required" or $arrResult['message'] == "checkpoint_required"){
			print "Challenge required, check your Instagram Account\n";
		} else {
			print $arrResult['message']."\n";
		}
    }
  }
  private function generateUUID(){
      $uuid = sprintf(
          '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
          mt_rand(0, 0xffff),
          mt_rand(0, 0xffff),
          mt_rand(0, 0xffff),
          mt_rand(0, 0x0fff) | 0x4000,
          mt_rand(0, 0x3fff) | 0x8000,
          mt_rand(0, 0xffff),
          mt_rand(0, 0xffff),
          mt_rand(0, 0xffff)
      );
      return $uuid;
  }
  private function generateDeviceId(){
      return 'android-'.substr(md5(time()), 16);
  }
  private function generateSignature($data){
      $hash = hash_hmac('sha256', $data, $this->ig_sig_key);
      return 'ig_sig_key_version='.$this->sig_key_version.'&signed_body='.$hash.'.'.urlencode($data);
  }
  function generateUploadId(){
      return number_format(round(microtime(true) * 1000), 0, '', '');
  }
}
echo "/*~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
echo "     Instagram Mass Login\n";
echo "        Auto Get Token\n\n";
echo "    http://bit.ly/igMassLogin\n";
echo "      JNCK Media (c) 2018\n";
echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/\n\n";
echo "IDS file ~> ";
$idsList = trim(fgets(STDIN));
echo "Delimeter ~> ";
$delim = trim(fgets(STDIN));
echo "Delay (seconds) ~> ";
$delay = trim(fgets(STDIN));

$ids = file_get_contents($idsList);
$shit = explode("\r\n", $ids);
for ($i = 0; $i < count($shit); $i++){
	$obj = new InstagramLogin();
	$explode = explode($delim, $shit[$i]);
	$userx = $explode[0];
	$passx = $explode[1];
	$obj->Login($userx, $passx);
	print "Delay, Please wait...\n";
	sleep($delay);
}
?>
