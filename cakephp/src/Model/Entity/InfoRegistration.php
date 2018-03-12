<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InfoRegistration Entity
 *
 * @property int $id
 * @property string $reg_type
 * @property string $reg_status
 * @property string $store_code
 * @property string|resource $mail_address
 * @property string $tempreg_id
 * @property \Cake\I18n\Time $request_date
 * @property string $ip_address
 * @property string $s_mem_no
 * @property string $s_password
 * @property string $s_posno
 * @property \Cake\I18n\Time $s_posentryd
 * @property string $s_identifycode
 * @property string $s_identifyinfo
 * @property string $s_identifydateinfo
 * @property string $s_identifynoinfo
 * @property string $s_identifystaffcode
 * @property string|resource $s_identifystaffname
 * @property \Cake\I18n\Time $create_at
 * @property \Cake\I18n\Time $update_at
 *
 * @property \App\Model\Entity\Tempreg $tempreg
 * @property \App\Model\Entity\InfoMember[] $info_members
 */
class InfoRegistration extends Entity
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
