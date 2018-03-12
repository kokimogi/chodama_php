<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InfoMember Entity
 *
 * @property int $id
 * @property int $info_registration_id
 * @property string $tempreg_id
 * @property string $store_code
 * @property string|resource $family_name
 * @property string|resource $first_name
 * @property string|resource $middle_name
 * @property string|resource $family_name_kana
 * @property string|resource $first_name_kana
 * @property string|resource $middle_name_kana
 * @property string|resource $mail_address
 * @property string|resource $birthday
 * @property string $gender_type
 * @property string $country_type
 * @property string $post_code_left
 * @property string $post_code_right
 * @property string|resource $prefectures_name
 * @property string|resource $city_name
 * @property string|resource $town_name
 * @property string|resource $house_number
 * @property string|resource $building_name
 * @property string|resource $phone_number
 * @property string|resource $cellphone_number
 * @property string $dm_receive_type
 * @property string $job_type
 * @property string $job_name
 * @property string $dm_post_code_left
 * @property string $dm_post_code_right
 * @property string|resource $dm_prefectures_name
 * @property string|resource $dm_city_name
 * @property string|resource $dm_town_name
 * @property string|resource $dm_house_number
 * @property string|resource $dm_building_name
 * @property string $question_code
 * @property \Cake\I18n\Time $create_at
 * @property \Cake\I18n\Time $update_at
 *
 * @property \App\Model\Entity\InfoRegistration $info_registration
 */
class InfoMember extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
