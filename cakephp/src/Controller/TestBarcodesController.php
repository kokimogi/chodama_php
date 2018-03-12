<?php
namespace App\Controller;

use App\Controller\AppController;
//use App\Form\MailForm;
use App\Utils\AppUtility;
//use Cake\ORM\TableRegistry;
//use Cake\Datasource\ConnectionManager;
//use App\Utils\SqlManager;
//use App\Utils\MailUtil;
use Cake\Log\Log;
//use Cake\Network\Email\Email;

class TestBarcodesController extends AppController
{
  public function initialize()
  {
    $this->loadComponent('Csrf');
    $this->viewBuilder()->layout('chodama');
    $this->set('footer', 'Chodama/footer');
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
         $this->set('footer', 'Chodama/footer_mau');
    }
  }
  
  public function index($tenpoCd=null,$tempRegId=null)
  {
    $session = $this->request->session();
    $session->write('token',$this->request->params['_csrfToken']);
    //店舗コードと仮会員IDをセッションに格納
    if($tenpoCd!=null) $session->write('tenpo.cd',$tenpoCd);
    if($tempRegId!=null) $session->write('member.tempRegId',$tempRegId);
    
    //必要項目退避
    $req = array('tenpoCd'=>$tenpoCd,'tempRegId'=>$tempRegId);
    $this->set($req);
  }
}
