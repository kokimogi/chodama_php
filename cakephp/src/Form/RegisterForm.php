<?php

namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Cake\Form\DateTime;
use Cake\I18n\Time;

class RegisterForm extends Form
{

  // フォームのスキーマを定義する
  protected function _buildSchema(Schema $schema)
  {
    return $schema->addField('family_name', 'string')
                  ->addField('first_name', 'string')
                  ->addField('middle_name', 'string')
                  ->addField('family_name_kana', 'string')
                  ->addField('first_name_kana', 'string')
                  ->addField('middle_name_kana', 'string')
                  ->addField('mail_address', 'string')
                  ->addField('birthday', 'string')
                  ->addField('gender_type', 'string')
                  ->addField('dm_receive_type', 'string')
                  ->addField('post_code_left', 'string')
                  ->addField('post_code_right', 'string')
                  ->addField('prefectures_name', 'string')
                  ->addField('city_name', 'string')
                  ->addField('town_name', 'string')
                  ->addField('house_number', 'string')
                  ->addField('building_name', 'string')
                  ->addField('phone_number', 'string')
                  ->addField('cellphone_number', 'string')
                  ->addField('job_type', 'string')
                  ->addField('job_name', 'string')
//                ->addField('password', '')
                  ->addField('confirm_check', 'string')
                  ->addField('tenpoCd', 'string')
                  ->addField('tempRegId', 'string')
    ;
  }

