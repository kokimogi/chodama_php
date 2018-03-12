<?php

namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Cake\Network\Email\Email;

class ContactForm extends Form
{

  // お問い合わせフォームのスキーマを定義する
  protected function _buildSchema(Schema $schema)
  {
    return $schema->addField('name', 'string');
  }

  // バリデーション内容を定義する
  protected function _buildValidator(Validator $validator)
  {
    return $validator->add('name', 'length', [
                           'rule' => ['minlength', 10],
                           'message' => '名前は10文字以上入力してください。'
    ])->add('email', 'format', [
            'rule' => 'email',
            'message' => 'メールアドレスを入力してください。',
    ]);
  }

  // バリデーション後に実行したい処理を記述する
  protected function _execute(array $data)
  {
    // メールを送信する
    $email = new Email('default');
    $email->from(['celestian0306@gmail.com' => 'from名'])
          ->to('celestian0306@gmail.com')
          ->subject('タイトル')
          ->send('本文です。');
    return true;
  }
}