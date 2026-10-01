<?php
// POST {items:[{qty,price}], sales_tax_pct, advanced_tax_pct}
// Returns the authoritative invoice totals (same function used by the
// create/update controllers) so the Sale Order screen never has its own
// copy of the tax formula.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoice_calculations.php';
require_login();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$items = is_array($input['items'] ?? null) ? $input['items'] : [];
$subtotal = invoice_subtotal_from_lines($items);
$totals = invoice_calculate_totals(
    $subtotal,
    (float)($input['sales_tax_pct'] ?? 0),
    (float)($input['advanced_tax_pct'] ?? 0)
);

echo json_encode(['success' => true, 'totals' => $totals]);
