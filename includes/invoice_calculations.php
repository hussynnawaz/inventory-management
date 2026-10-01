<?php
// Authoritative sale-order invoice calculations.
//
// Every surface that shows invoice money (Sale Order screen, Sales list,
// Ledger, Payments, Returns and the PDF invoice) must go through these
// helpers so all of them always agree with the values stored on the
// sale_orders row.
//
// Rules:
//   Subtotal     = sum of (unit price x quantity) for all lines
//   Sales Tax    = subtotal x sales tax %
//   Advance Tax  = subtotal x advance tax %      (NOT compounded on sales tax)
//   Grand Total  = subtotal + sales tax + advance tax
//
// Both taxes are applied to the same base (the subtotal). They are ALWAYS kept
// as two separate amounts and are never merged into a single combined
// percentage (the old code compounded advance tax on top of sales tax, which
// produced effective rates such as 10% + 5% -> 15.5%).
//
// The ONLY place these formulas live is invoice_calculate_totals(). Create /
// update controllers and api/invoice_totals.php (used by the Sale Order screen
// preview) all call it; the PDF, views and payments read the stored values.

if (!function_exists('invoice_round')) {
    function invoice_round($value): float
    {
        return round((float)$value, 2);
    }
}

/**
 * Calculate the authoritative invoice totals from a subtotal + tax percentages.
 *
 * @return array{subtotal:float,sales_tax_pct:float,sales_tax_amt:float,
 *               after_sales_tax:float,advanced_tax_pct:float,
 *               advanced_tax_amt:float,net_total:float}
 */
if (!function_exists('invoice_calculate_totals')) {
    function invoice_calculate_totals(float $subtotal, float $salesTaxPct, float $advancedTaxPct): array
    {
        $subtotal = invoice_round($subtotal);
        // Validate percentages server-side: 0..100 only.
        $salesTaxPct = min(100.0, max(0.0, invoice_round($salesTaxPct)));
        $advancedTaxPct = min(100.0, max(0.0, invoice_round($advancedTaxPct)));

        $salesTaxAmt = invoice_round($subtotal * $salesTaxPct / 100);
        $afterSalesTax = invoice_round($subtotal + $salesTaxAmt);
        // Advance Tax is calculated on the subtotal, independently of Sales Tax.
        $advancedTaxAmt = invoice_round($subtotal * $advancedTaxPct / 100);
        $netTotal = invoice_round($subtotal + $salesTaxAmt + $advancedTaxAmt);

        return [
            'subtotal'         => $subtotal,
            'sales_tax_pct'    => $salesTaxPct,
            'sales_tax_amt'    => $salesTaxAmt,
            'after_sales_tax'  => $afterSalesTax,
            'advanced_tax_pct' => $advancedTaxPct,
            'advanced_tax_amt' => $advancedTaxAmt,
            'net_total'        => $netTotal,
        ];
    }
}

/**
 * Read the authoritative totals straight from a stored sale_orders row.
 * Never recomputes: the stored row is the single source of truth for the
 * PDF, screens, ledger and payments.
 *
 * @return array{subtotal:float,sales_tax_pct:float,sales_tax_amt:float,
 *               after_sales_tax:float,advanced_tax_pct:float,
 *               advanced_tax_amt:float,net_total:float}
 */
if (!function_exists('invoice_totals_from_order')) {
    function invoice_totals_from_order(array $order): array
    {
        $subtotal = invoice_round($order['subtotal'] ?? 0);
        $salesTaxAmt = invoice_round($order['sales_tax_amt'] ?? 0);

        return [
            'subtotal'         => $subtotal,
            'sales_tax_pct'    => invoice_round($order['sales_tax_pct'] ?? 0),
            'sales_tax_amt'    => $salesTaxAmt,
            'after_sales_tax'  => invoice_round($subtotal + $salesTaxAmt),
            'advanced_tax_pct' => invoice_round($order['advanced_tax_pct'] ?? 0),
            'advanced_tax_amt' => invoice_round($order['advanced_tax_amt'] ?? 0),
            'net_total'        => invoice_round($order['total'] ?? 0),
        ];
    }
}

