<?php
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Database/classes/class.ilDB.php';
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';

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



        $form->addItem($this->memberLogin);
        $form->addItem($this->groupTitle);
        $form->addCommandButton('moveMember', $this->pl->txt('move_member'));

        return $form;
    }

    protected function moveMember(){
        $form = $this->initForm();
        $form->setValuesByPost();
        $field1 = $this->memberLogin->getValue();
        $field2 = $this->groupTitle->getValue();
        var_dump($field1);
        var_dump($field2);

        $member_login = "root";
        $group_title = "Gruppe1";

        var_dump($member_login);
        var_dump($group_title);


        $member_id = $this->getMemberIdByLogin($member_login);
        $group_id = $this->getGroupIdByTitle($group_title,$this->course_id);
        var_dump($member_id);
        var_dump($group_id);

        $this->view();



    }
    protected function getGroupIdByTitle($group_title,$course_id){
        global $ilDB;
        $group_id= array();

        $query = "select citem.obj_id from
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