<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use App\Utils\AppUtility;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */

    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
	//$this->InfoReg = TableRegistry::get('m_stores');
	//$this->set('entity',$this->InfoRegnewEntity());
	
        /*
         * Enable the following components for recommended CakePHP security settings.
         * see http://book.cakephp.org/3.0/en/controllers/components/security.html
         */
        $this->loadComponent('Security');
        //$this->loadComponent('Csrf');
        $this->loadComponent('Email');
        $deviceType=AppUtility::getDeviceType();
        if($deviceType=="03"){
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

    public function afterFilter(Event $event)
    {    
	//if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false || $this -> is_imode_browser_v1($_SERVER['HTTP_USER_AGENT']) === true){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
	}else {
		$this->output = mb_convert_encoding($this->output,'Shift_JIS','UTF-8');
	}
    }


    public function beforeFilter(Event $event)
    {
      $maintenanceFlg=Configure::read('MAINTENANCE');
      $auris_ip=Configure::read('AURIS_IP');
      $deviceType=AppUtility::getDeviceType();
      $now=strtotime("now");
      $startTime=strtotime(Configure::read('MAINTENANCE_FROM'))-1;
      $fromTime=strtotime(Configure::read('MAINTENANCE_TO'));

      if($maintenanceFlg=="1" && !in_array($_SERVER["REMOTE_ADDR"],$auris_ip) && $now > $startTime && $now <=$fromTime){
        if($deviceType=="03"){
            if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
                $this->redirect("/maintenance_au.html");
            }else{
                $this->redirect("/maintenance_fp.html");
            }
        }else{
            $this->redirect("/maintenance.html");
        }
      }
      if($deviceType=="03"){
        $data=$this->request->data;
        if(count($data)>0){
          foreach($data as $key=>$value){
            if(!is_array($value)){
                  $this->request->data[$key]=mb_convert_encoding($value,"UTF-8","SJIS-WIN");
            }
          }
        }
      }
      //店舗コードチェック
      $tenpoCd=null;
      if(isset($this->request->params['pass'][0])){
        $tenpoCd=$this->request->params['pass'][0];
      }elseif(isset($this->request->data['tenpoCd'])){
        $tenpoCd=$this->request->data['tenpoCd'];
      }

      if(!self::checkStoreCode($tenpoCd)){
        $errorNo = 2;
        $this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
      }

      //リンクURL有効期限確認
      if($this->request->params['controller']=="Registers" || $this->request->params['action'] =='send'){
        $tempRegId=null;
        if(isset($this->request->params['pass'][1])){
          $tempRegId=$this->request->params['pass'][1];
        }elseif(isset($this->request->data['tempRegId'])){
          $tempRegId=$this->request->data['tempRegId'];
        }
        if(!self::checkTime($tempRegId)){
        $errorNo = 1;
	      $this->redirect(array('controller' => 'Error', 'action' => 'index',$errorNo));
        }
    }
    $this->set('tenpoName', AppUtility::getStoreName($tenpoCd));
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
	$this->set('tenpoName', mb_convert_encoding(AppUtility::getStoreName($tenpoCd),"Shift_JIS","UTF-8"));
    }
}
    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeRender(Event $event)
    {
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
        /**
        * デバイス判定対応
        */
        if(AppUtility::getDeviceType()=="03"){
            Configure::write('App.encoding','shift_jis');
	    $this->viewBuilder()->layout('chodama_m');
            $this->viewBuilder()->template($this->request->action."_m");
            if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
                $this->response->charset('SHIFT_JIS');
            	$this->viewBuilder()->template($this->request->action."_mau");
            }
        }

        
    }
    	
/*
* 関数名::店舗コード存在確認機能
* @param::inforegistrations.store_code
* @return::boolean型
*/	
  public static function  checkStoreCode($storeCode){

    if(!AppUtility::checkDigitsTenpoCd($storeCode)){
      return false;
    }
    $InfoReg = TableRegistry::get('m_stores')->newEntity();
    $InfoReg = TableRegistry::get('m_stores')->find();
    $InfoReg->where(['store_code' => $storeCode,'delete_flg' => 0]);
    if($InfoReg->count()!=1){
      return false;
    }
    return true;

  }

/*
* 関数名::登録日時超過識別
* @param::inforegistrations.id,メールアドレス(複合化)
* @return::boolean型
*/
  public static function checkTime($tempreg_id){
    //$InfoReg = TableRegistry::get('InfoRegistrations')->newEntity();
    $InfoReg = TableRegistry::get('InfoRegistrations')->find();
    $InfoReg->where(['tempreg_id' => $tempreg_id]);
    foreach ($InfoReg as $timeInfo) {
      //timeオブジェクトをYYY-MM-dd-hh形式に変換
      $time = new Time($timeInfo['create_at']);
      //時間超過判断
      $hour = Configure::read('LIMITTIME');
      $hour .= " hours";
      if($time->wasWithinLast($hour)){
        //超過していない場合
        return true;
      }else{
        //超過している場合
        return false;
      }
    }
    return false;
  }




}
