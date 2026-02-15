<?php
/**
 * Module descriptor for Bulk Add Products
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module BulkAddProducts
 */
class modBulkaddproducts extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        
        $this->db = $db;

        // Id for module (must be unique).
        // Use a high custom module number to avoid conflicts
        $this->numero = 207500;
        
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'bulkaddproducts';
        
        // Family of module ('base', 'products', 'crm', 'financial', 'hr', 'projects', 'technic', 'interface', 'other')
        $this->family = 'interface';
        
        // Module position in the family (0 for first one)
        $this->module_position = '0';
        
        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = array('interface' => array('position' => '1', 'label' => $langs->trans("ModuleFamilyInterface")));

        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module to add bulk functionality for adding products to customer and supplier documents";
        
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "This module provides hook-based functionality to add multiple products to customer and supplier documents including Quotations, Sales Orders, Customer Invoices, Purchase Orders and Supplier Invoices.";

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0.5';

        // Url to the file with your last numberversion of this module
        $this->url_last_version = '';

        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module.
        $this->picto = 'generic';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            'hooks' => array('ordercard', 'orderline', 'propalcard', 'propalline', 'invoicecard', 'invoiceline', 'supplier_proposalcard', 'supplier_proposalline', 'ordersuppliercard', 'ordersupplierline', 'supplier_ordercard', 'supplier_orderline', 'invoicesuppliercard', 'invoicesupplierline'),
            'triggers' => 0,
            'login' => 0,
            'substitutions' => 0,
            'menus' => 0,
            'theme' => 0,
            'tpl' => 0,
            'barcode' => 0,
            'models' => 0,
            'css' => 0,
            'js' => 0,
            'moduleforexternal' => 0,
        );

        // Data directories to create when module is enabled.
        $this->dirs = array("/bulkaddproducts/temp");

        // Config pages. Put here list of php page, stored into bulkaddproducts/admin directory, to use to setup module.
        $this->config_page_url = array();

        // Dependencies
        $this->hidden = false; // A condition to hide module
        $this->depends = array(); // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = array("bulkaddproducts@bulkaddproducts");
        $this->phpmin = array(7, 0); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(17, 0); // Minimum version of Dolibarr required by module (17.x–22.x)

        // Constants
        $this->const = array();

        // Array to add new pages in new tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes/Widgets
        $this->boxes = array();

        // Cronjobs
        $this->cronjobs = array();

        // Permissions provided by this module
        $this->rights = array();

        // Main menu entries to add
        $this->menu = array();
    }

    /**
     * Function called when module is enabled.
     * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf, $langs;

        // Check compatibility before enabling the module
        if (!$this->checkCompatibility()) {
            $this->error = $langs->trans("ModuleNotCompatibleWithCurrentVersion");
            return 0;
        }

        // Remove permissions and default values
        $this->remove($options);

        $sql = array();

        // ODT template
        //$sql = array();

        return $this->_init($sql, $options);
    }

    /**
     * Check if the module is compatible with the current Dolibarr version
     *
     * @return bool True if compatible, false otherwise
     */
    private function checkCompatibility()
    {
        global $conf;

        // Get Dolibarr version with multiple fallbacks (required during module init when $conf may not be fully loaded)
        $dolibarrVersion = '0.0.0';
        if (!empty($conf->global->MAIN_VERSION_LAST_INSTALL)) {
            $dolibarrVersion = $conf->global->MAIN_VERSION_LAST_INSTALL;
        } elseif (defined('DOL_VERSION')) {
            $dolibarrVersion = DOL_VERSION;
        }

        // Compatible with Dolibarr 17.0.0 and later (including 22.x)
        return version_compare($dolibarrVersion, '17.0.0', '>=');
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }

    /**
     * Get module name
     *
     * @return string Module name
     */
    public function getName()
    {
        global $langs;
        $langs->load("bulkaddproducts@bulkaddproducts");
        return $langs->trans("ModuleBulkaddproductsName");
    }

    /**
     * Get module description
     *
     * @return string Module description
     */
    public function getDesc()
    {
        global $langs;
        $langs->load("bulkaddproducts@bulkaddproducts");
        return $langs->trans("ModuleBulkaddproductsDesc");
    }

    /**
     * Get module version
     *
     * @param int $translated 1=Translated, 0=Not translated
     * @return string Module version
     */
    public function getVersion($translated = 1)
    {
        return $this->version;
    }

    /**
     * Get module publisher
     *
     * @return string Module publisher
     */
    public function getPublisher()
    {
        return 'LH Wong';
    }
}
