# Bulk Add Products Module for Dolibarr

A powerful Dolibarr module that enhances document creation by allowing users to add multiple product lines quickly without page reloads.

## Features

- **AJAX Interface**: Add new product rows instantly without page refreshes
- **Bulk Operations**: Add multiple product lines and save them all at once
- **Multi-Document Support**: Works across all major Dolibarr document types
- **Smart Integration**: Seamlessly integrates with existing Dolibarr interface
- **Version Compatibility**: Automatically detects and adapts to different Dolibarr versions
- **User-Friendly**: Simple "Add" and "Save All" buttons with intuitive workflow
- **CKEditor Support**: Full compatibility with rich text editors
- **Custom Attributes Support**: Fully supports Dolibarr extra fields on document lines.

## Supported Documents

The module works with the following document types (in draft status):

- **Commercial Proposals** (Propales) 
- **Customer Orders** (Commandes)
- **Customer Invoices** (Factures)
- **Supplier Orders** (Commandes Fournisseurs)
- **Supplier Proposals** (Propales Fournisseurs)
- **Supplier Invoices** (Factures Fournisseurs)

## Requirements

- **Dolibarr**: Version 17.0.0 or higher
- **PHP**: Version 7.3 or higher  
- **Browser**: Modern browser with JavaScript enabled

## Installation

### Method 1: Via Dolibarr Interface (Recommended)
1. Download the module ZIP file
2. Go to **Home → Setup → Modules**  
3. Click **"Deploy/install an external module"**
4. Upload the ZIP file
5. Find "Bulk Add Products" in the modules list and enable it

### Method 2: Manual Installation
1. Extract the ZIP file to `htdocs/custom/bulkaddproducts/`
2. Go to **Home → Setup → Modules**
3. Find "Bulk Add Products" and enable it

## ⚙Configuration

After installation:

1. The module works immediately on supported documents
2. No additional configuration required

## How to Use

### Adding Multiple Products:

1. **Open Document**: Navigate to any supported document in draft status
2. **Find Buttons**: Look for the new **"Add"** and **"Save All"** buttons near the product lines section
3. **Add Rows**: Click **"Add"** to create a new product line row
4. **Fill Details**: Complete the product information (product, quantity, price, description, etc.)
5. **Repeat**: Add as many rows as needed by clicking **"Add"** again
6. **Save All**: Click **"Save All"** to save all rows simultaneously
7. **Delete Rows**: Use the trash icon to remove unwanted rows before saving

## Permissions

The module respects all existing Dolibarr permissions:
- Users need appropriate rights to create/modify the specific document type
- Only appears on documents the user has permission to edit
- Honors draft status requirements

## Browser Compatibility

Tested and compatible with:
- Chrome 80+
- Firefox 75+  
- Safari 13+
- Edge 80+

## Technical Details

### Architecture:
- **Hook System**: Uses Dolibarr's native hook system (`doActions`, `addMoreActionsButtons`)
- **No Core Modifications**: Doesn't modify any standard Dolibarr files
- **Version Detection**: Automatically adapts to different Dolibarr versions
- **AJAX Technology**: Modern JavaScript for smooth user experience

### Integration Points:
- Hooks into document card contexts
- Modifies form element identifiers for uniqueness
- Handles JavaScript function renaming for multiple instances
- Integrates with CKEditor for rich text descriptions

## Troubleshooting

### Common Issues:

**Module not appearing on documents:**
- Ensure document is in draft status
- Check user permissions for that document type
- Verify module is enabled in module list

**JavaScript errors:**
- Ensure browser has JavaScript enabled
- Check browser console for specific error messages
- Try refreshing the page

**Save functionality not working:**
- Verify user has creation rights for the document type
- Check that all required fields are filled
- Ensure document is still in draft status

**CKEditor issues:**
- Module automatically detects and handles CKEditor integration
- Ensure FCKEditor module is properly configured if using rich text

## Performance

- **Lightweight**: Minimal impact on page load times
- **Efficient**: Only loads on supported document types
- **Optimized**: Uses efficient DOM manipulation techniques
- **Scalable**: Handles multiple rows without performance degradation

## Version Compatibility

The module includes built-in version detection and handles:
- Different global variable formats across Dolibarr versions
- Changing permission systems
- Evolving supplier order configurations
- CKEditor integration variations

Tested with Dolibarr versions: 17.x, 17.x, 18.x, 19.x, 20.x, 21.x, 22.x

## Support

For support, bug reports, or feature requests:
- **Email**: frankie.wg@gmail.com

## License

This module is released under the GNU General Public License v3.0 or later (GPL-3.0+).
See the COPYING file for full license details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

---

*This module enhances productivity by streamlining the process of adding multiple products to Dolibarr documents, saving time and improving user experience.*

## Version

1.0.4

## Author

Frankie Wong - UK based
