<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Event\Event;
use App\Utils\ErrorMessage;
use App\Utils\AppUtility;
use Cake\Log\Log;
use Cake\Network\Exception\NotFoundException;
use App\Controller\AppController;

/**
 * Error Handling Controller
 *
 * Controller used by ExceptionRenderer to render error responses.
 */
class ErrorController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize()
    {
    ini_set('display_errors', 'Off');
    $session = $this->request->session();
    $session->delete('token');
    $session->destroy();
	//$this->name ='エラー';
        //$this->loadComponent('RequestHandler')
	$this->set('title', 'Chodama/title');
	$this->viewBuilder()->layout('chodamaError');
    $this->set('footer', 'Chodama/footer');
    if(AppUtility::getDeviceType()=="03" && strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
         $this->viewBuilder()->layout('chodamaError_mau');
         $this->set('footer', 'Chodama/footer_mau');
    }
    }

    public function index($errorNo=null,$tempRegId=null)
    {
    $message=null;
	if(isset($errorNo)){
		$message= ErrorMessage::getErrorMessage($errorNo);
                if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
			$message=mb_convert_encoding($message,"SJIS","UTF-8");
                }
		$this->set('ErrorMessage',$message);
	}else{
		$errorNo =0;
		$message= ErrorMessage::getErrorMessage($errorNo);
                if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
			$message=mb_convert_encoding($message,"SJIS","UTF-8");
                }
		$this->set('ErrorMessage',$message);
	}
	
    if(!empty($tempRegId)){
        $tenpoId=substr($tempRegId,1,3);
        $this->set('TopUrl',"/policys/index/".$tenpoId);
    }else{
        $this->set('TopUrl',"/");
    }

	
    //エラーログ
	$log_error= null;
    $log_error[]=array('error_type'=>'error page','date'=>date( "Y/m/d/ H:i:s" ),'errorNo'=>$errorNo,'errorMsg'=>$message,'tempRegId'=>$tempRegId);
    Log::error($log_error,['scope' => ['errorPage']]);
	
    }

    /**
     * beforeFilter callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeFilter(Event $event)
    {
    }

    /**
     * beforeRender callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Network\Response|null|void
     */
    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
     //URLがドキュメントルートの場合,オフィシャルサイトへ
    if(empty($this->request->url)){
   		return $this->redirect('https://www.maruhan.co.jp/');
	}
	//文字化け対応
	$message= ErrorMessage::errorPageMessage();
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'KDDI') !== false){
	  if(count($message)>0){
            foreach($message as $key=>$value){
              if(!is_array($value)){
    		  $message[$key]=mb_convert_encoding($value,"SJIS","UTF-8");
              }
            }
          }
       }
	$this->set('error_title',$message[0]);
	$this->set('error_400',$message[1]);
	$this->set('error_500',$message[2]);
	//var_dump($this->response->statusCode());exit;
	//携帯端末の場合で400,500系エラーが発生した場合
	
	if(AppUtility::getDeviceType()=="03"){
			if($this->response->statusCode()<400){
				//getErrorMessage系のエラーテンプレート
				$this->viewBuilder()->templatePath('Error');
			}else if($this->response->statusCode()<500){
				//400系の場合テンプレート切り替え
				$this->viewBuilder()->templatePath('Error');
				$this->viewBuilder()->layout('errorTemp');
				$this->set('content', 'Chodama/content');
				$this->set('ErrorMessage',$message[1]);
				$this->set('ErrorTitle',$message[0]);
				$this->set('PageTop',$message[3]);
			}else{
				//500系の場合
				$this->viewBuilder()->templatePath('Error');
				$this->viewBuilder()->layout('errorTemp');
				$this->set('content', 'Chodama/content');
				$this->set('ErrorMessage',$message[2]);
				$this->set('ErrorTitle',$message[0]);
				$this->set('PageTop',$message[3]);
			}
	}else{
		//PC版、getErrorMessage系のエラーテンプレート
		$this->viewBuilder()->templatePath('Error');
	}
	//500系エラーのURLが入力されると、error500.ctpを表示します。
	if(isset($this->request->params['pass'][0])){
		$errorNo = AppUtility::check500ErrorURL($this->request->params['pass'][0],$this->request->params['controller']);
	}else{
		$errorNo = AppUtility::check500ErrorURL(null,$this->request->params['controller']);
	}
    }

    /**
     * afterFilter callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Network\Response|null|void
     */
    public function afterFilter(Event $event)
    {
    }
}
