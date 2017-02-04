<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Services/UIComponent/Explorer2/classes/class.ilExplorerBaseGUI.php");
include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CourseImport/classes/class.ilNavigationMenu.php");
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
require_once './Modules/Group/classes/class.ilObjGroup.php';
require_once './Services/Object/classes/class.ilObject2.php';
require_once './Services/Form/classes/class.ilNumberInputGUI.php';
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Database/classes/class.ilDB.php';
require_once './Services/Form/classes/class.ilRadioGroupInputGUI.php';
require_once './Services/Form/classes/class.ilRadioOption.php';
require_once './Services/Form/classes/class.ilDateTimeInputGUI.php';

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 05.12.2016
 * Time: 15:54
 * @ilCtrl_IsCalledBy ilCourseImportGroupGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportGroupGUI: ilObjCourseAdministrationGUI
 * 
 *  This class implements the functionality of the "groupcreator tab" which are
 *  the number of groups (checks the db and numbers the group consecutively), 
 *  maximum members, how to join the group (password ord not) and from/till which
 *  date.   
 *  
 */
class ilCourseImportGroupGUI
{
    const CREATION_SUCCEEDED = 'creation_succeeded';
    const CREATION_FAILED = 'creation_failed';
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
    

    protected $courses;
    protected $members;
    protected $group_count;
    protected $number_grp;
    protected $reg_proc;
    protected $pass;
    
    protected $group_time_start;
    


    public function __construct() {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilCourseImportPlugin::getInstance();
    }

    protected function prepareOutput() {

        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportmembergui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $_GET['ref_id']);

        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit',$this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportMemberGUI')));
        $this->tabs->activateSubTab('group_create');

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
        $form->setTitle($this->pl->txt('group_create_title'));
        $form->setId('group_create');
        $form->setFormAction($this->ctrl->getFormAction($this));

        $this->group_count = new ilNumberInputGUI($this->pl->txt('group_count'), 'group_count');
        $this->members = new ilNumberInputGUI($this->pl->txt('members'), 'members');

        $this->group_count->setRequired(true);
        $this->members->setRequired(true);

        $form->addItem($this->group_count);
        $form->addItem($this->members);
        $this->reg_proc = new ilRadioGroupInputGUI($this->pl->txt('grp_registration_type'),'subscription_type');

        $opt = new ilRadioOption($this->pl->txt('grp_reg_direct_info_screen'),GRP_REGISTRATION_DIRECT);
        $this->reg_proc->addOption($opt);

        $opt = new ilRadioOption($this->pl->txt('grp_reg_passwd_info_screen'),GRP_REGISTRATION_PASSWORD);
        $this->pass = new ilTextInputGUI($this->pl->txt("password"),'subscription_password');
        $this->pass->setSize(12);
        $this->pass->setMaxLength(12);

        $opt->addSubItem($this->pass);
        $this->reg_proc->addOption($opt);
        $form->addItem($this->reg_proc);

        $time_limit = new ilCheckboxInputGUI($this->lng->txt('grp_reg_limited'),'reg_limit_time');
        $this->lng->loadLanguageModule('dateplaner');
        include_once './Services/Form/classes/class.ilDateDurationInputGUI.php';
        $this->tpl->addJavaScript('./Services/Form/js/date_duration.js');
        $dur = new ilDateDurationInputGUI($this->lng->txt('grp_reg_period'),'reg');
        $dur->setStartText($this->pl->txt('cal_start'));
        $dur->setEndText($this->pl->txt('cal_end'));
        $dur->setShowTime(true);

        $time_limit->addSubItem($dur);
        $form->addItem($time_limit);

        $form->addCommandButton('createGroups', $this->pl->txt('create_groups'));
        
        
        return $form;
    }

    protected function loadDate($a_field)
    {
        global $ilUser;

        include_once('./Services/Calendar/classes/class.ilDateTime.php');

        $dt['year'] = (int) $_POST['reg'][$a_field]['date']['y'];
        $dt['mon'] = (int) $_POST['reg'][$a_field]['date']['m'];
        $dt['mday'] = (int) $_POST['reg'][$a_field]['date']['d'];
        $dt['hours'] = (int) $_POST['reg'][$a_field]['time']['h'];
        $dt['minutes'] = (int) $_POST['reg'][$a_field]['time']['m'];
        $dt['seconds'] = (int) $_POST['reg'][$a_field]['time']['s'];

        $date = new ilDateTime($dt,IL_CAL_FKT_GETDATE,$ilUser->getTimeZone());
        return $date;
    }


    protected function createGroups()
    {
        
        global $ilDB;
        
         
        $form = $this->initForm();
        $form->setValuesByPost();
        $reg_start = $this->loadDate('start');
        $reg_end = $this->loadDate('end');
        $group_number = array();
        $created = false;
        $number = $this->group_count->getValue();
        $members = $this->members->getValue();
        $password = $this->pass->getValue();
        $reg_type = $this->reg_proc->getValue();

        $query = "select od.title as 'Übungsruppe'
                  from ilias.object_data od
                  join ilias.object_reference obr on od.obj_id = obr.obj_id
                  join ilias.crs_items crsi on obr.ref_id = crsi.obj_id
                  where (od.type = 'grp') and (obr.deleted is null) and (crsi.parent_id = '".$_GET['ref_id']."') ";

        $results = $ilDB->query($query);
            while ($record = $ilDB->fetchAssoc($results)){
                   array_push($group_number,$record);
            }
        $result = count($group_number);
       
        $nn = 1;
        
        if ($result > 0){
            $result ++;
            $nn = $result;
            $number = $number + $nn - 1;
        }


        for ($n = $nn ; $n <= $number; $n++) {
                $group = new ilObjGroup();
                
                
                
                
                if($number<10){   //is necessary for numerical sort
                
                $group->setTitle('Gruppe 0'.$n);
                
                }
                
                else
                {
                     $group->setTitle('Gruppe '.$n);
                }
                $group->setGroupType(GRP_TYPE_CLOSED);
                $group->setRegistrationType($reg_type);
                if($reg_type == GRP_REGISTRATION_PASSWORD){
                    $group->setPassword($password);
                }
                $group->enableUnlimitedRegistration((bool) !$_POST['reg_limit_time']);
                $group->setRegistrationStart($reg_start);
                $group->setRegistrationEnd($reg_end);
                $group->setMaxMembers($members);
                $group->enableMembershipLimitation(true);
                $group->create();
                $group->createReference();
                $group->putInTree($_GET['ref_id']);
                $group->setPermissions($_GET['ref_id']);

                $this->courses['created'] .= ilObject2::_lookupTitle(ilObject2::_lookupObjId($_GET['ref_id'])) . ' - ' . $group->getTitle() . '<br>';
                $created = true;
        }
            if($created) {
                ilUtil::sendSuccess(sprintf($this->pl->txt(self::CREATION_SUCCEEDED), $this->courses['created'], $this->courses['updated'], $this->courses['refs'], $this->courses['refs_del']));
                $form = $this->initForm();
                $this->tpl->setContent($form->getHTML());
            }else {
                ilUtil::sendFailure($this->pl->txt(self::CREATION_FAILED), true);
                $this->tpl->setContent($form->getHTML());

            }
    }


    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}