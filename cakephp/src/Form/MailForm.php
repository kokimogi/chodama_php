<?php

namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class MailForm extends Form
{
  protected function _buildSchema(Schema $schema)
  {
    return $schema->addField('mail_address', 'string')->addField('tenpoCd', 'string');
  }

  protected function _buildValidator(Validator $validator)
  {
    return $validator
    ->notEmpty('mail_address', 'メールアドレスを必ず入力してください。')
    ->add('mail_address', 'maxLen', [
          'rule' => ['maxlength', 254],
          'message' => 'メールアドレスは254桁以内で入力してください。'
    ])
    ->add('mail_address', 'email', [
          'rule' => 'email',
          'message' => 'メールアドレスの形式が不正です'
    ])
    ->add('tenpoCd', 'decimal', [
          'rule' => ['decimal'],
          'message' => '無効なコードが指定されました'
    ])    ->add('tenpoCd', 'maxLen', [
          'rule' => ['maxlength', 4],
          'message' => '無効なコードが指定されました'
    ]);
  }

  protected function _execute(array $data)
  {
    return true;
  }
}