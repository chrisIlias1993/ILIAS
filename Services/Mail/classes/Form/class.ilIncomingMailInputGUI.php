<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilIncomingMailInputGUI
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */
class ilIncomingMailInputGUI extends ilRadioGroupInputGUI
{
    protected bool $freeOptionChoice = true;
    protected bool $optionsInitialized = false;

    public function __construct(string $title = '', string $post_var = '', bool $freeOptionChoice = true)
    {
        parent::__construct($title, $post_var);
        $this->setFreeOptionChoice($freeOptionChoice);
    }
    
    protected function initializeOptions() : void
    {
        if (!$this->optionsInitialized) {
            $this->addSubOptions();
            $this->optionsInitialized = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getOptions() : array
    {
        $this->initializeOptions();
        return parent::getOptions();
    }

    /**
     * @inheritdoc
     */
    public function setValueByArray($a_values) : void
    {
        $this->initializeOptions();
        parent::setValueByArray($a_values);
    }

    /**
     * @inheritdoc
     */
    public function checkInput() : bool
    {
        $this->initializeOptions();
        return parent::checkInput();
    }

    /**
     * @inheritdoc
     */
    public function render() : string
    {
        $this->initializeOptions();
        return parent::render();
    }

    /**
     * @inheritdoc
     */
    public function getItemByPostVar($a_post_var)
    {
        $this->initializeOptions();
        return parent::getItemByPostVar($a_post_var);
    }

    /**
     * @inheritdoc
     */
    public function getSubInputItemsRecursive() : array
    {
        $this->initializeOptions();
        return parent::getSubInputItemsRecursive();
    }

    
    public function isFreeOptionChoice() : bool
    {
        return $this->freeOptionChoice;
    }

    
    public function setFreeOptionChoice(bool $freeOptionChoice) : void
    {
        $this->freeOptionChoice = $freeOptionChoice;
    }

    
    private function addSubOptions() : void
    {
        global $DIC;
        
        $incomingLocal = new ilRadioOption(
            $DIC->language()->txt('mail_incoming_local'),
            ilMailOptions::INCOMING_LOCAL
        );
        $incomingLocal->setDisabled($this->getDisabled());

        $incomingExternal = new ilRadioOption(
            $DIC->language()->txt('mail_incoming_smtp'),
            ilMailOptions::INCOMING_EMAIL
        );
        $incomingExternal->setDisabled($this->getDisabled());

        $incomingBoth = new ilRadioOption(
            $DIC->language()->txt('mail_incoming_both'),
            ilMailOptions::INCOMING_BOTH
        );
        $incomingBoth->setDisabled($this->getDisabled());

        $this->addOption($incomingLocal);
        $this->addOption($incomingExternal);
        $this->addOption($incomingBoth);

        $incomingExternalAddressChoice = new ilRadioGroupInputGUI('', 'mail_address_option');
        $incomingExternalAddressChoice->setDisabled($this->getDisabled());

        $sub_mail_opt1 = new ilRadioOption(
            $DIC->language()->txt('mail_first_email'),
            ilMailOptions::FIRST_EMAIL
        );
        $sub_mail_opt1->setDisabled($this->getDisabled());

        $sub_mail_opt2 = new ilRadioOption(
            $DIC->language()->txt('mail_second_email'),
            ilMailOptions::SECOND_EMAIL
        );
        $sub_mail_opt2->setDisabled($this->getDisabled());
        $sub_mail_opt3 = new ilRadioOption(
            $DIC->language()->txt('mail_both_email'),
            ilMailOptions::BOTH_EMAIL
        );
        $sub_mail_opt3->setDisabled($this->getDisabled());

        $incomingBothAddressChoice = new ilRadioGroupInputGUI('', 'mail_address_option_both');
        $incomingBothAddressChoice->setDisabled($this->getDisabled());
        $sub_both_opt1 = new ilRadioOption(
            $DIC->language()->txt('mail_first_email'),
            ilMailOptions::FIRST_EMAIL
        );
        $sub_both_opt1->setDisabled($this->getDisabled());

        $sub_both_opt2 = new ilRadioOption(
            $DIC->language()->txt('mail_second_email'),
            ilMailOptions::SECOND_EMAIL
        );
        $sub_both_opt2->setDisabled($this->getDisabled());
        $sub_both_opt3 = new ilRadioOption(
            $DIC->language()->txt('mail_both_email'),
            ilMailOptions::BOTH_EMAIL
        );
        $sub_both_opt3->setDisabled($this->getDisabled());
        $email_info = [];
        if (!$this->isFreeOptionChoice()) {
            $email_info = [];
            if (
//				!strlen(ilObjUser::_lookupEmail($DIC->user()->getId())) ||
                $DIC->settings()->get('usr_settings_disable_mail_incoming_mail') === '1') {
                $this->setDisabled(true);
            }

            if ($DIC->user()->getEmail() === '' ||
                $DIC->settings()->get('usr_settings_disable_mail_incoming_mail') === '1'
            ) {
                $sub_mail_opt1->setDisabled(true);
                $sub_mail_opt1->setInfo($DIC->language()->txt('first_email_missing_info'));
                $sub_mail_opt3->setDisabled(true);
                $sub_mail_opt3->setInfo($DIC->language()->txt('first_email_missing_info'));
                $sub_both_opt1->setDisabled(true);
                $sub_both_opt1->setInfo($DIC->language()->txt('first_email_missing_info'));
                $sub_both_opt3->setDisabled(true);
                $sub_both_opt3->setInfo($DIC->language()->txt('first_email_missing_info'));
            } else {
                $email_info[] = $DIC->user()->getEmail();
            }

            if ($DIC->user()->getSecondEmail() === '' ||
                $DIC->settings()->get('usr_settings_disable_mail_incoming_mail') === '1'
            ) {
                $sub_mail_opt2->setDisabled(true);
                $sub_mail_opt2->setInfo($DIC->language()->txt('second_email_missing_info'));
                $sub_mail_opt3->setDisabled(true);
                $sub_mail_opt3->setInfo($DIC->language()->txt('second_email_missing_info'));
                $sub_both_opt2->setDisabled(true);
                $sub_both_opt2->setInfo($DIC->language()->txt('second_email_missing_info'));
                $sub_both_opt3->setDisabled(true);
                $sub_both_opt3->setInfo($DIC->language()->txt('second_email_missing_info'));
            } else {
                $email_info[] = $DIC->user()->getSecondEmail();
            }
            
            if (count($email_info) > 1) {
                $sub_mail_opt1->setInfo($email_info[0]);
                $sub_both_opt1->setInfo($email_info[0]);
                $sub_mail_opt2->setInfo($email_info[1]);
                $sub_both_opt2->setInfo($email_info[1]);
                $sub_mail_opt3->setInfo(implode(', ', $email_info));
                $sub_both_opt3->setInfo(implode(', ', $email_info));
            }
            
            if (count($email_info) === 1) {
                $incomingExternal->setInfo($email_info[0]);
                $incomingBoth->setInfo($email_info[0]);
            } else {
                $incomingExternalAddressChoice->addOption($sub_mail_opt1);
                $incomingExternalAddressChoice->addOption($sub_mail_opt2);
                $incomingExternalAddressChoice->addOption($sub_mail_opt3);
                    
                $incomingBothAddressChoice->addOption($sub_both_opt1);
                $incomingBothAddressChoice->addOption($sub_both_opt2);
                $incomingBothAddressChoice->addOption($sub_both_opt3);

                $incomingExternal->addSubItem($incomingExternalAddressChoice);
                $incomingBoth->addSubItem($incomingBothAddressChoice);
            }
        } else {
            $incomingExternalAddressChoice->addOption($sub_mail_opt1);
            $incomingExternalAddressChoice->addOption($sub_mail_opt2);
            $incomingExternalAddressChoice->addOption($sub_mail_opt3);
            $incomingBothAddressChoice->addOption($sub_both_opt1);
            $incomingBothAddressChoice->addOption($sub_both_opt2);
            $incomingBothAddressChoice->addOption($sub_both_opt3);
            
            $incomingExternal->addSubItem($incomingExternalAddressChoice);
            $incomingBoth->addSubItem($incomingBothAddressChoice);
        }
    }
}
