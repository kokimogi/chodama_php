<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Form\RegisterForm;
use Cake\ORM\TableRegistry;
use App\Utils\AppUtility;
use App\Utils\SqlManager;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use App\Utils\RegisterTime;
use App\Utils\checkStore;
use \Exception;
use Cake\Log\Log;
use App\Utils\MailUtil;
use Cake\Network\Email\Email;
use App\Utils\BarcodeUtil;

class RegistersController extends AppController
{
  
  public function initialize()
  {
    $this->loadComponent('Csrf');
    $this->viewBuilder()->layout('chodama');
    $this->set('footer', 'Chodama/footer');
    if(AppUtility::getDeviceType()=="03" && strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
	 $this->viewBuilder()->layout('chodama_mau');
         $this->set('footer', 'Chodama/footer_mau');
    }

    if(AppUtility::getDeviceType()=="03"){
          ini_set('session.use_cookies',0);
          ini_set('session.use_only_cookies',0);
          ini_set('session.use_trans_sid',1);
    }

  }

  public function is_imode_browser_v1($ua){
	    if(preg_match('/^DoCoMo\/1.0/',$ua)){
	        return true;
	    }
	    elseif(preg_match('/^DoCoMo\/2.0[^(]+\(c100;/',$ua)){
	        return true;
	    }
	    return false;
    }

  public function index($tenpoCd=null,$tempRegId=null)
  {
    //トークン処理
    $session = $this->request->session();
    if(isset($this->request->params['_csrfToken'])){
    	$session->write('token',$this->request->params['_csrfToken']);
    }elseif(isset($_REQUEST['_csrfToken'])){
    	$session->write('token',$_REQUEST['_csrfToken']);
    }
    //店舗コードと事前登録IDをセッションに格納
    if($tenpoCd!=null) $session->write('tenpo.cd',$tenpoCd);
    if($tempRegId!=null) $session->write('member.tempRegId',$tempRegId);
    $entity = new RegisterForm();
    $this->set('entity', $entity);

    if ($this->request->is('post')) {
       $data = $this->request->data;
       //if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false || $this -> is_imode_browser_v1($_SERVER['HTTP_USER_AGENT']) === true){
       if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
	  if(count($data)>0){
            foreach($data as $key=>$value){
              if(!is_array($value)){
    		  $data[$key]=mb_convert_encoding($value,"SJIS","UTF-8");
              }
            }
          }
	  $this->set('token', $this->request->params['_csrfToken']);
       }
       //戻ってきた場合はここを通る
       $this->set($data);
       if(AppUtility::getDeviceType()=="03"){
    	$csrfTemp=null;
    	if(isset($this->request->params['_csrfToken'])){
    	   $csrfTemp=$this->request->params['_csrfToken'];
    	}elseif(isset($_REQUEST['_csrfToken'])){
    	   $csrfTemp=$_REQUEST['_csrfToken'];
    	}
    	$this->set('token', $csrfTemp);
      }
    }else{
      //データベースコネクト
      $sqlManager = new SqlManager('default');
      $sql=<<< EOF
SELECT 
     CONVERT( nvarchar(max), DecryptByKey([mail_address])) as mail_address
 FROM mrhn.info_registrations
WHERE tempreg_id = :tempreg_id;
EOF;

      $compSql = $sqlManager->makeCryptSql($sql);
      $params[] = array('tempreg_id',$session->read('member.tempRegId'),'string');
      $result=$sqlManager->select($compSql,$params);
	
	  if(isset($result[0]['mail_address'])){
        $this->request->data += array('mail_address'=>$result[0]['mail_address']);
        $this->set('mail_address', $result[0]['mail_address']);
	  }else{
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',300,$tempRegId));
	  }
      //必要項目退避
      $req = array('tenpoCd'=>$tenpoCd,'tempRegId'=>$tempRegId);
      $this->set($req);
      if(AppUtility::getDeviceType()=="03"){
    	$csrfTemp=null;
    	if(isset($this->request->params['_csrfToken'])){
    	   $csrfTemp=$this->request->params['_csrfToken'];
    	}elseif(isset($_REQUEST['_csrfToken'])){
    	   $csrfTemp=$_REQUEST['_csrfToken'];
    	}
    	$this->set('token', $csrfTemp);
      }
    }
  }

  public function confirm()
  {
    $session = $this->request->session();
    //トークンチェック
    if(AppUtility::getDeviceType()=="03"){
      if($_POST['_csrfToken']!=$session->read('token')){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',301,$this->request->data['tempRegId']));
      }
    }else{
      if($this->request->params['_csrfToken']!=$session->read('token')){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',301,$this->request->data['tempRegId']));
      }
    }
    //画面入力データ
    $entity = new RegisterForm();
    $data = $this->request->data;

    //電話番号のフォーマット
    $pattern = '/[\x{30FC}\x{2010}-\x{2015}\x{2212}\x{FF70}-]/u';
    $data['phone_number']=mb_convert_kana(preg_replace($pattern, '-', $data['phone_number']),'a');
    $data['cellphone_number']=mb_convert_kana(preg_replace($pattern, '-', $data['cellphone_number']),'a');

    //店舗IDが入力画面と違ったり、セッションにない場合はエラー
    $sessCd = $session->read('tenpo.cd');
    $tempID = $session->read('member.tempRegId');

    if($sessCd!=$data['tenpoCd'] || $tempID!=$data['tempRegId']){
      $errorNo = 302;
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo,$data['tempRegId']));
    }
    //重複チェック
    $sqlManager = new SqlManager('default');
    $sql=<<< EOF
