<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 07/02/17
 * Time: 12.12
 *
 */

namespace API\V2;

use API\V2\Json\Membership;
use API\V2\Validators\OrganizationAccessValidator;
use OrganizationModel;
use Organizations\MembershipDao;
use Organizations\OrganizationDao;
use Organizations\PendingInvitations;

class OrganizationMembersController extends KleinController {

    protected function afterConstruct() {
        $this->appendValidator( new OrganizationAccessValidator( $this ) );
    }

    /**
     * Get organization members list
     */
    public function index(){
        $membersList = ( new MembershipDao )
            ->setCacheTTL( 60 * 60 * 24 )
            ->getMemberListByOrganizationId( $this->request->id_organization );

        $pendingInvitation = new PendingInvitations( ( new \RedisHandler() )->getConnection(), [] );

        $formatter = new Membership( $membersList ) ;
        $this->response->json( [
                'members' => $formatter->render(),
                'pending_invitations' => $pendingInvitation->get( $this->request->id_organization )
        ] );

    }

    public function update() {
        $params = $this->request->paramsPost()->getIterator()->getArrayCopy();

        $params = filter_var_array($params, [
            'members' => [
                'filter' => FILTER_SANITIZE_EMAIL,
                'flags' => FILTER_REQUIRE_ARRAY
            ]
        ], true ) ;

        $organizationStruct = ( new OrganizationDao() )
            ->findById( $this->request->id_organization );

        $model = new OrganizationModel( $organizationStruct ) ;
        $model->setUser( $this->user ) ;
        $model->addMemberEmails( $params['members'] ) ;
        $full_members_list = $model->updateMembers();

        $pendingInvitation = new PendingInvitations( ( new \RedisHandler() )->getConnection(), [] );
        $formatter = new Membership( $full_members_list ) ;
        $this->response->json( [
                'members' => $formatter->render(),
                'pending_invitations' => $pendingInvitation->get( $organizationStruct->id )
        ] );

    }

    public function delete(){
        \Database::obtain()->begin();

        $organizationStruct = ( new OrganizationDao() )
            ->findById( $this->request->id_organization );

        $model = new OrganizationModel( $organizationStruct ) ;
        $model->removeMemberUids( array( $this->request->uid_member ) );
        $model->setUser( $this->user ) ;
        $membersList = $model->updateMembers();

        $pendingInvitation = new PendingInvitations( ( new \RedisHandler() )->getConnection(), [] );
        $formatter = new Membership( $membersList ) ;
        $this->response->json( [
                'members' => $formatter->render(),
                'pending_invitations' => $pendingInvitation->get( $organizationStruct->id )
        ] );

    }



}