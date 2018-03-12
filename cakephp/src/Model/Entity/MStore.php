<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class MStore extends Entity
{
  //idフィールドへのアクセスを保護
  //idフィールド以外へのアクセスを許可
  protected $_accessible = [
    '*' => true,
    'id' => false
  ];

}