SELECT COUNT(*) AS CNT FROM  [mrhn].[info_members] 
WHERE store_code = :tenpoCd
  AND [tempreg_id]= :tempreg_id;
EOF;
/*
  AND (CONVERT( nvarchar(max), DecryptByKey([mail_address])) = :mail_address
   OR (CONVERT( nvarchar(max), DecryptByKey([family_name])) = :family_name
       AND CONVERT( nvarchar(max), DecryptByKey([first_name]))=:first_name
       AND CONVERT( nvarchar(max), DecryptByKey([family_name_kana]))=:family_name_kana
       AND CONVERT( nvarchar(max), DecryptByKey([first_name_kana]))=:first_name_kana
       AND CONVERT( nvarchar(max), DecryptByKey([birthday]))=:birthday))
   OR  [tempreg_id]= :tempreg_id;
*/

      //暗号化SQL
    $compSql = $sqlManager->makeCryptSql($sql);
    //バインド変数
    $params[] = array('tenpoCd',str_pad($this->request->data['tenpoCd'], 3, 0, STR_PAD_LEFT),'string');
//    $params[] = array('mail_address',$data['mail_address'],'string');
//    $params[] = array('family_name',$data['family_name'],'string');
//    $params[] = array('first_name',$data['first_name'],'string');
//    $params[] = array('family_name_kana',$data['family_name_kana'],'string');
//    $params[] = array('first_name_kana',$data['first_name_kana'],'string');
//    $params[] = array('birthday',$data['birthday']['year'].'/'.$data['birthday']['month'].'/'.$data['birthday']['day'],'string');
    $params[] = array('tempreg_id',$data['tempRegId'],'string');
    //SQL実行
    $result=$sqlManager->select($compSql,$params);
    $duplicateCnt=$result[0]['CNT'];
    if($duplicateCnt!=0){
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',303,$data['tempRegId']));
    }
    //エラーチェック
    if (!$entity->execute($data)) {
      //入力データにエラー（改ざんの可能性）があった場合
      $this->set($data);
      $this->setAction('index');
    }

    //if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false || $this -> is_imode_browser_v1($_SERVER['HTTP_USER_AGENT']) === true){
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
	if(count($data)>0){
          foreach($data as $key=>$value){
            if(!is_array($value)){
		$data[$key]=mb_convert_encoding($value,"SJIS","UTF-8");
            }
          }
        }
    }
    $this->set($data);
    $this->set('entity', $entity);
    if(AppUtility::getDeviceType()=="03"){
    	$csrfTemp=null;
    	if(isset($_REQUEST['_csrfToken'])){
    	   $csrfTemp=$_REQUEST['_csrfToken'];
    	}
    	$this->set('token', $csrfTemp);
    }
  }
        
  public function complete()
  {
    $session = $this->request->session();
    //トークンチェック
    if(empty($session->read('token'))){
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',304,$this->request->data['tempRegId']));
    }
    if(AppUtility::getDeviceType()=="03"){
      if($_POST['_csrfToken']!=$session->consume('token')){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',305,$this->request->data['tempRegId']));
      }
    }else{
      if($this->request->params['_csrfToken']!=$session->consume('token')){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',305,$this->request->data['tempRegId']));
      }
    }

    //画面入力データ
    $entity = new RegisterForm();
    $data = $this->request->data;

    //店舗IDが入力画面と違ったり、セッションにない場合はエラー
    $sessCd = $session->read('tenpo.cd');
    $tempID = $session->read('member.tempRegId');
    if($sessCd!=$data['tenpoCd'] || $tempID!=$data['tempRegId']){
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',306,$data['tempRegId']));
    }

    //入力データにエラー（改ざんの可能性）があった場合
    if (!$entity->execute($data)) {
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',307,$data['tempRegId']));
    }
    //info_registrationsからid取得
    $InfoRegistrations = TableRegistry::get('InfoRegistrations')->find()->where(['tempreg_id' => $data['tempRegId']])->order(['create_at' => 'ASC']);
    $id=null;
    foreach ($InfoRegistrations as $InfoRegistration) {
      $id=$InfoRegistration->id;
    }
    if(empty($id)){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',308,$data['tempRegId']));
    }
    
    //二重登録防止
    $infoMembersList = TableRegistry::get('InfoMembers')->find();
    $infoMembersList->select(['count'=>$infoMembersList->func()->count('*')])->where(['info_registration_id ' => $id]);
    foreach ($infoMembersList as $infoMembersCount) {
      if($infoMembersCount['count'] != 0){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',309,$data['tempRegId']));
      }
    }
    //事前登録会員情報追加SQL定義
    $sqlMember=<<< EOM
INSERT INTO [mrhn].[info_members]
           ([info_registration_id]
           ,[tempreg_id]
           ,[store_code]
           ,[family_name]
           ,[first_name]
           ,[middle_name]
           ,[family_name_kana]
           ,[first_name_kana]
           ,[middle_name_kana]
           ,[mail_address]
           ,[birthday]
           ,[gender_type]
           ,[country_type]
           ,[post_code_left]
           ,[post_code_right]
           ,[prefectures_name]
           ,[city_name]
           ,[town_name]
           ,[house_number]
           ,[building_name]
           ,[phone_number]
           ,[cellphone_number]
           ,[dm_receive_type]
           ,[mail_receive_type]
           ,[job_type]
           ,[job_name]
           ,[create_at])
     VALUES
           (:info_registration_id
           ,:tempreg_id
           ,:store_code
           ,EncryptByKey(@kGuid, :family_name)
           ,EncryptByKey(@kGuid, :first_name)
           ,EncryptByKey(@kGuid, :middle_name)
           ,EncryptByKey(@kGuid, :family_name_kana)
           ,EncryptByKey(@kGuid, :first_name_kana)
           ,EncryptByKey(@kGuid, :middle_name_kana)
           ,EncryptByKey(@kGuid, :mail_address)
           ,EncryptByKey(@kGuid, :birthday)
           ,:gender_type
           ,:country_type
           ,:post_code_left
           ,:post_code_right
           ,EncryptByKey(@kGuid, :prefectures_name)
           ,EncryptByKey(@kGuid, :city_name)
           ,EncryptByKey(@kGuid, :town_name)
           ,EncryptByKey(@kGuid, :house_number)
           ,EncryptByKey(@kGuid, :building_name)
           ,EncryptByKey(@kGuid, :phone_number)
           ,EncryptByKey(@kGuid, :cellphone_number)
           ,:dm_receive_type
           ,0
           ,:job_type
           ,:job_name
           ,GETDATE());
EOM;
        

    //事前登録情報更新SQL定義
    $sqlRegist=<<< EOF
UPDATE [mrhn].[info_registrations]
   SET [reg_status] = :reg_status
      ,[update_at] = GETDATE()
 WHERE tempreg_id = :tempreg_id;
EOF;

    //事前登録会員情報追加パラメタ
    $aryMenberParams[] = array('info_registration_id',$id,'string');
    $aryMenberParams[] = array('tempreg_id',$data['tempRegId'],'string');
//    $aryMenberParams[] = array('store_code',$data['tenpoCd'],'string');
    $aryMenberParams[] = array('store_code',str_pad($data['tenpoCd'], 3, 0, STR_PAD_LEFT),'string');
    $aryMenberParams[] = array('family_name',$data['family_name'],'string');
    $aryMenberParams[] = array('first_name',$data['first_name'],'string');
    $aryMenberParams[] = array('middle_name',$data['middle_name'],'string');
    $aryMenberParams[] = array('family_name_kana',mb_convert_kana($data['family_name_kana'],'k'),'string');
    $aryMenberParams[] = array('first_name_kana',mb_convert_kana($data['first_name_kana'],'k'),'string');
    $aryMenberParams[] = array('middle_name_kana',mb_convert_kana($data['middle_name_kana'],'k'),'string');
    $aryMenberParams[] = array('mail_address',$data['mail_address'],'string');
    $aryMenberParams[] = array('birthday',$data['birthday']['year'].'/'.$data['birthday']['month'].'/'.$data['birthday']['day'],'string');
    $aryMenberParams[] = array('gender_type',$data['gender_type'],'string');
    $aryMenberParams[] = array('country_type',$data['country_type'],'string');
    $aryMenberParams[] = array('post_code_left',$data['post_code_left'],'string');
    $aryMenberParams[] = array('post_code_right',$data['post_code_right'],'string');
    $aryMenberParams[] = array('prefectures_name',$data['prefectures_name'],'string');
    
    $aryMenberParams[] = array('city_name',$data['city_name'],'string');
    $aryMenberParams[] = array('town_name',$data['town_name'],'string');
    $aryMenberParams[] = array('house_number',$data['house_number'],'string');
    $aryMenberParams[] = array('building_name',$data['building_name'],'string');
    $aryMenberParams[] = array('phone_number',$data['phone_number'],'string');
    $aryMenberParams[] = array('cellphone_number',$data['cellphone_number'],'string');
    $aryMenberParams[] = array('dm_receive_type',$data['dm_receive_type'],'string');
    if(empty($data['job_type'])){
      $aryMenberParams[] = array('job_type',0,'string');
    }else{
      $aryMenberParams[] = array('job_type',$data['job_type'],'string');
    }
    if($data['job_type']=='9'){
      $aryMenberParams[] = array('job_name',$data['other_job'],'string');
    }else{
      $aryMenberParams[] = array('job_name',$data['job_name'],'string');
    }
    //事前登録情報更新パラメタ
    $aryRegistParams[] = array('reg_status','02','string');
    $aryRegistParams[] = array('tempreg_id',$data['tempRegId'],'string');

    try{

      //データベースコネクト
      $sqlManager = new SqlManager('default');
      //暗号化対応
      $compSqlMember=$sqlManager->makeCryptSql($sqlMember);

      //トランザクション処理
      $sqlManager->begin();

      //SQLセット
      $sqlManager->setQueries($compSqlMember);
      $sqlManager->setQueries($sqlRegist);
      //パラメタセット
      $sqlManager->setParams($aryMenberParams);
      $sqlManager->setParams($aryRegistParams);

      $result = $sqlManager->commit();
      if($result!=1){
        return $this->redirect(array('controller' => 'Error', 'action' => 'index',310,$data['tempRegId']));
      }else{
        $req = array('tenpoCd'=>$data['tenpoCd'],'tempRegId'=>$data['tempRegId']);
        $this->set($req);
      }

    }catch(Exception $e) {
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',311,$data['tempRegId']));
    }

    //メール送信
    $basedir = dirname(dirname(dirname(__FILE__)));
    $version=null;
    preg_match("/(Android\s([0-9\.]*))/", $_SERVER['HTTP_USER_AGENT'], $android);
    if (count($android)){
       $version = $android[2];
    }
    //Windowsの場合はpopen関数で非同期実行
    if (strpos(PHP_OS, 'WIN')!==false) {
    	//「start」コマンドで非同期実行
    	pclose(popen('start '.$basedir.'\bin\cake RegistersMail send '.$data['tenpoCd'].' '.$data['tempRegId'].' '.$this->referer().' '.$version, 'r'));
        pclose(popen('taskkill  /f /im cmd.exe', 'r'));
    //Linuxの場合はexec関数で非同期実行
    } else {
	//「>」で出力先指定(＊出力先はnullなので出力しない)
	//「&」で非同期実行
	exec($basedir.'\bin\cake RegistersMail send '.$data['tenpoCd'].' '.$data['tempRegId'].' '.$this->referer().' '.$version.' > /dev/null &');
    }
/*
//#####メール送信
    $mailResult='01';
    $mail = new MailUtil("");
      try {
        //ガラケーの場合
        if(AppUtility::getDeviceType()=="03"){
          $mail->sendFinishMail($data['mail_address'], array("tenpoCd" => $data['tenpoCd'],"tempRegId" => $data['tempRegId']));
        }else{
           //ガラケー以外の場合
          $mail->sendTemplateMail($data['mail_address'],"仮登録完了のお知らせ","complete",array("tenpoCd" => $data['tenpoCd'],"tempRegId" => $data['tempRegId']));	
        }
      } catch(BadMethodCallException $e) {
        $mailResult='02';
      }	

//#####メールログ書き込み
	
$sqlManager = new SqlManager('default');
//mail_logsへinsert
      $sql_mail=<<< EOM
INSERT 
INTO mrhn.mail_logs(
   [send_type]
  ,[send_status]
  ,[send_date]
  ,[mail_address]
  ,[create_at]
)
VALUES(
  :send_type
 , :send_status
 , GETDATE()
 , EncryptByKey(@kGuid, :mail_address)
 , GETDATE()
);
EOM;
      //SQLを暗号化対応に整形
      $compSql_mail=$sqlManager->makeCryptSql($sql_mail);

      //バインド変数生成
      $aryParams_mail[] = array('send_type','02','string');
      $aryParams_mail[] = array('send_status',$mailResult,'string');
      $aryParams_mail[] = array('mail_address',$data['mail_address'],'string');

      $insertCnt=$sqlManager->insert($compSql_mail, $aryParams_mail);

	//ログ出力

	$log_content[] = array('controller','Registers');
	$log_content[] = array('mail_address',$data['mail_address']);
	$log_content[] = array('tempRegId',$data['tempRegId']);
	Log::info($log_content,['scope' => ['mail']]);
*/
  }

  public function send($tenpoCd,$tempRegId){

    $log_error= null;
    if($this->referer()!='https://m.maruhan.co.jp/registers/complete'){
      $log_error[]=array('error_type'=>'referer error','controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
      //Log::error($log_error,['scope' => ['mail_error']]);
    }

/*----- AppControllerにてチェック済みのためコメントアウト
	//店舗コード数値＆桁数チェック	
    if(!AppUtility::checkDigitsTenpoCd($tenpoCd)){
      $errorNo = 913;
      //$this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
    }
	//仮会員ID数値＆桁数チェック	
    if(!AppUtility::checkDigitsTempRegId($tempRegId)){
      $errorNo = 914;
      //$this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
    }
---------------*/

    //SQL実行モジュール
    $sqlManager = new SqlManager('default');

    //メールアドレス取得
    $sql=<<< EOM
  SELECT TOP 1 CONVERT( nvarchar(max), DecryptByKey([mail_address])) AS [mail_address]
  FROM [mrhn].[info_registrations]
  WHERE [tempreg_id] = :tempreg_id
  ORDER BY [create_at] DESC;
EOM;
    $compSql=$sqlManager->makeCryptSql($sql);
    //バインド変数生成
    $aryParams_info[] = array('tempreg_id',$tempRegId,'string');
    $result=$sqlManager->select($compSql, $aryParams_info);

    $mail_address=$result[0]['mail_address'];

    if(empty($mail_address)){
      $log_error[]=array('error_type'=>'no data error','controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
    }
    
    //メール送信
    $mailResult='01';
    $mail = new MailUtil("");
    try {
      //ガラケーの場合
      if(AppUtility::getDeviceType()=="03"){
        $mail->sendFinishMail($mail_address, array("tenpoCd" => $tenpoCd,"tempRegId" => $tempRegId));
      }else{
         //ガラケー以外の場合
        $mail->sendTemplateMail($mail_address,"仮登録完了のお知らせ","complete",array("tenpoCd" => $tenpoCd,"tempRegId" => $tempRegId));	
      }
    } catch(BadMethodCallException $e) {
      $mailResult='02';
      $log_error[]=array('error_type'=>'mail send error:'.$e->getMessage(),'controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
    }

    //mail_logsへinsert
    $sql_mail=<<< EOM
INSERT 
INTO mrhn.mail_logs(
   [send_type]
  ,[send_status]
  ,[send_date]
  ,[mail_address]
  ,[create_at]
)
VALUES(
  :send_type
 , :send_status
 , GETDATE()
 , EncryptByKey(@kGuid, :mail_address)
 , GETDATE()
);
EOM;
    //SQLを暗号化対応に整形
    $compSql_mail=$sqlManager->makeCryptSql($sql_mail);

    //バインド変数生成
    $aryParams_mail[] = array('send_type','02','string');
    $aryParams_mail[] = array('send_status',$mailResult,'string');
    $aryParams_mail[] = array('mail_address',$mail_address,'string');
    $insertCnt=$sqlManager->insert($compSql_mail, $aryParams_mail);

    if($insertCnt!=1){
      $log_error[]=array('error_type'=>'maillog insert error','controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
    }

    if(count($log_error)>0){
      Log::error($log_error,['scope' => ['mail_error']]);
    }
    $file="D:\apl\htdocs\img\dummy.gif";
    $this->response->type('image/gif');
    readfile($file);
    exit;
  }


  
}
?>