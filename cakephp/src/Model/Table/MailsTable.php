<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class MailsTable extends Table
{
  public function validationDefault(Validator $validator)
  {

    $validator
      ->notEmpty('mail_address', 'メールアドレスを必ず入力してください。')
      ->add('mail_address', 'maxLen', [
            'rule' => ['maxlength', 255],
            'message' => 'メールアドレスは255桁以内で入力してください。'
      ])
      ->add('mail_address', 'email', [
            'rule' => 'email',
            'message' => 'メールアドレスの形式が不正です'
      ])
    ;
    return $validator;
  }

}