/**
 * Line total for one item = unit price x quantity (before any tax).
 */
if (!function_exists('invoice_line_total')) {
    function invoice_line_total($unitPrice, $quantity): float
    {
        return invoice_round((float)$unitPrice * (int)$quantity);
    }
}

/**
 * Subtotal for a list of submitted lines (each with qty + price).
 */
if (!function_exists('invoice_subtotal_from_lines')) {
    function invoice_subtotal_from_lines(array $lines): float
    {
        $subtotal = 0.0;
        foreach ($lines as $ln) {
            $subtotal += invoice_line_total((float)($ln['price'] ?? 0), (int)($ln['qty'] ?? 0));
        }
        return invoice_round($subtotal);
    }
}

/**
 * Per-line Sales Tax amounts (actual money, never a percentage).
 *
 * Each line is taxed at the given percentage, then any rounding difference
 * against the authoritative invoice-level Sales Tax amount is absorbed by the
 * last line so the "Sales Tax" column always sums exactly to the Sales Tax
 * total shown in the invoice totals.
 *
 * @param array  $items     rows with a line_total key (unit price x qty)
 * @param float  $salesTaxPct
 * @param float  $expectedSalesTaxAmt authoritative invoice-level Sales Tax
 * @return float[] per-line sales tax amounts, index-aligned with $items
 */
if (!function_exists('invoice_line_sales_taxes')) {
    function invoice_line_sales_taxes(array $items, float $salesTaxPct, float $expectedSalesTaxAmt): array
    {
        $taxes = [];
        foreach (array_values($items) as $it) {
            $taxes[] = invoice_round(invoice_round($it['line_total'] ?? 0) * $salesTaxPct / 100);
        }

        if (!$taxes) {
            return $taxes;
        }

        $diff = invoice_round(invoice_round($expectedSalesTaxAmt) - array_sum($taxes));
        if ($diff != 0.0) {
            $last = count($taxes) - 1;
            $taxes[$last] = invoice_round($taxes[$last] + $diff);
        }

        return $taxes;
    }
}

/**
 * Money formatter used by the PDF and screens: 1234.5 -> "1,234.50"
 */
if (!function_exists('invoice_fmt')) {
    function invoice_fmt($value): string
    {
        return number_format(invoice_round($value), 2);
    }
}

/** Tolerance for comparing paid vs invoice total (currency, 2 dp). */
if (!function_exists('invoice_money_equal')) {
    function invoice_money_equal(float $a, float $b): bool
    {
        return abs(invoice_round($a) - invoice_round($b)) < 0.005;
    }
}

/**
 * Amount remaining on an invoice (never negative).
 */
if (!function_exists('invoice_amount_remaining')) {
    function invoice_amount_remaining(float $grandTotal, float $amountPaid): float
    {
        return invoice_round(max(0, invoice_round($grandTotal) - invoice_round($amountPaid)));
    }
}

/**
 * Payment status from authoritative totals: paid | partial | unpaid
 */
if (!function_exists('invoice_payment_status')) {
    function invoice_payment_status(float $grandTotal, float $amountPaid): string
    {
        $total = invoice_round($grandTotal);
        $paid = invoice_round($amountPaid);

        if ($total <= 0) {
            return 'paid'; // nothing outstanding
        }
        if ($paid <= 0) {
            return 'unpaid';
        }
        if ($paid >= $total || invoice_money_equal($paid, $total)) {
            return 'paid';
        }
        return 'partial';
    }
}

/**
 * Sum of payments recorded against a sale order.
 */
if (!function_exists('invoice_order_amount_paid')) {
    function invoice_order_amount_paid(PDO $pdo, int $saleOrderId): float
    {
        if ($saleOrderId <= 0) {
            return 0.0;
        }
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE sale_order_id = ?');
        $stmt->execute([$saleOrderId]);
        return invoice_round($stmt->fetchColumn());
    }
}
