<?php declare(strict_types=1);

/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\HTTP\Services;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

/**
 * Class ilMailTemplateGUI
 * @author            Nadia Ahmad <nahmad@databay.de>
 * @author            Michael Jansen <mjansen@databay.de>
 * @ilCtrl_isCalledBy ilMailTemplateGUI: ilObjMailGUI
 */
class ilMailTemplateGUI
{
    protected ilPropertyFormGUI $form;
    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilToolbarGUI $toolbar;
    protected ilRbacSystem $rbacsystem;
    protected ilObject $parentObject;
    protected ilErrorHandling $error;
    protected ilMailTemplateService $service;
    protected Services $http;
    protected Factory $uiFactory;
    protected Renderer $uiRenderer;


    public function __construct(
        ilObject $parentObject,
        ilGlobalTemplateInterface $tpl = null,
        ilCtrl $ctrl = null,
        ilLanguage $lng = null,
        ilToolbarGUI $toolbar = null,
        ilRbacSystem $rbacsystem = null,
        ilErrorHandling $error = null,
        Services $http = null,
        Factory $uiFactory = null,
        Renderer $uiRenderer = null,
        ilMailTemplateService $templateService = null
    ) {
        global $DIC;

        $this->parentObject = $parentObject;

        if ($tpl === null) {
            $tpl = $DIC->ui()->mainTemplate();
        }
        $this->tpl = $tpl;

        if ($ctrl === null) {
            $ctrl = $DIC->ctrl();
        }
        $this->ctrl = $ctrl;

        if ($lng === null) {
            $lng = $DIC->language();
        }
        $this->lng = $lng;

        if ($toolbar === null) {
            $toolbar = $DIC->toolbar();
        }
        $this->toolbar = $toolbar;

        if ($rbacsystem === null) {
            $rbacsystem = $DIC->rbac()->system();
        }
        $this->rbacsystem = $rbacsystem;

        if ($error === null) {
            $error = $DIC['ilErr'];
        }
        $this->error = $error;

        if ($http === null) {
            $http = $DIC->http();
        }
        $this->http = $http;

        if ($uiFactory === null) {
            $uiFactory = $DIC->ui()->factory();
        }
        $this->uiFactory = $uiFactory;

        if ($uiRenderer === null) {
            $uiRenderer = $DIC->ui()->renderer();
        }
        $this->uiRenderer = $uiRenderer;

        if (null === $templateService) {
            $templateService = $DIC['mail.texttemplates.service'];
        }
        $this->service = $templateService;

        $this->lng->loadLanguageModule('meta');
    }

    
    private function isEditingAllowed() : bool
    {
        return $this->rbacsystem->checkAccess('write', $this->parentObject->getRefId());
    }

    
    public function executeCommand() : void
    {
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            default:
                if (!$cmd || !method_exists($this, $cmd)) {
                    $cmd = 'showTemplates';
                }

                $this->$cmd();
                break;
        }
    }

    
    protected function showTemplates() : void
    {
        $contexts = ilMailTemplateContextService::getTemplateContexts();
        if (count($contexts) <= 1) {
            ilUtil::sendFailure($this->lng->txt('mail_template_no_context_available'));
        } elseif ($this->isEditingAllowed()) {
            $create_tpl_button = ilLinkButton::getInstance();
            $create_tpl_button->setCaption('mail_new_template');
            $create_tpl_button->setUrl($this->ctrl->getLinkTarget($this, 'showInsertTemplateForm'));
            $this->toolbar->addButtonInstance($create_tpl_button);
        }

        $tbl = new ilMailTemplateTableGUI(
            $this,
            'showTemplates',
            $this->uiFactory,
            $this->uiRenderer,
            !$this->isEditingAllowed()
        );
        $tbl->setData($this->service->listAllTemplatesAsArray());

        $this->tpl->setContent($tbl->getHTML());
    }

    /**
     * @throws ilMailException
     */
    protected function insertTemplate() : void
    {
        if (!$this->isEditingAllowed()) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_write'), $this->error->WARNING);
        }

        $form = $this->getTemplateForm();

        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->showInsertTemplateForm($form);
            return;
        }

        $generic_context = new ilMailTemplateGenericContext();
        if ($form->getInput('context') === $generic_context->getId()) {
            $form->getItemByPostVar('context')->setAlert(
                $this->lng->txt('mail_template_no_valid_context')
            );
            $form->setValuesByPost();
            $this->showInsertTemplateForm($form);
            return;
        }

        try {
            $this->service->createNewTemplate(
                ilMailTemplateContextService::getTemplateContextById($form->getInput('context'))->getId(),
                $form->getInput('title'),
                $form->getInput('m_subject'),
                $form->getInput('m_message'),
                $form->getInput('lang')
            );

            ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
            $this->ctrl->redirect($this, 'showTemplates');
        } catch (Exception $e) {
            $form->getItemByPostVar('context')->setAlert(
                $this->lng->txt('mail_template_no_valid_context')
            );
            ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
        }

        $form->setValuesByPost();
        $this->showInsertTemplateForm($form);
    }

    /**
     * @throws ilMailException
     */
    protected function showInsertTemplateForm(ilPropertyFormGUI $form = null) : void
    {
        if (!($form instanceof ilPropertyFormGUI)) {
            $form = $this->getTemplateForm();
        }

        $this->tpl->setContent($form->getHTML());
    }

    
    protected function updateTemplate() : void
    {
        if (!$this->isEditingAllowed()) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_write'), $this->error->WARNING);
        }

        $templateId = $this->http->request()->getParsedBody()['tpl_id'] ?? 0;

        if (!is_numeric($templateId) || $templateId < 1) {
            ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            $this->showTemplates();
            return;
        }

        try {
            $form = $this->getTemplateForm();
            if (!$form->checkInput()) {
                $form->setValuesByPost();
                $this->showEditTemplateForm($form);
                return;
            }

            $genericContext = new ilMailTemplateGenericContext();
            if ($form->getInput('context') === $genericContext->getId()) {
                $form->getItemByPostVar('context')->setAlert(
                    $this->lng->txt('mail_template_no_valid_context')
                );
                $form->setValuesByPost();
                $this->showEditTemplateForm($form);
                return;
            }

            try {
                $this->service->modifyExistingTemplate(
                    (int) $templateId,
                    ilMailTemplateContextService::getTemplateContextById($form->getInput('context'))->getId(),
                    $form->getInput('title'),
                    $form->getInput('m_subject'),
                    $form->getInput('m_message'),
                    $form->getInput('lang')
                );

                ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
                $this->ctrl->redirect($this, 'showTemplates');
            } catch (OutOfBoundsException $e) {
                ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            } catch (Exception $e) {
                $form->getItemByPostVar('context')->setAlert(
                    $this->lng->txt('mail_template_no_valid_context')
                );
                ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
            }

            $form->setValuesByPost();
            $this->showEditTemplateForm($form);
        } catch (Exception $e) {
            ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            $this->showTemplates();
            return;
        }
    }

    
    protected function showEditTemplateForm(ilPropertyFormGUI $form = null) : void
    {
        if (!($form instanceof ilPropertyFormGUI)) {
            $templateId = $this->http->request()->getQueryParams()['tpl_id'] ?? 0;

            if (!is_numeric($templateId) || $templateId < 1) {
                ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
                $this->showTemplates();
                return;
            }

            try {
                $template = $this->service->loadTemplateForId((int) $templateId);
                $form = $this->getTemplateForm($template);
                $this->populateFormWithTemplate($form, $template);
            } catch (Exception $e) {
                ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
                $this->showTemplates();
                return;
            }
        }

        $this->tpl->setContent($form->getHTML());
    }

    
    protected function populateFormWithTemplate(ilPropertyFormGUI $form, ilMailTemplate $template) : void
    {
        $form->setValuesByArray([
            'tpl_id' => $template->getTplId(),
            'title' => $template->getTitle(),
            'context' => $template->getContext(),
            'lang' => $template->getLang(),
            'm_subject' => $template->getSubject(),
            'm_message' => $template->getMessage(),
        ]);
    }

    
    protected function confirmDeleteTemplate() : void
    {
        if (!$this->isEditingAllowed()) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_write'), $this->error->WARNING);
        }

        $templateIds = $this->http->request()->getParsedBody()['tpl_id'] ?? [];
        if (is_array($templateIds) && count($templateIds) > 0) {
            $templateIds = array_filter(array_map('intval', $templateIds));
        } else {
            $templateId = $this->http->request()->getQueryParams()['tpl_id'] ?? '';
            if (is_numeric($templateId) && $templateId > 0) {
                $templateIds = array_filter([(int) $templateId]);
            } else {
                $templateIds = [];
            }
        }

        if (0 === count($templateIds)) {
            ilUtil::sendFailure($this->lng->txt('select_one'));
            $this->showTemplates();
            return;
        }

        $confirm = new ilConfirmationGUI();
        $confirm->setFormAction($this->ctrl->getFormAction($this, 'deleteTemplate'));

        $confirm->setHeaderText($this->lng->txt('mail_tpl_sure_delete_entry'));
        if (1 === count($templateIds)) {
            $confirm->setHeaderText($this->lng->txt('mail_tpl_sure_delete_entries'));
        }

        $confirm->setConfirm($this->lng->txt('confirm'), 'deleteTemplate');
        $confirm->setCancel($this->lng->txt('cancel'), 'showTemplates');

        foreach ($templateIds as $templateId) {
            $template = $this->service->loadTemplateForId((int) $templateId);
            $confirm->addItem('tpl_id[]', $templateId, $template->getTitle());
        }

        $this->tpl->setContent($confirm->getHTML());
    }

    
    protected function deleteTemplate() : void
    {
        if (!$this->isEditingAllowed()) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_write'), $this->error->WARNING);
        }

        $templateIds = $this->http->request()->getParsedBody()['tpl_id'] ?? [];
        if (is_array($templateIds) && count($templateIds) > 0) {
            $templateIds = array_filter(array_map('intval', $templateIds));
        } else {
            $templateId = $this->http->request()->getQueryParams()['tpl_id'] ?? '';
            if (is_numeric($templateId) && $templateId > 0) {
                $templateIds = array_filter([(int) $templateId]);
            } else {
                $templateIds = [];
            }
        }

        if (0 === count($templateIds)) {
            ilUtil::sendFailure($this->lng->txt('select_one'));
            $this->showTemplates();
            return;
        }

        $this->service->deleteTemplatesByIds($templateIds);

        if (1 === count($templateIds)) {
            ilUtil::sendSuccess($this->lng->txt('mail_tpl_deleted_s'), true);
        } else {
            ilUtil::sendSuccess($this->lng->txt('mail_tpl_deleted_p'), true);
        }
        $this->ctrl->redirect($this, 'showTemplates');
    }

    /**
     * @throws ilMailException
     */
    public function getAjaxPlaceholdersById() : void
    {
        $triggerValue = $this->http->request()->getQueryParams()['triggerValue'] ?? '';
        $contextId = ilUtil::stripSlashes($triggerValue);

        $placeholders = new ilManualPlaceholderInputGUI('m_message');
        $placeholders->setInstructionText($this->lng->txt('mail_nacc_use_placeholder'));
        $placeholders->setAdviseText(sprintf($this->lng->txt('placeholders_advise'), '<br />'));

        $context = ilMailTemplateContextService::getTemplateContextById($contextId);
        foreach ($context->getPlaceholders() as $key => $value) {
            $placeholders->addPlaceholder($value['placeholder'], $value['label']);
        }

        $placeholders->render(true);
        exit();
    }

    /**
     * @throws ilMailException
     */
    protected function getTemplateForm(ilMailTemplate $template = null) : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();

        $title = new ilTextInputGUI($this->lng->txt('mail_template_title'), 'title');
        $title->setRequired(true);
        $title->setDisabled(!$this->isEditingAllowed());
        $form->addItem($title);

        $context = new ilRadioGroupInputGUI($this->lng->txt('mail_template_context'), 'context');
        $context->setDisabled(!$this->isEditingAllowed());
        $contexts = ilMailTemplateContextService::getTemplateContexts();

        if (count($contexts) <= 1) {
            ilUtil::sendFailure($this->lng->txt('mail_template_no_context_available'), true);
            $this->ctrl->redirect($this, 'showTemplates');
        }

        $context_sort = [];
        $context_options = [];
        $generic_context = new ilMailTemplateGenericContext();
        foreach ($contexts as $ctx) {
            if ($ctx->getId() !== $generic_context->getId()) {
                $context_options[$ctx->getId()] = $ctx;
                $context_sort[$ctx->getId()] = $ctx->getTitle();
            }
        }
        asort($context_sort);
        $first = null;
        foreach ($context_sort as $id => $title) {
            $ctx = $context_options[$id];
            $option = new ilRadioOption($ctx->getTitle(), $ctx->getId());
            $option->setInfo($ctx->getDescription());
            $context->addOption($option);

            if (!$first) {
                $first = $id;
            }
        }
        $context->setValue($first);
        $context->setRequired(true);
        $form->addItem($context);

        $hidden = new ilHiddenInputGUI('lang');
        $hidden->setValue($this->lng->getLangKey());
        $form->addItem($hidden);

        $subject = new ilTextInputGUI($this->lng->txt('subject'), 'm_subject');
        $subject->setDisabled(!$this->isEditingAllowed());
        $subject->setSize(50);
        $form->addItem($subject);

        $message = new ilTextAreaInputGUI($this->lng->txt('message'), 'm_message');
        $message->setDisabled(!$this->isEditingAllowed());
        $message->setRequired(true);
        $message->setCols(60);
        $message->setRows(10);
        $form->addItem($message);

        $placeholders = new ilManualPlaceholderInputGUI('m_message');
        $placeholders->setDisabled(!$this->isEditingAllowed());
        $placeholders->setInstructionText($this->lng->txt('mail_nacc_use_placeholder'));
        $placeholders->setAdviseText(sprintf($this->lng->txt('placeholders_advise'), '<br />'));
        $placeholders->supportsRerenderSignal(
            'context',
            $this->ctrl->getLinkTarget($this, 'getAjaxPlaceholdersById', '', true)
        );
        if ($template === null) {
            $context_id = $generic_context->getId();
        } else {
            $context_id = $template->getContext();
        }
        $context = ilMailTemplateContextService::getTemplateContextById($context_id);
        foreach ($context->getPlaceholders() as $key => $value) {
            $placeholders->addPlaceholder($value['placeholder'], $value['label']);
        }
        $form->addItem($placeholders);
        if ($template instanceof ilMailTemplate && $template->getTplId() > 0) {
            $id = new ilHiddenInputGUI('tpl_id');
            $form->addItem($id);

            $form->setTitle($this->lng->txt('mail_edit_tpl'));
            $form->setFormAction($this->ctrl->getFormAction($this, 'updateTemplate'));

            if ($this->isEditingAllowed()) {
                $form->addCommandButton('updateTemplate', $this->lng->txt('save'));
            }
        } else {
            $form->setTitle($this->lng->txt('mail_create_tpl'));
            $form->setFormAction($this->ctrl->getFormAction($this, 'insertTemplate'));

            if ($this->isEditingAllowed()) {
                $form->addCommandButton('insertTemplate', $this->lng->txt('save'));
            }
        }

        if ($this->isEditingAllowed()) {
            $form->addCommandButton('showTemplates', $this->lng->txt('cancel'));
        } else {
            $form->addCommandButton('showTemplates', $this->lng->txt('back'));
        }

        return $form;
    }

    
    public function unsetAsContextDefault() : void
    {
        if (!$this->isEditingAllowed()) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_write'), $this->error->WARNING);
        }

        $templateId = $this->http->request()->getQueryParams()['tpl_id'] ?? 0;

        if (!is_numeric($templateId) || $templateId < 1) {
            ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            $this->showTemplates();
            return;
        }

        try {
            $template = $this->service->loadTemplateForId((int) $templateId);
            $this->service->unsetAsContextDefault($template);
        } catch (Exception $e) {
            ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            $this->showTemplates();
            return;
        }

        ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
        $this->ctrl->redirect($this, 'showTemplates');
    }

    
    public function setAsContextDefault() : void
    {
        if (!$this->isEditingAllowed()) {
            $this->error->raiseError($this->lng->txt('msg_no_perm_write'), $this->error->WARNING);
        }

        $templateId = $this->http->request()->getQueryParams()['tpl_id'] ?? 0;

        if (!is_numeric($templateId) || $templateId < 1) {
            ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            $this->showTemplates();
            return;
        }

        try {
            $template = $this->service->loadTemplateForId((int) $templateId);
            $this->service->setAsContextDefault($template);
        } catch (Exception $e) {
            ilUtil::sendFailure($this->lng->txt('mail_template_missing_id'));
            $this->showTemplates();
            return;
        }

        ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
        $this->ctrl->redirect($this, 'showTemplates');
    }
}
