<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InfoRegistrationsTable extends Table
{

  public function initialize(array $config)
  {
    parent::initialize($config);

    $this->table('info_registrations');
    $this->displayField('mail_address');
    $this->primaryKey('id');

    $this->belongsTo('Tempregs', [
      'foreignKey' => 'tempreg_id'
    ]);
    $this->hasMany('InfoMembers', [
      'foreignKey' => 'info_registration_id'
    ]);
  }

  public function validationDefault(Validator $validator)
  {
    $validator
      ->integer('id')
      ->requirePresence('id', 'create')
      ->notEmpty('id');

    $validator
      ->requirePresence('reg_type', 'create')
      ->notEmpty('reg_type');

    $validator
      ->requirePresence('reg_status', 'create')
      ->notEmpty('reg_status');

    $validator
      ->requirePresence('store_code', 'create')
      ->notEmpty('store_code');

    $validator
      ->requirePresence('mail_address', 'create')
      ->notEmpty('mail_address', 'メールアドレスを必ず入力してください。')
      ->add('mail_address', 'maxLen', [
            'rule' => ['maxlength', 254],
            'message' => 'メールアドレスは254桁以内で入力してください。'
      ])
      ->add('mail_address', 'email', [
            'rule' => 'email',
            'message' => 'メールアドレスの形式が不正です'
      ]);

    $validator
      ->dateTime('request_date')
      ->requirePresence('request_date', 'create')
      ->notEmpty('request_date');

    $validator
            ->allowEmpty('ip_address');

        $validator
            ->allowEmpty('s_mem_no');

        $validator
            ->allowEmpty('s_password');

        $validator
            ->allowEmpty('s_posno');

        $validator
            ->dateTime('s_posentryd')
            ->allowEmpty('s_posentryd');

        $validator
            ->allowEmpty('s_identifycode');

        $validator
            ->allowEmpty('s_identifyinfo');

        $validator
            ->allowEmpty('s_identifydateinfo');

        $validator
            ->allowEmpty('s_identifynoinfo');

        $validator
            ->allowEmpty('s_identifystaffcode');

        $validator
            ->allowEmpty('s_identifystaffname');

        $validator
            ->dateTime('create_at')
            ->requirePresence('create_at', 'create')
            ->notEmpty('create_at');

        $validator
            ->dateTime('update_at')
            ->allowEmpty('update_at');

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['tempreg_id'], 'Tempregs'));

        return $rules;
    }
}
