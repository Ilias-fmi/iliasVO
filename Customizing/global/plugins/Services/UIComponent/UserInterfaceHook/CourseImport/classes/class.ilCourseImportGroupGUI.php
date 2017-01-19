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

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 05.12.2016
 * Time: 15:54
 * @ilCtrl_IsCalledBy ilCourseImportGroupGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilCourseImportGroupGUI: ilObjCourseAdministrationGUI
 *
 */
class ilCourseImportGroupGUI
{
    const IMPORT_SUCCEEDED = 'import_succeeded';
    const IMPORT_FAILED = 'import_failed';
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

        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCourseImportGroupDisplayGUI')));
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
        $form->addCommandButton('createGroups', $this->pl->txt('create_groups'));

        return $form;
    }

    protected function createGroups()
    {
        $form = $this->initForm();
        $form->setValuesByPost();
        $number = $this->group_count->getValue();
        $members = $this->members->getValue();

            for ($n = 1; $n <= $number; $n++) {
                $group = new ilObjGroup();
                //TODO: getNumberOfExistingGroups in Course($_GET['ref_id']) and Titel = n + numExisting !
                $group->setTitle('Gruppe'.$n);
                $group->setGroupType(GRP_TYPE_OPEN);
                $group->setMaxMembers($members);
                $group->enableMembershipLimitation(true);
                $group->create();
                $group->createReference();
                $group->putInTree($_GET['ref_id']);
                $group->setPermissions($_GET['ref_id']);
                $this->courses['created'] .= ilObject2::_lookupTitle(ilObject2::_lookupObjId($_GET['ref_id'])) . ' - ' . $group->getTitle() . '<br>';
            }
        ilUtil::sendSuccess(sprintf($this->pl->txt(self::IMPORT_SUCCEEDED), $this->courses['created'], $this->courses['updated'], $this->courses['refs'], $this->courses['refs_del']));

    }


    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}