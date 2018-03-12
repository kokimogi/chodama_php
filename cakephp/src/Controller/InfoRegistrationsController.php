<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Form\MailForm;
use App\Utils\AppUtility;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use App\Utils\SqlManager;
use App\Utils\MailUtil;
use Cake\Log\Log;
use Cake\Network\Email\Email;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;

class InfoRegistrationsController extends AppController
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
  
  public function index()
  {
    $session = $this->request->session();
    //リクエストデータ取得
    $reqData=$this->request->data;
    //店舗コード
    $tenpoCd=null;
    if(isset($this->request->params['pass'][0])){
      $tenpoCd=$this->request->params['pass'][0];
    }elseif(isset($reqData['tenpoCd'])){
      $tenpoCd=$reqData['tenpoCd'];
    }else{
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',201));
    }
    //事前登録ID生成
    $tempRegId=AppUtility::makeTempRegId($tenpoCd);
    //トークン確認
    if(AppUtility::getDeviceType()=="03"){
      if($_POST['_csrfToken']!=$session->read('token')){
          return $this->redirect(array('controller' => 'Error', 'action' => 'index',200,$tempRegId));
      }
    }else{
       if($this->request->params['_csrfToken']!=$session->read('token')){
      	  return $this->redirect(array('controller' => 'Error', 'action' => 'index',200,$tempRegId));
       }
    }

	//店舗コード数値＆桁数チェック	
/*
    if(!AppUtility::checkDigitsTenpoCd($tenpoCd)){
	$errorNo = 13;
	$this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
    }
*/
    $query = new MailForm();
    if (isset($this->request->data['mail_address'])){
      //メールアドレスチェック
      if ($query->execute($reqData)) {
        //更新処理
        $sqlManager = new SqlManager('default');
        $sql=<<< EOM
INSERT 
INTO mrhn.info_registrations( 
    [reg_type]
  , [reg_status]
  , [store_code]
  , [mail_address]
  , [tempreg_id]
  , [request_date]
  , [ip_address]
  , [create_at]
) 
VALUES ( 
  :reg_type
  , :reg_status
  , :store_code
  , EncryptByKey(@kGuid, :mail_address)
  , :tempreg_id
  , GETDATE()
  , :ip_address
  , GETDATE()
); 
EOM;
        //SQLを暗号化対応に整形
        $compSql=$sqlManager->makeCryptSql($sql);
        //バインド変数生成
        $aryParams[] = array('reg_type',AppUtility::getDeviceType(),'string');
        $aryParams[] = array('reg_status','01','string');
        $aryParams[] = array('store_code',str_pad($reqData['tenpoCd'], 3, 0, STR_PAD_LEFT),'string');
        $aryParams[] = array('mail_address',$reqData['mail_address'],'string');
        $aryParams[] = array('tempreg_id',$tempRegId,'string');
        $aryParams[] = array('ip_address',$_SERVER['REMOTE_ADDR'],'string');

        $insertCnt=$sqlManager->insert($compSql, $aryParams);

        //メールアドレスログ保管
        //$log_content[] = array('コントローラ','Inforegistrations');
        //$log_content[] = array('メールアドレス',$this->request->data['mail_address']);
        
        //メールアドレス退避
        $infoMail = $this->request->data['mail_address'];

        //メールアドレス削除
        $this->request->data['mail_address']="";

        if($insertCnt==1){
          $req = array('tenpoCd'=>$tenpoCd,'tempRegId'=>$tempRegId,'nextURL'=>"https://m.maruhan.co.jp/registers/index/".$tenpoCd."/".$tempRegId);
          $this->set($req);
          $url = $req['nextURL'];
          $this->set('url',$url);
          //リンクURLログ保管
          //$log_content[] = array('リンクURL',$req['tempRegId']);
          
          //ログにメールアドレス、リンクURL保管
          //Log::info($log_content,['scope' => ['mail']]);
          $this->request->data['mail_address'] = $infoMail;
          $this->request->data['tempRegId'] = $tempRegId;
          $this->request->data['nextURL'] = "https://m.maruhan.co.jp/registers/index/".$tenpoCd."/".$tempRegId;

	$basedir = dirname(dirname(dirname(__FILE__)));
    	//Windowsの場合はpopen関数で非同期実行
    	if (strpos(PHP_OS, 'WIN')!==false) {
    		//「start」コマンドで非同期実行
    		pclose(popen('start '.$basedir.'\bin\cake InfoRegistrationsMail send '.$tenpoCd.' '.$tempRegId.' '.$this->referer().' exit', 'r'));
		pclose(popen('taskkill  /f /im cmd.exe', 'r'));
    	//Linuxの場合はexec関数で非同期実行
    	} else {
    		//「>」で出力先指定(＊出力先はnullなので出力しない)
		//「&」で非同期実行
		exec($basedir.'\bin\cake InfoRegistrationsMail send '.$tenpoCd.' '.$tempRegId.' '.$this->referer().' > /dev/null &');
    	}    

          return $this->setAction('complete');
        }else{
          return $this->redirect(array('controller' => 'Error', 'action' => 'index',202,$tempRegId));
        }
      }
    }

    $req = array('tenpoCd'=>$tenpoCd);
    $this->set($req); 
    $this->set('query', $query);
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
    if(AppUtility::getDeviceType()=="03"){
      if($_POST['_csrfToken']!=$session->consume('token')){
          return $this->redirect(array('controller' => 'Error', 'action' => 'index',203,$this->request->data['tempRegId']));
      }
    }else{
       if($this->request->params['_csrfToken']!=$session->consume('token')){
      	  return $this->redirect(array('controller' => 'Error', 'action' => 'index',203,$this->request->data['tempRegId']));
       }
    }
  }

//  public function send($tenpoCd,$tempRegId,$mail_address){
  public function send($tenpoCd,$tempRegId){

	$log_error= null;
    if($this->referer()!='https://m.maruhan.co.jp/inforegistrations'){
      $log_error[]=array('error_type'=>'referer error','controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
      //Log::error($log_error,['scope' => ['mail_error']]);
    }
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
      $mail->sendTextMail($mail_address, array("tenpoCd" => $tenpoCd,"tempRegId" => $tempRegId));
    } catch(BadMethodCallException $e) {
      $mailResult='02';
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
    $aryParams_mail[] = array('send_type','01','string');
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
