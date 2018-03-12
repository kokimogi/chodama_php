<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Form\PolicyForm;
use App\Utils\AppUtility;
use Cake\Event\Event;

class PolicysController extends AppController
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

    //“X•ÜƒR[ƒhŽæ“¾
    $tenpoCd=null;
    if(isset($this->request->params['pass'][0])){
      $tenpoCd=$this->request->params['pass'][0];
    }elseif(isset($this->request->data['tenpoCd'])){
      $tenpoCd=$this->request->data['tenpoCd'];
    }
    if($tenpoCd==null){
          return $this->redirect(array('controller' => 'Error', 'action' => 'index',100));
    }
    if(!AppUtility::checkDigitsTenpoCd($tenpoCd)){
      if(isset($this->request->params['pass'][0])){
          $errorNo = 101;
          return $this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
      }
    }

  }

  public function index($tenpoCd=null){
   $this->request->data['tenpoCd']=$tenpoCd;
   return $this->setAction('introduction');
  }

  public function agreement(){
    $session = $this->request->session();
    if(empty($session->read('token'))){
      $this->redirect(array('controller' => 'Error', 'action' => 'index',102,$this->request->data['tenpoCd']));
    }

    $tenpoCd=$this->request->data['tenpoCd'];
    $policy = new PolicyForm();
    if (isset($this->request->data['confirm_check'])) {
      if ($policy->execute($this->request->data)) {
        return $this->redirect('/inforegistrations/index/'.$tenpoCd);
      }
    }
    if(AppUtility::getDeviceType()=="03"){
    	$csrfTemp=null;
    	if(isset($_REQUEST['_csrfToken'])){
    	   $csrfTemp=$_REQUEST['_csrfToken'];
    	}
    	$this->set('token', $csrfTemp);
    }
    $this->set('policy', $policy);
    $this->set('tenpoCd', $tenpoCd);
  }


  public function introduction($tenpoCd=null){
    $session = $this->request->session();
    if(isset($this->request->data['tenpoCd'])){
      $tenpoCd=$this->request->data['tenpoCd'];
    }

    $policy = new PolicyForm();
    $this->set('policy', $policy);
    $this->set('tenpoCd', $tenpoCd);

    if(AppUtility::getDeviceType()=="03"){
    	$this->set('token',$this->request->params['_csrfToken']);
    }
    $session->write('token',$this->request->params['_csrfToken']);
  }
  
  public function complete()
  {
  }
}