  // バリデーション内容を定義する
  protected function _buildValidator(Validator $validator)
  {
    return $validator
    // 必須チェック
    ->notEmpty('family_name', 'お名前(姓)を必ず入力してください。')
    ->notEmpty('first_name', 'お名前(名)を必ず入力してください。')
    ->notEmpty('family_name_kana', 'フリガナ(セイ)を必ず入力してください。')
    ->notEmpty('first_name_kana', 'フリガナ(メイ)を必ず入力してください。')
    ->notEmpty('mail_address', 'メールアドレスを必ず入力してください。')
    ->notEmpty('birthday', '生年月日を必ず指定してください。')
    ->notEmpty('gender_type', '性別を必ず選択してください。')
    ->notEmpty('dm_receive_type', 'DM配信を必ず選択してください。')
    ->notEmpty('post_code_left', '郵便番号(上3桁)を必ず入力してください。')
    ->notEmpty('post_code_right', '郵便番号(下4桁)を必ず入力してください。')
    ->notEmpty('prefectures_name', '都道府県を必ず選択してください。')
    ->notEmpty('city_name', '市区町村を必ず入力してください。')
    ->notEmpty('town_name', '町域名を必ず入力してください。')
    ->notEmpty('house_number', '番地を必ず入力してください。')
    ->notEmpty('confirm_check', '利用規約及びプライバシーポリシーに同意してください。')
    ->notEmpty('tenpoCd', '無効なコードが指定されました。')
    ->notEmpty('tempRegId', '無効なコードが指定されました。')
    
    // 未入力可能
    ->allowEmpty('middle_name')
    ->allowEmpty('middle_name_kana')
    ->allowEmpty('country_type')
    ->allowEmpty('building_name')
    ->allowEmpty('phone_number')
    ->allowEmpty('cellphone_number')
    ->allowEmpty('job_type')
    ->allowEmpty('job_name')

    // 桁数チェック
    ->add('family_name', 'maxLen', [
            'rule' => ['maxlength', 32],
            'message' => 'お名前(姓)は32文字以内で入力してください。'
    ])
    ->add('first_name', 'maxLen', [
            'rule' => ['maxlength', 32],
            'message' => 'お名前(名)は32文字以内で入力してください。'
    ])
    ->add('middle_name', 'maxLen', [
            'rule' => ['maxlength', 32],
            'message' => 'ミドルネームは32文字以内で入力してください。'
    ])
    ->add('family_name_kana', 'maxLen', [
            'rule' => ['maxlength', 10],
            'message' => 'フリガナ(セイ)は10文字以内で入力してください。'
    ])
    ->add('first_name_kana', 'maxLen', [
            'rule' => ['maxlength', 10],
            'message' => 'フリガナ(メイ)は10文字以内で入力してください。'
    ])
    ->add('middle_name_kana', 'maxLen', [
            'rule' => ['maxlength', 10],
            'message' => 'フリガナ(ミドルネーム)は10文字以内で入力してください。'
    ])
    ->add('mail_address', 'maxLen', [
            'rule' => ['maxlength', 254],
            'message' => 'メールアドレスは254桁以内で入力してください。'
    ])
    ->add('post_code_left', 'lenBet', [
            'rule' => ['lengthBetween', 3, 3],
            'message' => '郵便番号(上3桁)は3桁で入力してください。'
    ])
    ->add('post_code_right', 'lenBet', [
            'rule' => ['lengthBetween', 4, 4],
            'message' => '郵便番号(下4桁)は4桁で入力してください。'
    ])
    ->add('prefectures_name', 'maxLen', [
            'rule' => ['maxLength', 8],
            'message' => '住所(市区町村)は8文字以内で入力してください。'
    ])
    ->add('city_name', 'maxLen', [
            'rule' => ['maxLength', 8],
            'message' => '住所(市区町村)は8文字以内で入力してください。'
    ])
    ->add('town_name', 'maxLen', [
            'rule' => ['maxLength', 8],
            'message' => '住所(町域名)は8文字以内で入力してください。'
    ])
    ->add('house_number', 'maxLen', [
            'rule' => ['maxLength', 16],
            'message' => '住所(番地)は16文字以内で入力してください。'
    ])
    ->add('building_name', 'maxLen', [
            'rule' => ['maxLength', 16],
            'message' => '住所(建物・アパート名)は16文字以内で入力してください。'
    ])
//    ->add('phone_number', 'lenBet', [
//            'rule' => ['lengthBetween', 12, 12],
//            'message' => '電話番号は12桁(ハイフン付き)で入力してください。'
//    ])
//    ->add('cellphone_number', 'lenBet', [
//            'rule' => ['lengthBetween', 13, 13],
//            'message' => '携帯番号は13桁(ハイフン付き)で入力してください。'
//    ])
    ->add('jobName', 'maxLen', [
            'rule' => ['maxLength', 16],
            'message' => 'ご職業（その他）は16文字以内で入力してください。'
    ])
    ->add('tenpoCd', 'maxLen', [
            'rule' => ['maxLength', 4],
            'message' => '無効なコードが指定されました。'
    ])
    ->add('tempRegId', 'maxLen', [
            'rule' => ['maxLength', 12],
            'message' => '無効なコードが指定されました。'
    ])

    // その他の入力チェック
    ->add('family_name', 'zenkaku_moji', [
            'rule' => [$this, 'zenkaku_moji'],
            'message' => 'お名前(姓)は英数字を除く全角文字のみ入力してください。'
    ])
    ->add('first_name', 'zenkaku_moji', [
            'rule' => [$this, 'zenkaku_moji'],
            'message' => 'お名前(名)は英数字を除く全角文字のみ入力してください。'
    ])
    ->add('middle_name', 'zenkaku_moji', [
            'rule' => [$this, 'zenkaku_moji'],
            'message' => 'お名前(ミドルネーム)は英数字を除く全角文字のみ入力してください。'
    ])
    ->add('family_name_kana', 'zenkaku_kana', [
            'rule' => [$this, 'zenkaku_kana'],
            'message' => 'フリガナ(セイ)は全角カナのみ入力してください。'
    ])
    ->add('first_name_kana', 'zenkaku_kana', [
            'rule' => [$this, 'zenkaku_kana'],
            'message' => 'フリガナ(メイ)は全角カナのみ入力してください。'
    ])
    ->add('middle_name_kana', 'zenkaku_kana', [
            'rule' => [$this, 'zenkaku_kana'],
            'message' => 'フリガナ(ミドルネーム)は全角カナのみ入力してください。'
    ])
    ->add('mail_address', 'email', [
            'rule' => 'email',
            'message' => 'メールアドレスの形式が不正です'
    ])
    ->add('post_code_left', 'hankaku_suuji', [
            'rule' => [$this, 'hankaku_suuji'],
            'message' => '郵便番号(上3桁)は半角数字のみ入力してください。'
    ])
    ->add('post_code_right', 'hankaku_suuji', [
            'rule' => [$this, 'hankaku_suuji'],
            'message' => '郵便番号(下4桁)は半角数字のみ入力してください。'
    ])
    ->add('phone_number', 'phone_format', [
            'rule' => [$this, 'phone_format'],
            'message' => '電話番号は半角数字12桁(ハイフン付き)で入力してください。'
    ])
    ->add('cellphone_number', 'cellphone_format', [
            'rule' => [$this, 'cellphone_format'],
            'message' => '携帯番号は半角数字13桁(ハイフン付き)で入力してください。'
    ])
    ->add('tenpoCd', 'hankaku_suuji', [
            'rule' => [$this, 'hankaku_suuji'],
            'message' => '無効なコードが指定されました。'
    ])
    ->add('tempRegId', 'hankaku_suuji', [
            'rule' => [$this, 'hankaku_suuji'],
            'message' => '無効なコードが指定されました。'
    ])
    ->add('birthday','input_check',[
            'rule'=> [$this,'input_check'],
            'message'=>'生年月日が正しく選択されていません。'
    ])
//    ->add('birthday', 'ymd', [
//            'rule' => ['date', 'ymd'],
//            'message' => '存在しない日が指定されています。'
//    ])
    ->add('birthday','ageCheck',[
            'rule'=> [$this,'ageCheck'],
            'message'=>'18歳未満の方はご登録できません。'
    ])
	;

  
  }
  // 全角文字のみ
  public function zenkaku_moji($value) {
    return (bool) preg_match('/[ぁ-んァ-ヶー一-龠々]+$/u', $value);
  }
  // 半角カナのみ
  public function hankaku_kana($value) {
    return (bool) preg_match('/[ｦ-ﾟ]+$/u', $value);
  }
  // 半角カナのみ
  public function zenkaku_kana($value) {
    return (bool) preg_match('/^[ァ-ヶー]+$/u', $value);
  }
  // 半角数字のみ
  public function hankaku_suuji($value) {
    return (bool) preg_match('/^\d+$/', $value);
  }
  // 電話番号の形式
  public function phone_format($value) {
    return (bool) preg_match('/\A\d{2,5}+-\d{1,4}+-\d{4}\z/', $value);
  }
  // 携帯番号の形式
  public function cellphone_format($value) {
    return (bool) preg_match('/\A\d{3}+-\d{4}+-\d{4}\z/', $value);
  }
  /**
  * 誕生日コンボチェック
  */
  public function input_check($value){
	if(empty($value['year'])){
		return false;
	}elseif(empty($value['month'])){
		return false;
	}elseif(empty($value['day'])){
		return false;
	}
    if(!checkdate($value['month'],$value['day'],$value['year'])){
      return false;
	}
	return true;
  }
  //18才未満チェック(ﾓｷﾞ)
  public function ageCheck($value){
    $formattedYear = null;
    if($this->input_check($value)){
    $formattedTime =$value["year"].'-'.$value["month"].'-'.$value["day"];
    $time = new Time($formattedTime);
    $formattedYear = $time->wasWithinLast('18 year');
	}
    if($formattedYear){
      return false;
    }else{
      return true;
    }
  }



  
  // バリデーション後に実行したい処理を記述する
  protected function _execute(array $data)
  {
    // ※記述
    return true;
  }
}