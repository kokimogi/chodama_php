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
    //���N�G�X�g�f�[�^�擾
    $reqData=$this->request->data;
    //�X�܃R�[�h
    $tenpoCd=null;
    if(isset($this->request->params['pass'][0])){
      $tenpoCd=$this->request->params['pass'][0];
    }elseif(isset($reqData['tenpoCd'])){
      $tenpoCd=$reqData['tenpoCd'];
    }else{
      return $this->redirect(array('controller' => 'Error', 'action' => 'index',201));
    }
    //���O�o�^ID����
    $tempRegId=AppUtility::makeTempRegId($tenpoCd);
    //�g�[�N���m�F
    if(AppUtility::getDeviceType()=="03"){
      if($_POST['_csrfToken']!=$session->read('token')){
          return $this->redirect(array('controller' => 'Error', 'action' => 'index',200,$tempRegId));
      }
    }else{
       if($this->request->params['_csrfToken']!=$session->read('token')){
      	  return $this->redirect(array('controller' => 'Error', 'action' => 'index',200,$tempRegId));
       }
    }

	//�X�܃R�[�h���l�������`�F�b�N	
/*
    if(!AppUtility::checkDigitsTenpoCd($tenpoCd)){
	$errorNo = 13;
	$this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
    }
*/
    $query = new MailForm();
    if (isset($this->request->data['mail_address'])){
      //���[���A�h���X�`�F�b�N
      if ($query->execute($reqData)) {
        //�X�V����
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
        //SQL���Í����Ή��ɐ��`
        $compSql=$sqlManager->makeCryptSql($sql);
        //�o�C���h�ϐ�����
        $aryParams[] = array('reg_type',AppUtility::getDeviceType(),'string');
        $aryParams[] = array('reg_status','01','string');
        $aryParams[] = array('store_code',str_pad($reqData['tenpoCd'], 3, 0, STR_PAD_LEFT),'string');
        $aryParams[] = array('mail_address',$reqData['mail_address'],'string');
        $aryParams[] = array('tempreg_id',$tempRegId,'string');
        $aryParams[] = array('ip_address',$_SERVER['REMOTE_ADDR'],'string');

        $insertCnt=$sqlManager->insert($compSql, $aryParams);

        //���[���A�h���X���O�ۊ�
        //$log_content[] = array('�R���g���[��','Inforegistrations');
        //$log_content[] = array('���[���A�h���X',$this->request->data['mail_address']);
        
        //���[���A�h���X�ޔ�
        $infoMail = $this->request->data['mail_address'];

        //���[���A�h���X�폜
        $this->request->data['mail_address']="";

        if($insertCnt==1){
          $req = array('tenpoCd'=>$tenpoCd,'tempRegId'=>$tempRegId,'nextURL'=>"https://m.maruhan.co.jp/registers/index/".$tenpoCd."/".$tempRegId);
          $this->set($req);
          $url = $req['nextURL'];
          $this->set('url',$url);
          //�����NURL���O�ۊ�
          //$log_content[] = array('�����NURL',$req['tempRegId']);
          
          //���O�Ƀ��[���A�h���X�A�����NURL�ۊ�
          //Log::info($log_content,['scope' => ['mail']]);
          $this->request->data['mail_address'] = $infoMail;
          $this->request->data['tempRegId'] = $tempRegId;
          $this->request->data['nextURL'] = "https://m.maruhan.co.jp/registers/index/".$tenpoCd."/".$tempRegId;

	$basedir = dirname(dirname(dirname(__FILE__)));
    	//Windows�̏ꍇ��popen�֐��Ŕ񓯊����s
    	if (strpos(PHP_OS, 'WIN')!==false) {
    		//�ustart�v�R�}���h�Ŕ񓯊����s
    		pclose(popen('start '.$basedir.'\bin\cake InfoRegistrationsMail send '.$tenpoCd.' '.$tempRegId.' '.$this->referer().' exit', 'r'));
		pclose(popen('taskkill  /f /im cmd.exe', 'r'));
    	//Linux�̏ꍇ��exec�֐��Ŕ񓯊����s
    	} else {
    		//�u>�v�ŏo�͐�w��(���o�͐��null�Ȃ̂ŏo�͂��Ȃ�)
		//�u&�v�Ŕ񓯊����s
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
    //�g�[�N���`�F�b�N
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
    //SQL���s���W���[��
    $sqlManager = new SqlManager('default');

    //���[���A�h���X�擾
    $sql=<<< EOM
  SELECT TOP 1 CONVERT( nvarchar(max), DecryptByKey([mail_address])) AS [mail_address]
  FROM [mrhn].[info_registrations]
  WHERE [tempreg_id] = :tempreg_id
  ORDER BY [create_at] DESC;
EOM;
    $compSql=$sqlManager->makeCryptSql($sql);
    //�o�C���h�ϐ�����
    $aryParams_info[] = array('tempreg_id',$tempRegId,'string');
    $result=$sqlManager->select($compSql, $aryParams_info);

    $mail_address=$result[0]['mail_address'];

    if(empty($mail_address)){
      $log_error[]=array('error_type'=>'no data error','controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
    }

    //���[�����M
    $mailResult='01';
    $mail = new MailUtil("");
    try {
      $mail->sendTextMail($mail_address, array("tenpoCd" => $tenpoCd,"tempRegId" => $tempRegId));
    } catch(BadMethodCallException $e) {
      $mailResult='02';
      $mailResult='02';
      $log_error[]=array('error_type'=>'mail send error:'.$e->getMessage(),'controller'=>$this->request->params['controller'],'date'=>date( "Y/m/d/ H:i:s" ),'referer'=>$this->referer(),'tempRegId'=>$tempRegId);
    }

    //mail_logs��insert
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
    //SQL���Í����Ή��ɐ��`
    $compSql_mail=$sqlManager->makeCryptSql($sql_mail);

    //�o�C���h�ϐ�����
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
