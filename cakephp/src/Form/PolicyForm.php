<?php

namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class PolicyForm extends Form
{
  protected function _buildSchema(Schema $schema)
  {
    return $schema->addField('confirm_check', 'string')->addField('tenpoCd', 'string');
  }

  protected function _buildValidator(Validator $validator)
  {
    return $validator
    ->add('confirm_check', 'custom', [
          'rule' => [$this, 'ConfirmCheck'],
          'message' => '利用規約及びプライバシーポリシーに同意してください。'
     ])
    ->add('tenpoCd', 'maxlen', [
          'rule' => ['maxlength', 3],
          'message' => '無効なコードが指定されました。'
     ])
     ;
  }

  public function ConfirmCheck($value,$context)
  {
    if ($context['data']['confirm_check']==1) {
      return true;
    } else {
      return false;
    }
  }

  protected function _execute(array $data)
  {
    return true;
  }
}