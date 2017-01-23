<?php
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Database/classes/class.ilDB.php';
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';

define('IL_GRP_MEMBER',5);

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 19.01.2017
 * Time: 12:40
 * @ilCtrl_IsCalledBy ilCourseImportMemberGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportMemberGUI: ilObjCourseAdministrationGUI

 */
class ilCourseImportMemberGUI {
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilTemplate
     */
    protected $tpl;
    /**
     * @var ilCourseImportPlugin
     */
    protected $pl;
    /**
     * @var ilTabsGUI
     */
    protected $tabs;
    /**
     * @var ilLocatorGUI
     */
    protected $ilLocator;
    /**
     * @var ilLanguage
     */
    protected $lng;
    /**
     * @var ilTree
     */
    protected $tree;
    protected $course_id;
    protected $memberLogin;
    protected $groupTitle;
    protected $destinationTitle;

    public function __construct() {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->course_id = $_GET['ref_id'];
        $this->ilLocator = $ilLocator;
        $this->pl = ilCourseImportPlugin::getInstance();
    }
    protected function prepareOutput() {

        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupgui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportmembergui','ref_id',$_GET['ref_id']);

        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit',$this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportMemberGUI')));
        $this->tabs->activateSubTab('member_edit');

        $this->ctrl->getRedirectSource();

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilrepositorygui',
            'ilrepositorygui',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }
    protected function setTitleAndIcon() {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->lng->txt('obj_crss'));
        $this->tpl->setDescription($this->lng->txt('obj_crss_desc'));
    }
    /**
     *
     */
    public function executeCommand() {
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();

        switch ($cmd) {
            default:
                $this->$cmd();
                break;
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }

    /**
     * default command
     */
    protected function view() {

        $form = $this->initForm();
        $this->tpl->setContent($form->getHTML());
    }

    protected function initForm(){
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('member_edit'));
        $form->setDescription($this->pl->txt('member_description'));
        $form->setId('member_edit');
        $form->setFormAction($this->ctrl->getFormAction($this));

        $this->memberLogin = new ilTextInputGUI($this->pl->txt('member_login'), 'member_login');
        $this->groupTitle = new ilTextInputGUI($this->pl->txt('group_title'), 'group_title');
        $this->destinationTitle = new ilTextInputGUI($this->pl->txt('destination_title'), 'destination_title');
        $this->memberLogin->setRequired(true);
        $this->groupTitle->setRequired(true);
        $this->destinationTitle->setRequired(true);



        $form->addItem($this->memberLogin);
        $form->addItem($this->groupTitle);
        $form->addItem($this->destinationTitle);
        $form->addCommandButton('moveMember', $this->pl->txt('move_member'));

        return $form;
    }

    protected function moveMember(){
        global $ilDB;

        $form = $this->initForm();
        $form->setValuesByPost();
        $member_login = $this->memberLogin->getValue();
        $group_title = $this->groupTitle->getValue();
        $destination_title = $this->destinationTitle->getValue();


        $member_id = $this->getMemberIdByLogin($member_login);
        $group_id = $this->getGroupIdByTitle($group_title,$this->course_id);
        $destination_id = $this->getGroupIdByTitle($destination_title,$this->course_id);

        $ref = $destination_id[0];
        $ref2 = $group_id[0];

        $description_dest = "Groupmember of group obj_no." . $ref["obj_id"];
        $description_source = "Groupmember of group obj_no." . $ref2["obj_id"];

        $role_id_dest = $this->getRoleID($description_dest);
        $role_id_source = $this->getRoleID($description_source);








        $this->manipulateDB($member_id[0],$role_id_source[0],$destination_id[0],$role_id_dest[0],$group_id[0]);

        $this->view();



    }
    protected function getRoleID($description){
        global $ilDB;
        $role_id = array();
        $query = "SELECT od.obj_id FROM ilias.object_data as od WHERE od.description = '".$description."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($role_id,$record);
        }
        return $role_id;
    }
    protected function manipulateDB($member_id,$role_id_source,$destination_id,$role_id_dest,$source_id){
        global $ilDB;

        //insert in RBAC
        $query = "INSERT INTO rbac_ua (usr_id, rol_id) ".
            "VALUES (".$member_id['usr_id'].",".$role_id_dest['obj_id'].")";
        $res = $ilDB->manipulate($query);

        //delete OLD from RBAC
        $query = "DELETE FROM rbac_ua 
            WHERE usr_id = ".$member_id['usr_id']."
            AND rol_id = ".$role_id_source['obj_id']." ";
        $res = $ilDB->manipulate($query);

        $query = "UPDATE ilias.obj_members as om
        SET om.obj_id = '".$destination_id['obj_id']."' WHERE om.usr_id = '".$member_id['usr_id']."' AND om.obj_id = '".$source_id['obj_id']."' AND om.member = 1";
        $ilDB->manipulate($query);

    }
    protected function getGroupIdByTitle($group_title,$course_id){
        global $ilDB;
        $group_id= array();

        $query = "select oref.obj_id from
                  ilias.crs_items as citem
                  join ilias.object_reference oref
                  join ilias.object_data od on (oref.obj_id = od.obj_id and citem.obj_id = oref.ref_id)
                  where od.title= %s and citem.parent_id= %s and oref.deleted is null";
        $result = $ilDB->queryF($query, array('text','integer'),array($group_title,$course_id));
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($group_id,$record);
        }

        return $group_id;
    }

    protected function getMemberIdByLogin($member_login){
        global $ilDB;

        $member_id = array();
        $query = "select usr_id from ilias.usr_data as ud where (ud.login='".$member_login."')";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($member_id,$record);
        }
        return $member_id;




    }
    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}