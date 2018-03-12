<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MZipCodes Model
 *
 * @method \App\Model\Entity\MZipCode get($primaryKey, $options = [])
 * @method \App\Model\Entity\MZipCode newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\MZipCode[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MZipCode|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MZipCode patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\MZipCode[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\MZipCode findOrCreate($search, callable $callback = null, $options = [])
 */
class MZipCodesTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('m_zip_codes');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->requirePresence('id', 'create')
            ->notEmpty('id');

        $validator
            ->requirePresence('post_code', 'create')
            ->notEmpty('post_code');

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
            ->dateTime('create_at')
            ->requirePresence('create_at', 'create')
            ->notEmpty('create_at');

        $validator
            ->dateTime('update_at')
            ->allowEmpty('update_at');

        return $validator;
    }
}
