<?php
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'class.ilCourseImportGroupTable.php';
/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 16.12.2016
 * Time: 13:50
 * @ilCtrl_IsCalledBy ilCourseImportGroupDisplayGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportGroupDisplayGUI: ilObjCourseAdministrationGUI
 */
class ilCourseImportGroupDisplayGUI
{
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

    protected $course_table;
    /**
     * @var ilTree
     */
    protected $tree;

    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilCourseImportPlugin::getInstance();
    }

    protected function prepareOutput()
    {
        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupgui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportmembergui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $_GET['ref_id']);


        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit',$this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportMemberGUI')));

        $this->tabs->activateSubTab('course_edit');

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilrepositorygui',
            'ilrepositorygui',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }

    protected function setTitleAndIcon()
    {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->lng->txt('obj_crss'));
        $this->tpl->setDescription($this->lng->txt('obj_crss_desc'));
    }

    /**
     *
     */
    public function executeCommand()
    {
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
    protected function view()
    {
        $table = $this->initTable();
        $this->tpl->setContent($table->getHTML());
    }

    /**
     * @return ilCourseImportGroupTable
     */
    protected function initTable()
    {

        $table = new ilCourseImportGroupTable($this);
        $table->setTitle($this->pl->txt('form_title_management'));
        $table->setId('crs_management');
        $table->setFormAction($this->ctrl->getFormAction($this));
        //$table->addColumn($this->pl->txt('course_name'), "", "20%");
        $table->addColumn($this->pl->txt('title'), "", "30%");
        $table->addColumn($this->pl->txt('description'), "", "30%");
        $table->addColumn($this->pl->txt('login'), "", "20%");
        $table->addColumn($this->pl->txt('registration_max_members'), "", "20%");

        $data = $this->getTableData($_GET['ref_id']);

        var_dump($data);

        $table->fillRow($data[0]);


        $table->addCommandButton('saveForm', $this->pl->txt('save_settings'));

        return $table;
    }

    protected function getTableData($ref_id){

        global $ilDB;

        $data = array();
        $query = "select od.title, gs.registration_max_members, ud.login, od.description
from ilias.object_data as od
join ilias.object_reference as oref on oref.obj_id = od.obj_id 
join ilias.grp_settings gs on gs.obj_id = oref.obj_id
join ilias.crs_items citem on citem.obj_id = oref.ref_id
left join (select * from ilias.obj_members om where om.tutor = 1) as obm on obm.obj_id = oref.obj_id
left join ilias.usr_data ud on ud.usr_id = obm.usr_id
where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$ref_id."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }

        return $data;

    }

    protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}