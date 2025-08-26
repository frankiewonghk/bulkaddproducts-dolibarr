<?php
/**
 * Hook class for bulkaddproducts.
 * Place in htdocs/custom/bulkaddproducts/class/actions_bulkaddproducts.class.php
 */

class ActionsBulkaddproducts
{
    public $results = array();
    public $resprints;
    public $errors = array();
    public $usercancreatepropal;
    public $usercancreatecommande;
    public $usercancreateinvoice;
    public $usercancreatefournisseur_propal;
    public $usercancreatefournisseur_order;
    public $usercancreatefournisseur_invoice;

    private static $rowCounter = 1; // Static counter for auto-incrementing row numbers

    // Add these missing properties
    private $dolibarrVersion;
    public $isCompatible = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $conf;
        
        // Get Dolibarr version
        if (isset($conf->global->MAIN_VERSION_LAST_INSTALL)) {
            $this->dolibarrVersion = $conf->global->MAIN_VERSION_LAST_INSTALL;
        } else {
            // Fallback: try to get version from constants
            $this->dolibarrVersion = defined('DOL_VERSION') ? DOL_VERSION : '0.0.0';
        }
        
        // Check if version is 17.0.0 or higher
        if (version_compare($this->dolibarrVersion, '17.0.0', '>=')) {
            $this->isCompatible = true;
        } else {
            $this->isCompatible = false;
        }
    }

    /**
     * doActions hook
     *
     * @param array         $parameters
     * @param CommonObject  $object
     * @param string        $action
     * @param HookManager   $hookmanager
     * @return int          <0 on error, 0 no action taken, >0 action taken
     */

     public function doActions($parameters, &$object, &$action, $hookmanager)
     {
        global $user;
        global $mysoc;
        global $soc;
        global $conf;
        

        // Set user permissions
        $this->usercancreatepropal = $user->hasRight("propal", "creer");
        $this->usercancreatecommande = $user->hasRight("commande", "creer");
        $this->usercancreateinvoice = $user->hasRight("facture", "creer");
        $this->usercancreatefournisseur_propal = $user->hasRight("fournisseur", "propal", "creer") || $user->hasRight("supplier_proposal", "creer");
        $this->usercancreatefournisseur_order = $user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer");
        $this->usercancreatefournisseur_invoice = $user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer");


        // Ensure we are on order card or propal card or contract card or invoice card or invoicerec card or supplier_proposal card or supplier_order card or supplier_invoice card or supplier_invoice_rec card
        
        if (empty($parameters['currentcontext']) 
            || (strpos($parameters['currentcontext'], 'ordercard') === false
            && strpos($parameters['currentcontext'], 'propalcard') === false
            && strpos($parameters['currentcontext'], 'invoicecard') === false
            && strpos($parameters['currentcontext'], 'supplier_proposalcard') === false
            && strpos($parameters['currentcontext'], 'ordersuppliercard') === false
            && strpos($parameters['currentcontext'], 'invoicesuppliercard') === false
            ))
        {
            return 0;
        }
        
        
        if($action == 'addNewRowAjax') {
            if ((strpos($parameters['currentcontext'], 'ordercard') !== false && $object->status == Commande::STATUS_DRAFT && $this->usercancreatecommande)
                || (strpos($parameters['currentcontext'], 'propalcard') !== false && $object->status == Propal::STATUS_DRAFT && $this->usercancreatepropal)
                || (strpos($parameters['currentcontext'], 'invoicecard') !== false && $object->status == Facture::STATUS_DRAFT && $this->usercancreateinvoice)
                || (strpos($parameters['currentcontext'], 'supplier_proposalcard') !== false && $object->status == SupplierProposal::STATUS_DRAFT && $this->usercancreatefournisseur_propal)
                || (strpos($parameters['currentcontext'], 'ordersuppliercard') !== false && $object->status == CommandeFournisseur::STATUS_DRAFT && $this->usercancreatefournisseur_order)
                || (strpos($parameters['currentcontext'], 'invoicesuppliercard') !== false && $object->status == FactureFournisseur::STATUS_DRAFT && $this->usercancreatefournisseur_invoice)
                ) {
				// Ensure we have a valid order object
                if (empty($object->id)) {
                    return 0;
                }
                
                // Ensure thirdparty is loaded in the order object
                if (empty($object->thirdparty) && !empty($object->socid)) {
                    $object->fetch_thirdparty();
                    if (!empty($object->thirdparty)) {
                        $soc = $object->thirdparty;
                    }
                }

                if(empty($soc) || !is_object($soc)) {
                    $soc = $object->thirdparty;
                }

                if(empty($mysoc) || !is_object($mysoc)) {
                    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                    $mysoc = new Societe($GLOBALS['db']);
                    $mysoc->setMysoc($GLOBALS['conf']);
                }
                
                // Initialize required objects if they don't exist (required by the template)
                global $form;
          
                if (empty($form) || !is_object($form)) {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
                    $form = new Form($GLOBALS['db']);
                }
                
                // Capture only the add-line snippet and stop page rendering
                ob_start();
                global $forceall, $senderissupplier, $dateSelector, $inputalsopricewithtax;
                global $forcetoshowtitlelines;
                $forcetoshowtitlelines = true;
                if(strpos($parameters['currentcontext'], 'ordercard') !== false){       
			       $inputalsopricewithtax = 1;
                   $object->formAddObjectLine(1, $mysoc, $soc);
                }
                elseif(strpos($parameters['currentcontext'], 'ordersuppliercard') !== false){
                    $forceall = 1;
                    $dateSelector = 0;
                    $inputalsopricewithtax = 1;
                    $senderissupplier = 2; // $senderissupplier=2 is same than 1 but disable test on minimum qty and disable autofill qty with minimum.
                    if(version_compare($this->dolibarrVersion, '21.0.0', '>=')) {
                        if (getDolGlobalInt('SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY')) {
                            $senderissupplier = getDolGlobalInt('SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY');
                        }
                    }elseif(version_compare($this->dolibarrVersion, '18.0.0', '>=')) {
                        if (getDolGlobalString('SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY')) {
                            $senderissupplier = 1;
                        }
                    }elseif(version_compare($this->dolibarrVersion, '17.0.0', '>=')) {
                        if (!empty($conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY)) {
                            $senderissupplier = 1;
                        }
                    }
                    $object->formAddObjectLine(1, $soc, $mysoc);
                }
                elseif(strpos($parameters['currentcontext'], 'propalcard') !== false){
                    $inputalsopricewithtax = 1;
                    $object->formAddObjectLine(1, $mysoc, $soc);
                }
                elseif(strpos($parameters['currentcontext'], 'invoicecard') !== false){
                    $inputalsopricewithtax = 1;
                    $object->formAddObjectLine(1, $mysoc, $soc);
                }
                elseif(strpos($parameters['currentcontext'], 'supplier_proposalcard') !== false){
                    $forceall = 1;
                    $dateSelector = 0;
                    $inputalsopricewithtax = 1;
                    $senderissupplier = 2; // $senderissupplier=2 is same than 1 but disable test on minimum qty.
                    if(version_compare($this->dolibarrVersion, '21.0.0', '>=')) {
                        if (getDolGlobalInt('SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY')) {
                            $senderissupplier = getDolGlobalInt('SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY');
                        }
                    }elseif(version_compare($this->dolibarrVersion, '19.0.0', '>=')) {
                        if (getDolGlobalString('SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY')) {
                            $senderissupplier = 1;
                        }                
                    }elseif(version_compare($this->dolibarrVersion, '17.0.0', '>=')) {
                        if (!empty($conf->global->SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY)) {
                            $senderissupplier = 1;
                        }
                    }
                    $object->formAddObjectLine($dateSelector, $soc, $mysoc);
                }
                elseif(strpos($parameters['currentcontext'], 'invoicesuppliercard') !== false){
                    $forceall = 1;
                    $dateSelector = 0;
                    $inputalsopricewithtax = 1;
                    $senderissupplier = 2; // $senderissupplier=2 is same than 1 but disable test on minimum qty and disable autofill qty with minimum.
                    
                    if(version_compare($this->dolibarrVersion, '21.0.0', '>=')) {
                        if (getDolGlobalInt('SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY')) {
                            $senderissupplier = getDolGlobalInt('SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY');
                        }
                    }elseif(version_compare($this->dolibarrVersion, '19.0.0', '>=')) {
                        if (getDolGlobalString('SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY')) {
                            $senderissupplier = 1;
                        }
                    }elseif(version_compare($this->dolibarrVersion, '17.0.0', '>=')) {
                        if (!empty($conf->global->SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY)) {
                            $senderissupplier = 1;
                        }
                    }
                    
                    $object->formAddObjectLine(1, $soc, $mysoc);
                }
                
                $snippet = ob_get_clean();
                
                // Get the row number from the AJAX request, or use auto-increment
                $rowNumber = GETPOST('rowNumber', 'int');
                if (empty($rowNumber)) {
                    $rowNumber = self::$rowCounter++;
                }
                
                // Modify input element IDs by appending the row number
                $snippet = $this->modifyFormElementIdentifiers($snippet, $rowNumber);
                
                // Modify JavaScript functions and selectors by appending the row number
                $snippet = $this->modifyJavascriptFunctions($snippet, $rowNumber);
                
                // Modify CKEditor setup for dp_desc by appending the row number
                if (isModEnabled('fckeditor')) {
                    $snippet = $this->modifyCKeditor($snippet, $rowNumber);
                }

                // Ensure clean output for AJAX response
                header('Content-Type: text/html; charset=utf-8');

                print $snippet;
                exit;
			}
        }
     }

    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user;
        global $langs;
        
        // Set user permissions
         // Set user permissions
         $this->usercancreatepropal = $user->hasRight("propal", "creer");
         $this->usercancreatecommande = $user->hasRight("commande", "creer");
         $this->usercancreateinvoice = $user->hasRight("facture", "creer");
         $this->usercancreatefournisseur_propal = $user->hasRight("fournisseur", "propal", "creer") || $user->hasRight("supplier_proposal", "creer");
         $this->usercancreatefournisseur_order = $user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer");
         $this->usercancreatefournisseur_invoice = $user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer");
        
        // Ensure we are on order card or propal card or contract card or invoice card or invoicerec card or supplier_proposal card or supplier_order card or supplier_invoice card or supplier_invoice_rec card
        if (empty($parameters['currentcontext']) 
            || (strpos($parameters['currentcontext'], 'ordercard') === false
            && strpos($parameters['currentcontext'], 'propalcard') === false
            && strpos($parameters['currentcontext'], 'invoicecard') === false
            && strpos($parameters['currentcontext'], 'supplier_proposalcard') === false
            && strpos($parameters['currentcontext'], 'ordersuppliercard') === false
            && strpos($parameters['currentcontext'], 'invoicesuppliercard') === false
            ))
        {
            return 0;
        }

        if (($action != 'selectlines' && $action != 'editline') 
        && ((strpos($parameters['currentcontext'], 'ordercard') !== false && $object->status == Commande::STATUS_DRAFT && $this->usercancreatecommande)
            || (strpos($parameters['currentcontext'], 'propalcard') !== false && $object->status == Propal::STATUS_DRAFT && $this->usercancreatepropal)
            || (strpos($parameters['currentcontext'], 'invoicecard') !== false && $object->status == Facture::STATUS_DRAFT && $this->usercancreateinvoice)
            || (strpos($parameters['currentcontext'], 'supplier_proposalcard') !== false && $object->status == SupplierProposal::STATUS_DRAFT && $this->usercancreatefournisseur_propal)
            || (strpos($parameters['currentcontext'], 'ordersuppliercard') !== false && $object->status == CommandeFournisseur::STATUS_DRAFT && $this->usercancreatefournisseur_order)
            || (strpos($parameters['currentcontext'], 'invoicesuppliercard') !== false && $object->status == FactureFournisseur::STATUS_DRAFT && $this->usercancreatefournisseur_invoice)
            )) {
            // Reset counter when page loads for a new order
            if (empty($object->lines) || count($object->lines) == 0) {
                self::$rowCounter = 1;
            }

            print '<div style="margin-bottom:50px;margin-top:-20px;">
                <button id="btn_custom_add_row" type="button" class="butAction" onclick="addNewRowAjax()" style="margin-right:0px;"><span class="fa fa-plus-circle valignmiddle btnTitle-icon" style="margin-right:5px;"></span> '.$langs->trans('Add').'</button><button id="btn_custom_save_all" type="button" class="butAction" style="margin-right:0px;">'.$langs->trans('Save').'</button>
               
            </div>';

            print '<script>
            // Define module status variables from PHP
            var fckeditorEnabled = '.(isModEnabled('fckeditor') ? 'true' : 'false').';
            
            $("#addline").replaceWith(\'<button name="deleterowbyhidden" class="butActionDelete" type="button"><span class="fas fa-trash pictodelete" title="Delete"></span></button>\');

            var currentRowNumber = 1; // Initialize row counter
            function addNewRowAjax() {
                $("#btn_custom_save_all").show();
                $("#btn_custom_add_row").prop("disabled", true);
                $("#btn_custom_add_row").addClass("butActionRefused");
                $.ajax({
                    url: "'.DOL_URL_ROOT.'/'.$this->getCurrentModulePath($parameters['currentcontext']).'",
                    type: "POST",
                    data: {action: "addNewRowAjax", id: "'.$object->id.'", token: "'.newToken().'", rowNumber: currentRowNumber},
                    success: function(response) {
                        $("#tablelines").append(response);
                        currentRowNumber++; // Increment counter for next row
                        $("#btn_custom_add_row").prop("disabled", false);  
                        $("#btn_custom_add_row").removeClass("butActionRefused");
                    }
                });
            }

            // Use event delegation for dynamically added buttons
            $(document).on("click", "button[name=\'deleterowbyhidden\']", function() {
                // Remove the closest "tr" (the row with the button)
                var $tr = $(this).closest("tr");
                // Also remove the previous "tr" if its class is exactly "liste_titre nodrag nodrop"
                var $prevTr = $tr.prev("tr");
                if ($prevTr.length && ($prevTr.attr("class") || "") === "liste_titre nodrag nodrop") {
                    $prevTr.remove();
                }
                // Also remove the next "tr" if its id starts with "trlinefordates"
                var $nextTr = $tr.next("tr");
                if ($nextTr.length && $nextTr.attr("id") && $nextTr.attr("id").indexOf("trlinefordates") === 0) {
                    $nextTr.remove();
                }
                $tr.remove();
            });

            $("#btn_custom_save_all").click(function() {
                // Loop through all inputs and textareas in the table #tablelines
                // Group them by rows that start with class "pair nodrag nodrop nohoverpair"
          
                $("#btn_custom_save_all").prop("disabled", true);
                $("#btn_custom_save_all").addClass("butActionRefused");
                $("#btn_custom_add_row").hide();

                var table = $("#tablelines");
                var groups = [];
                var currentGroup = null;
                
                // Loop through all rows in the table
                table.find("tr").each(function() {
                    var row = $(this);
                    var rowClasses = row.attr("class") || "";
                    
                    // Check if this row starts with the special grouping class
                    if (rowClasses.indexOf("pair nodrag nodrop nohoverpair") === 0) {
                        // Start a new group
                        currentGroup = {
                            row: row,
                            inputs: []
                        };
                        groups.push(currentGroup);
                        
                        console.log("=== New Group Started ===");
                        console.log("Row classes:", rowClasses);
                    }
                    
                    // If we have a current group, collect all inputs, textareas, and selects from this row
                    if (currentGroup) {
                        // Collect inputs in the correct visual order by going through each cell (td/th)
                        row.find("td, th").each(function() {
                            var cell = $(this);
                            // Find inputs within this specific cell to maintain column order
                            cell.find("input, textarea, select").each(function() {
                                var input = $(this);
                                var inputId = input.attr("id");
                                var inputName = input.attr("name");
                                // Fix: Properly detect type for textarea, select, and input
                                var tagName = input.prop("tagName").toLowerCase();
                                var inputType;
                                if (tagName === "textarea") {
                                    inputType = "textarea";
                                } else if (tagName === "select") {
                                    inputType = "select";
                                } else {
                                    inputType = input.attr("type") || "text";
                                }
                                // If inputType is radio, then we should remove the last number part of the inputName
                                if (inputType === "radio") {
                                    inputName = inputName.replace(/\d+$/, "");
                                }
                                
                                // Include elements that have either an id or a name attribute
                                // The name attribute is required for PHP processing, id is optional
                                if (inputName) {
                                    currentGroup.inputs.push({
                                        element: input,
                                        id: inputId || "",
                                        name: inputName,
                                        type: inputType || "text"
                                    });
                                    
                                    // Log the input details for testing
                                    var inputValue;
                                    var shouldLog = true;
                                    
                                    if (inputType === "radio") {
                                        // For radio buttons, only log if selected
                                        if (input.is(":checked")) {
                                            inputValue = input.val();
                                        } else {
                                            shouldLog = false; // Skip logging unchecked radio buttons
                                        }
                                    } else {
                                        // For other input types, use the normal value
                                        inputValue = input.val();
                                    }
                                    
                                    // Only log if we should (skip unchecked radio buttons)
                                    if (shouldLog) {
                                        console.log("Input found in group:", {
                                            id: inputId || "none",
                                            name: inputName,
                                            type: inputType || "text",
                                            value: inputValue
                                        });
                                    }
                                }
                            });
                        });
                    }
                });
                
                // Log summary of all groups
                console.log("=== Summary ===");
                console.log("Total groups found:", groups.length);
                groups.forEach(function(group, index) {
                    console.log("Group " + (index + 1) + " has " + group.inputs.length + " inputs");
                });
                
                // Process groups sequentially to maintain order
                function processGroupsSequentially(groups, index = 0) {
                    if (index >= groups.length) {
                        console.log("All groups saved in order");
                        $("#btn_custom_save_all").prop("disabled", false);
                        $("#btn_custom_save_all").removeClass("butActionRefused");
                        $("#btn_custom_add_row").show();
                        location.reload();
                        return;
                    }
                    
                    var group = groups[index];
                    var data = {};
                    
                    // Collect all form element values from this group
                    group.inputs.forEach(function(input) {
                        var inputId = input.id;
                        var inputName = input.name;
                        var inputType = input.type;
                        var inputValue;
               
                        if (inputName) {
                            if (inputType === "radio") {
                                // For radio buttons, only include if checked
                                if (input.element.is(":checked")) {
                                    inputValue = input.element.val();
                                    data[inputName] = inputValue;
                                }
                            } else if (inputType === "checkbox") {
                                // For checkboxes, include if checked
                                if (input.element.is(":checked")) {
                                    data[inputName] = input.element.val();
                                } else {
                                    data[inputName] = ""; // Unchecked checkbox
                                }
                            } else if (inputType === "textarea" && fckeditorEnabled && CKEDITOR.instances[inputId]) {
                                data[inputName] = CKEDITOR.instances[inputId].getData();
                            } else {
                                // For other input types, textarea, and select
                                inputValue = input.element.val();
                                data[inputName] = inputValue;
                            }
                        }
                    });
                    
                    console.log("Processing group " + (index + 1) + " of " + groups.length + ":", data);
                    
                    // Build the POST data with individual parameters instead of nested object
                    var postData = {
                        action: "addline", 
                        id: "'.$object->id.'", 
                        token: "'.newToken().'"
                    };
                    
                    // Add all form fields as individual POST parameters
                    for (var fieldName in data) {
                        if (data.hasOwnProperty(fieldName)) {
                            postData[fieldName] = data[fieldName];
                        }
                    }
                    
                    $.ajax({
                        url: "'.DOL_URL_ROOT.'/'.$this->getCurrentModulePath($parameters['currentcontext']).'",
                        type: "POST",
                        data: postData,
                        success: function(response) {
                            console.log("Group " + (index + 1) + " saved successfully");
                            // Process next group
                            processGroupsSequentially(groups, index + 1);
                        },
                        error: function(xhr, status, error) {
                            console.error("Error for group " + (index + 1) + ":", error);
                            // Continue with next group even if this one failed
                            processGroupsSequentially(groups, index + 1);
                        }
                    });
                }
                
                // Start processing groups sequentially
                processGroupsSequentially(groups);
 
            });

            </script>';
            
            return 0;
            
        }

        return 0;
    }
    
    /**
     * Reset the row counter (useful when starting a new order)
     */
    
    /**
     * Get the current row counter value
     */
    public static function getCurrentRowNumber() {
        return self::$rowCounter;
    }
    


    /**
     * Modify all form element IDs and names in HTML snippet by appending a number
     * @param string $html HTML snippet
     * @param int $rowNumber Number to append to IDs and names
     * @return string Modified HTML
     */
    private function modifyFormElementIdentifiers($html, $rowNumber) {
        // Split HTML into parts: HTML elements and JavaScript content
        $parts = preg_split('/(<script[^>]*>.*?<\/script>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = 0; $i < count($parts); $i++) {
            // Only process HTML parts (skip JavaScript parts)
            if ($i % 2 == 0) {
                $part = $parts[$i];
                
                // Pattern to match any element with id attribute
                $idPattern = '/<(\w+)([^>]*)\bid\s*=\s*["\']([^"\']+)["\']([^>]*)>/i';
                
                // Pattern to match any element with name attribute
                $namePattern = '/<(\w+)([^>]*)\bname\s*=\s*["\']([^"\']+)["\']([^>]*)>/i';
                
                // Replace elements with id attributes
                $part = preg_replace_callback($idPattern, function($matches) use ($rowNumber) {
                    $tagName = $matches[1];
                    $beforeId = $matches[2];
                    $idValue = $matches[3];
                    $afterId = $matches[4];
                    
                    // Create new tag with modified id
                    $newTag = '<' . $tagName . $beforeId . 'id="' . $idValue . $rowNumber . '"' . $afterId . '>';
                    
                    return $newTag;
                }, $part);
                
                // Replace elements with name attributes
                // Only replace input elements with type="radio"
                $part = preg_replace_callback(
                    '/<input([^>]*)\btype\s*=\s*["\']radio["\']([^>]*)\bname\s*=\s*["\']([^"\']+)["\']([^>]*)>/i',
                    function($matches) use ($rowNumber) {
                        $beforeType = $matches[1];
                        $afterType = $matches[2];
                        $nameValue = $matches[3];
                        $afterName = $matches[4];

                        // Rebuild the input tag with the modified name attribute
                        $newTag = '<input' . $beforeType . 'type="radio"' . $afterType . 'name="' . $nameValue . $rowNumber . '"' . $afterName . '>';
                        return $newTag;
                    },
                    $part
                );
                

                // Replace input elements with type="submit" with delete button
                $part = preg_replace('/<input[^>]*type\s*=\s*["\']submit["\'][^>]*>/i', '<button name="deleterowbyhidden" class="butActionDelete" type="button"><span class="fas fa-trash pictodelete" title="Delete"></span></button>', $part);

                // Replace maxwidth500 with maxwidth400
                $part = preg_replace('/maxwidth500/i', 'maxwidth400', $part);
                
                $parts[$i] = $part;
            }
        }
        
        // Rejoin the parts
        return implode('', $parts);
    }

    /**
     * Modify JavaScript functions and jQuery selectors in HTML snippet by appending a number
     * @param string $html HTML snippet
     * @param int $rowNumber Number to append to function names and selectors
     * @return string Modified HTML
     */
    private function modifyJavascriptFunctions($html, $rowNumber) {
        // Pattern to match JavaScript function declarations
        $functionPattern = '/function\s+(\w+)\s*\(/i';
        
        // Pattern to match jQuery selectors with ID or name
        $jqueryIdPattern = '/\$\(["\']#([^"\']+)["\']\)/g';
        $jqueryNamePattern = '/\$\(["\']\[name=["\']([^"\']+)["\']\]\)/g';
        
        // Pattern to match jQuery selectors with attribute selectors
        $jqueryAttrPattern = '/\$\(["\']\[([^"\']+)=["\']([^"\']+)["\']\]\)/g';
        
        // First, collect all function names that will be renamed (before processing)
        $renamedFunctions = array();
        preg_match_all($functionPattern, $html, $functionMatches);
        if (!empty($functionMatches[1])) {
            foreach ($functionMatches[1] as $functionName) {
                $renamedFunctions[$functionName] = $functionName . $rowNumber;
            }
        }
        
        // Replace function declarations
        $html = preg_replace_callback($functionPattern, function($matches) use ($rowNumber) {
            $functionName = $matches[1];
            $newFunctionName = $functionName . $rowNumber;
            
            return 'function ' . $newFunctionName . '(';
        }, $html);
        
        // Replace jQuery ID selectors (both $ and jQuery syntax) - handle multiple selectors
        $html = preg_replace_callback('/\$\(["\']([^"\']+)["\']\)/', function($matches) use ($rowNumber) {
            $selectors = $matches[1];
            
            // Split by comma and process each selector
            $selectorParts = array_map('trim', explode(',', $selectors));
            $processedParts = array();
            
            foreach ($selectorParts as $part) {
                if (preg_match('/^#([^,\s]+)/', $part, $idMatch)) {
                    // It's an ID selector
                    $processedParts[] = '#' . $idMatch[1] . $rowNumber;
                } elseif (preg_match('/^\[name=["\']([^"\']+)["\']\]/', $part, $nameMatch)) {
                    // It's a name selector
                    $processedParts[] = '[name=\'' . $nameMatch[1] . $rowNumber . '\']';
                } elseif (preg_match('/^\[([^=]+)=["\']([^"\']+)["\']\]/', $part, $attrMatch)) {
                    // It's an attribute selector
                    $attrName = $attrMatch[1];
                    $attrValue = $attrMatch[2];
                    
                    // Only modify if it's an id or name attribute
                    if ($attrName === 'id' || $attrName === 'name') {
                        $processedParts[] = '[' . $attrName . '=\'' . $attrValue . $rowNumber . '\']';
                    } else {
                        $processedParts[] = $part; // Keep unchanged
                    }
                } else {
                    // Keep other selectors unchanged
                    $processedParts[] = $part;
                }
            }
            
            return '$("' . implode(', ', $processedParts) . '")';
        }, $html);
        
        // Replace jQuery selectors with jQuery() syntax - handle multiple selectors
        $html = preg_replace_callback('/jQuery\(["\']([^"\']+)["\']\)/', function($matches) use ($rowNumber) {
            $selectors = $matches[1];
            
            // Split by comma and process each selector
            $selectorParts = array_map('trim', explode(',', $selectors));
            $processedParts = array();
            
            foreach ($selectorParts as $part) {
                if (preg_match('/^#([^,\s]+)/', $part, $idMatch)) {
                    // It's an ID selector
                    $processedParts[] = '#' . $idMatch[1] . $rowNumber;
                } elseif (preg_match('/^\[name=["\']([^"\']+)["\']\]/', $part, $nameMatch)) {
                    // It's a name selector
                    $processedParts[] = '[name=\'' . $nameMatch[1] . $rowNumber . '\']';
                } elseif (preg_match('/^\[([^=]+)=["\']([^"\']+)["\']\]/', $part, $attrMatch)) {
                    // It's an attribute selector
                    $attrName = $attrMatch[1];
                    $attrValue = $attrMatch[2];
                    
                    // Only modify if it's an id or name attribute
                    if ($attrName === 'id' || $attrName === 'name') {
                        $processedParts[] = '[' . $attrName . '=\'' . $attrValue . $rowNumber . '\']';
                    } else {
                        $processedParts[] = $part; // Keep unchanged
                    }
                } else {
                    // Keep other selectors unchanged
                    $processedParts[] = $part;
                }
            }
            
            return 'jQuery("' . implode(', ', $processedParts) . '")';
        }, $html);
        
        // Now update function calls for each renamed function
        foreach ($renamedFunctions as $originalName => $newName) {
            // Simple and comprehensive replacement of all function calls
            // This will catch functionName( in any context
            $html = str_replace($originalName . '(', $newName . '(', $html);
        }
        
        return $html;
    }
    
    private function modifyCKeditor($html, $rowNumber) {
        // Pattern to match CKEditor initialization for dp_desc
        $ckeditorPattern = '/CKEDITOR\.replace\(["\']dp_desc["\']/i';
        
        // Replace CKEditor initialization with modified ID
        $html = preg_replace($ckeditorPattern, 'CKEDITOR.replace("dp_desc' . $rowNumber . '"', $html);
        
        // Also handle any jQuery selectors that might reference dp_desc
        $jqueryPattern = '/\$\(["\']#dp_desc["\']\)/';
        $html = preg_replace($jqueryPattern, '$("#dp_desc' . $rowNumber . '")', $html);
        
        // Handle jQuery() syntax as well
        $jqueryAltPattern = '/jQuery\(["\']#dp_desc["\']\)/';
        $html = preg_replace($jqueryAltPattern, 'jQuery("#dp_desc' . $rowNumber . '")', $html);
        
        return $html;
    }
    /**
     * Get the current module path based on the context
     * @param string $context The current context
     * @return string The module path
     */
    private function getCurrentModulePath($context)
    {
        if (strpos($context, 'ordercard') !== false) {
            return 'commande/card.php';
        } elseif (strpos($context, 'propalcard') !== false) {
            return 'comm/propal/card.php';
        } elseif (strpos($context, 'invoicecard') !== false) {
            return 'compta/facture/card.php';
        } elseif (strpos($context, 'supplier_proposalcard') !== false) {
            return 'supplier_proposal/card.php';
        } elseif (strpos($context, 'ordersuppliercard') !== false) {
            return 'fourn/commande/card.php';
        } elseif (strpos($context, 'invoicesuppliercard') !== false) {
            return 'fourn/facture/card.php';
        } else {
            // Fallback to the current script name if context is unknown
            return basename($_SERVER["PHP_SELF"]);
        }
    }

    /**
     * Get compatibility information
     *
     * @return array Array with compatibility status and version info
     */
    public function getCompatibilityInfo()
    {
        return array(
            'isCompatible' => $this->isCompatible,
            'dolibarrVersion' => $this->dolibarrVersion,
            'requiredVersion' => '17.0.0',
            'versionCheck' => version_compare($this->dolibarrVersion, '17.0.0', '>=')
        );
    }
}

