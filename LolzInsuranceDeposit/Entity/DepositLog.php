<?php
namespace DCS\LolzInsuranceDeposit\Entity;

use XF;
use XF\Entity\ApprovalQueue;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int user_id
 * @property int request_amount
 * @property int status
 * @property int creation_date
 *
 * RELATIONS
 * @property User User
 * @property ApprovalQueue ApprovalQueue
 */
class DepositLog extends Entity
{
    /**
     * @return bool
     */
    public function canView()
    {
        return XF::visitor()->is_super_admin || XF::visitor()->user_id == $this->user_id;
    }

    protected function _preSave()
    {
        if ($this->isUpdate()) {
            $this->change_date = XF::$time;
        }
    }


    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_dcs_lolz_deposits_logs';
        $structure->shortName = 'DCS\LolzInsuranceDeposit:DepositLog';
        $structure->primaryKey = 'log_id';
        $structure->columns = [
            'log_id'  => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'amount' => ['type' => self::UINT, 'required' => true],
            'type' => ['type' => self::UINT, 'required' => true,
                'allowedValues' => [0, 1]
            ],
            'date' => ['type' => self::UINT, 'required' => true, 'default' => XF::$time],
        ];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
        ];

        return $structure;
    }
}
