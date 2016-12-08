<?php

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


    /**
     * @return ilPropertyFormGUI
     */
    protected function initForm() {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('form_title'));
        $form->setId('crs_import');
        $form->setFormAction($this->ctrl->getFormAction($this));

        $this->name_input = new ilTextInputGUI($this->pl->txt('name_input'), 'name_input');


       // $file_input = new ilFileInputGUI($this->pl->txt('file_input'), 'file_input');
       // $file_input->setRequired(true);
        //// $file_input->setSuffixes(array( self::TYPE_XML, self::TYPE_XLSX ));

        $form->addItem($this->name_input);


        //$form->addItem($file_input);
        $form->addCommandButton('saveForm', $this->pl->txt('new_course'));
        //$form->addCommandButton('saveForm', $this->pl->txt('import_courses'));

        return $form;
    }
}