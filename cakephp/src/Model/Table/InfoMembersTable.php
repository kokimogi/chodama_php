<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InfoMembersTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('info_members');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->belongsTo('InfoRegistrations', [
            'foreignKey' => 'info_registration_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Tempregs', [
            'foreignKey' => 'tempreg_id',
            'joinType' => 'INNER'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->requirePresence('id', 'create')
            ->notEmpty('id');

        $validator
            ->requirePresence('store_code', 'create')
            ->notEmpty('store_code');

        $validator
            ->requirePresence('family_name', 'create')
            ->notEmpty('family_name');

        $validator
            ->requirePresence('first_name', 'create')
            ->notEmpty('first_name');

        $validator
            ->allowEmpty('middle_name');

        $validator
            ->allowEmpty('family_name_kana');

        $validator
            ->allowEmpty('first_name_kana');

        $validator
            ->allowEmpty('middle_name_kana');

        $validator
            ->requirePresence('mail_address', 'create')
            ->notEmpty('mail_address');

        $validator
            ->requirePresence('birthday', 'create')
            ->notEmpty('birthday');

        $validator
            ->allowEmpty('gender_type');

        $validator
            ->allowEmpty('country_type');

        $validator
            ->requirePresence('post_code_left', 'create')
            ->notEmpty('post_code_left');

        $validator
            ->requirePresence('post_code_right', 'create')
            ->notEmpty('post_code_right');

        $validator
            ->requirePresence('prefectures_name', 'create')
            ->notEmpty('prefectures_name');

        $validator
            ->requirePresence('city_name', 'create')
            ->notEmpty('city_name');

        $validator
            ->requirePresence('town_name', 'create')
            ->notEmpty('town_name');

        $validator
            ->allowEmpty('house_number');

        $validator
            ->allowEmpty('building_name');

        $validator
            ->allowEmpty('phone_number');

        $validator
            ->allowEmpty('cellphone_number');

        $validator
            ->allowEmpty('dm_receive_type');

        $validator
            ->allowEmpty('job_type');

        $validator
            ->allowEmpty('job_name');

        $validator
            ->allowEmpty('dm_post_code_left');

        $validator
            ->allowEmpty('dm_post_code_right');

        $validator
            ->allowEmpty('dm_prefectures_name');

        $validator
            ->allowEmpty('dm_city_name');

        $validator
            ->allowEmpty('dm_town_name');

        $validator
            ->allowEmpty('dm_house_number');

        $validator
            ->allowEmpty('dm_building_name');

        $validator
            ->allowEmpty('question_code');

        $validator
            ->dateTime('create_at')
            ->requirePresence('create_at', 'create')
            ->notEmpty('create_at');

        $validator
            ->dateTime('update_at')
            ->allowEmpty('update_at');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['info_registration_id'], 'InfoRegistrations'));
        $rules->add($rules->existsIn(['tempreg_id'], 'Tempregs'));

        return $rules;
    }
}
