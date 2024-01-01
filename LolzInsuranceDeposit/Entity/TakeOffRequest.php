<?php
namespace DCS\LolzInsuranceDeposit\Entity;

use XF;
use XF\Entity\ApprovalQueue;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\PrintableException;

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
class TakeOffRequest extends Entity
{
    /**
     * @return XF\Phrase
     */
    public function getStatusPhrase()
    {
        return XF::phrase('dcs_lid_takeOffRequest_status_' . $this->status);
    }

    /**
     * @return bool
     */
    public function canView()
    {
        return XF::visitor()->is_super_admin || XF::visitor()->user_id == $this->user_id;
    }

    /**
     *
     */
    protected function _preSave()
    {
        if ($this->isUpdate()) {
            $this->change_date = XF::$time;
        }
    }

    /**
     *
     * @throws PrintableException
     */
    protected function _postSave()
    {
        parent::_postSave();

        $approvalChange = $this->isStateChanged('status', 'created');

        if ($approvalChange === 'enter' && $this->isInsert()) {
            $user = $this->User;
            $user->dcs_lolz_deposit_amount = $user->dcs_lolz_deposit_amount - $this->request_amount;
            $user->save();

            if ($user->hasErrors()) {
                $this->status = 'rejected';
                $this->save();
            }

            /** @var ApprovalQueue $approvalQueue */
            $approvalQueue = $this->getRelationOrDefault('ApprovalQueue', false);
            $approvalQueue->content_date = $this->creation_date;
            $approvalQueue->save();
        }


    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->ApprovalQueue->delete();
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_dcs_deposit_takeOff_requests';
        $structure->shortName = 'DCS\LolzInsuranceDeposit:TakeOffRequest';
        $structure->primaryKey = 'takeoff_request_id';
        $structure->columns = [
            'takeoff_request_id'  => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'request_amount' => ['type' => self::UINT, 'required' => true],
            'status' => ['type' => self::STR, 'required' => true, 'default' => 'created',
                'allowedValues' => ['created', 'rejected', 'completed']
            ],
            'creation_date' => ['type' => self::UINT, 'required' => true, 'default' => XF::$time],
        ];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
            'ApprovalQueue' => [
                'entity' => 'XF:ApprovalQueue',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['content_type', '=', 'takeoff_request'],
                    ['content_id', '=', '$takeoff_request_id']
                ],
                'primary' => true
            ]
        ];
        $structure->getters = [
            'status_phrase' => true
        ];

        return $structure;
    }
}
