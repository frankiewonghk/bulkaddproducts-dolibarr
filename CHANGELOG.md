# Changelog

## 1.0.5 — 2026-02-15
- Fix Purchase Order (Supplier Order) permission checking for Dolibarr 22.0.4
- Enhanced permission detection with multiple format attempts (supplier_order, fournisseur/commande)
- Try both "creer" and "write" permission types for better v22+ compatibility
- Module now fully functional on Purchase Orders and Supplier Invoices in Dolibarr 22.0.4

## 1.0.4 — 2026-02-15
- Fix compatibility with Dolibarr 22.0.4
- Update supplier permission checking logic to work with v22+ permission format
- Maintain backward compatibility with Dolibarr 17-21

## 1.0.3 - 2025-08-26
- Reload page without the "action" parameter after the lines are saved

## 1.0.2 — 2025-08-26
- Update module and description

## 1.0.1 — 2025-08-23
- Initial release of Bulk Add Products module
- Add multiple product/service lines on customer and supplier documents
- AJAX add row + save all functionality
- CKEditor/FCKEditor description support
- Dolibarr v17–22 compatibility