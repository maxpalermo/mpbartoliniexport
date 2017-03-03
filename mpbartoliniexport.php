<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('_PS_VERSION_')){exit;}
 
class MpBartoliniExport extends Module
{
  public function __construct()
  {
    $this->name = 'mpbartoliniexport';
    $this->tab = 'administration';
    $this->version = '1.0.0';
    $this->author = 'mpsoft';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
    $this->bootstrap = true;
 
    parent::__construct();
 
    $this->displayName = $this->l('Esporta ordini per Bartolini');
    $this->description = $this->l('Permette di esportare gli ordini selezionati nel formato CSV easysped Bartolini.');
 
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
 
    if (!Configuration::get('MP_BARTOLINI_EXPORT_NAME')) 
    {
      $this->warning = $this->l('No name provided');
    }
  }
  
  public function install()
    {
      if (Shop::isFeatureActive())
      {
        Shop::setContext(Shop::CONTEXT_ALL);
      }

      if (!parent::install() ||
        !$this->registerHook('displayAdminOrder') ||
        !$this->registerHook('displayBackOfficeHeader') ||
	!$this->installTab() ||
        !Configuration::updateValue('MP_BARTOLINI_EXPORT_NAME', 'mp_bartolini_export') ||
        !Configuration::updateValue('MP_BARTOLINI_EXPORT_ID_CUSTOMER', '0') ||
        !Configuration::updateValue('MP_BARTOLINI_EXPORT_ID_PO', '1') ||
        !Configuration::updateValue('MP_BARTOLINI_EXPORT_COLLI', '1') ||
        !Configuration::updateValue('MP_BARTOLINI_EXPORT_PESO', '1') 
      )
      {
        return false;
      }

      return true;
    }
    
    public function uninstall()
    {
      if (!parent::uninstall() 
              || !Configuration::deleteByName('MP_BARTOLINI_EXPORT_NAME') 
              || !Configuration::deleteByName('MP_BARTOLINI_EXPORT_ID_CUSTOMER') 
              || !Configuration::deleteByName('MP_BARTOLINI_EXPORT_ID_PO')
              || !Configuration::deleteByName('MP_BARTOLINI_EXPORT_COLLI')
              || !Configuration::deleteByName('MP_BARTOLINI_EXPORT_PESO'))
          
      {
        return false;
      }
      return true;
    }
    
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $id_customer    = strval(Tools::getValue('MP_BARTOLINI_EXPORT_ID_CUSTOMER'));
            $id_po          = strval(Tools::getValue('MP_BARTOLINI_EXPORT_ID_PO'));
            $colli          = strval(Tools::getValue('MP_BARTOLINI_EXPORT_COLLI'));
            $peso           = strval(Tools::getValue('MP_BARTOLINI_EXPORT_PESO'));
            
            Configuration::updateValue('MP_BARTOLINI_EXPORT_ID_CUSTOMER', $id_customer);
            Configuration::updateValue('MP_BARTOLINI_EXPORT_ID_PO', $id_po);
            Configuration::updateValue('MP_BARTOLINI_EXPORT_COLLI', $colli);
            Configuration::updateValue('MP_BARTOLINI_EXPORT_PESO', $peso);
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        return $output.$this->displayForm();
    }
    
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Codice cliente'),
                    'name' => 'MP_BARTOLINI_EXPORT_ID_CUSTOMER',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Codice P.O.'),
                    'name' => 'MP_BARTOLINI_EXPORT_ID_PO',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Colli'),
                    'name' => 'MP_BARTOLINI_EXPORT_COLLI',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Peso'),
                    'name' => 'MP_BARTOLINI_EXPORT_PESO',
                    'size' => 20,
                    'required' => true
                ),
                
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['MP_BARTOLINI_EXPORT_ID_CUSTOMER'] = Configuration::get('MP_BARTOLINI_EXPORT_ID_CUSTOMER');
        $helper->fields_value['MP_BARTOLINI_EXPORT_ID_PO'] = Configuration::get('MP_BARTOLINI_EXPORT_ID_PO');
        $helper->fields_value['MP_BARTOLINI_EXPORT_COLLI'] = Configuration::get('MP_BARTOLINI_EXPORT_COLLI');
        $helper->fields_value['MP_BARTOLINI_EXPORT_PESO'] = Configuration::get('MP_BARTOLINI_EXPORT_PESO');
        
        $html = $helper->generateForm($fields_form);
        
        return  $html;
    }
    
    public function hookDisplayAdminOrder($params)
    {
            $id_order = (int)Tools::getValue('id_order');
            //Assign Smarty Variables
            $this->context->smarty->assign(array(
                    'list_status' => 'ELENCO STATI',
                    'start_date' => "DATA INIZIO",
                    'end_date' => "DATA INIZIO",
                    'linkToAdmin' => Context::getContext()->link->getAdminLink('AdminMpBartoliniExport'),
                    'id_order' => $id_order,
            ));
            return $this->display(__FILE__, 'export_page.tpl');
    }

    public function hookDisplayBackOfficeHeader()
    {
            $this->context->controller->addCSS($this->_path.'views/css/admin.css') || $this->context->controller->addJS($this->_path.'views/js/label.js');
    }
    
    public function installTab()
    {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminMpBartoliniExport';
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang)
            {
                    $tab->name[$lang['id_lang']] = 'MP Bartolini Export';
            }
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
            $tab->module = $this->name;
            return $tab->add();
    }

    public function uninstallTab()
    {
            $id_tab = (int)Tab::getIdFromClassName('AdminMpBartoliniExport');
            if ($id_tab)
            {
                    $tab = new Tab($id_tab);
                    return $tab->delete();
            }
            else
            {
                    return false;
            }
    }
}