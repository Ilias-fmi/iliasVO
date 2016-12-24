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
    {   $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilcourseimportgroupgui','ref_id',$_GET['ref_id']);

        $this->tabs->addSubTab('course_search',$this->pl->txt('course_search'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
        $this->tabs->activateSubTab('course_edit');

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'iladministrationgui',
            'ilobjcourseadministrationgui',
        )));
        $this->setTitleAndIcon();
        $this->setLocator();
    }

    protected function setTitleAndIcon()
    {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->lng->txt('obj_crss'));
        $this->tpl->setDescription($this->lng->txt('obj_crss_desc'));
    }

    /**
     * invoked by prepareOutput
     */
    protected function setLocator()
    {
        $this->ctrl->setParameterByClass("ilobjsystemfoldergui", "ref_id", SYSTEM_FOLDER_ID);
        $this->ilLocator->addItem($this->lng->txt("administration"), $this->ctrl->getLinkTargetByClass(array(
            "iladministrationgui",
            "ilobjsystemfoldergui",
        ), ""));
        $this->ilLocator->addItem($this->lng->txt('obj_crss'), $this->ctrl->getLinkTargetByClass(array(
            'iladministrationgui',
            'ilobjcourseadministrationgui',
        )));
        $this->tpl->setLocator();
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
        $table->addColumn('Kursname', "", "20%");
        $table->addColumn('Gruppenname', "", "20%");
        $table->addColumn('Termin', "", "20%");
        $table->addColumn('Raum', "", "20%");
        $table->addColumn('Tutor', "", "10%");
        $table->addColumn('Max. Mitglieder', "", "10%");

        $table->fillRow();


        $table->addCommandButton('saveForm', $this->pl->txt('save_settings'));

        return $table;
    }

    protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}