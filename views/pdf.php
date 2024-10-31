<?php

header('Content-type: application/pdf',true,200);
header("Content-Disposition: attachment; filename=test");
header('Cache-Control: public');


require_once(PDF_INVOICES_PLUGIN_DIR . '/setasign/fpdf/fpdf.php');
require_once(PDF_INVOICES_PLUGIN_DIR . '/setasign/fpdi/src/autoload.php');
require_once(PDF_INVOICES_PLUGIN_DIR . '/setasign/fpdiPdfParser/src/autoload.php');

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use setasign\Fpdi\PdfParser\StreamReader;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//use Box\Spout\Reader\ReaderFactory;
//use Box\Spout\Common\Type;

$pdf = new \setasign\Fpdi\Fpdi('l');
$pdf->setSourceFile(PDF_INVOICES_PLUGIN_DIR . "/assets/rechnung.pdf");

$pdf->AddFont('overpass-regular','i', 'overpassr.php');
$pdf->AddFont('overpass-bold','i', 'overpassb.php');
$pdf->AddFont('overpass-extrabold','i', 'overpasseb.php');

$tpl = $pdf->importPage(1);
$size = $pdf->getTemplateSize($tpl);
$pdf->AddPage();

$pdf->useTemplate($tpl, null, null, $size['width'], $size['height'], true);
$pdf->SetTopMargin(50);
$pdf->SetAutoPageBreak(true, 5);

$pdf->Image(get_option('pdf_invoice_logo'),151,10,40,0,'PNG');

$pdf->SetFont('overpass-regularI');
$pdf->SetFontSize('8'); // set font size
$x = 20;
$pdf->SetXY($x, 38); // set the position of the box

$pdf->SetTextColor(33, 150, 243);
$pdf->Cell(25, 10, get_option('pdf_invoice_company') . ' · ' . get_option('pdf_invoice_first_name') . ' ' . get_option('pdf_invoice_last_name') . ' · ' . get_option('pdf_invoice_address') . ' · ' . get_option('pdf_invoice_city'), 0, 0, 'L');

$invoice_id = sanitize_text_field($_GET['invoice_id']);
global $wpdb;
$invoice = $wpdb->get_results ( "
    SELECT * 
    FROM {$wpdb->prefix}invoice
    inner join {$wpdb->prefix}invoice_customer on customer_id = {$wpdb->prefix}invoice_customer.id
    where {$wpdb->prefix}invoice.id = '$invoice_id'
", ARRAY_A );
$invoice_data = $invoice[0];

$position_data = $wpdb->get_results ( "
    SELECT * 
    FROM {$wpdb->prefix}invoice_position
    where invoice_id = '$invoice_id'
", ARRAY_A );

$pdf->SetTextColor(68, 68, 68);
$y = 45;
$pdf->SetFontSize('10');

if ($invoice_data['company']) {
    $pdf->SetXY($x, $y);
    $pdf->Cell(67, 5, $invoice_data['company'], 0, 0, 'L');
    $y = $y + 5;
}

if ($invoice_data['last_name']) {
    $pdf->SetXY($x, $y);
    $pdf->Cell(67, 5, $invoice_data['first_name'] . ' ' .$invoice_data['last_name'], 0, 0, 'L');
    $y = $y + 5;
}

$pdf->SetXY($x, $y);
$pdf->Cell(67, 5, $invoice_data['address'], 0, 0, 'L');
$y = $y + 5;

$pdf->SetXY($x, $y);
$pdf->Cell(67, 5, $invoice_data['zip'] . ' ' . $invoice_data['city'] . ' (' . $invoice_data['country'] . ')', 0, 0, 'L');

$y = 68;
$pdf->SetFont('overpass-boldi');
$pdf->SetFontSize('10');
$pdf->SetXY(125, $y);
$pdf->Cell(67, 5, "Kundennummer", 0, 0, 'L');

$pdf->SetXY(160, $y);
$pdf->Cell(67, 5, "Rechnungsdatum", 0, 0, 'L');

$y = $y + 5;
$pdf->SetFont('overpass-regularI');
$pdf->SetXY(126, $y);
$pdf->Cell(26, 5, $invoice_data['id'], 0, 0, 'C');

$pdf->SetXY(161, $y);
$pdf->Cell(28, 5, date('d.m.Y', strtotime($invoice_data['date'])), 0, 0, 'C');

$y = 85;

$pdf->SetFont('overpass-boldi');
$pdf->SetFontSize('16');
$pdf->SetXY($x, $y);
$pdf->Cell(67, 5, "Rechnung Nr. " . $invoice_id, 0, 0, 'L');

$pdf->SetFontSize('10');
$pdf->SetFont('overpass-regularI');
if ($invoice_data['start_text']) {
    $y = $y + 10;
    $pdf->SetXY($x, $y);
    $pdf->MultiCell(170, 5, $invoice_data['start_text'], 0, 'L');
    $y = $pdf->getY() + 6;
} else {
    $y = $y + 12;
}
$pdf->SetFont('overpass-boldi');

$pdf->SetDrawColor(33, 150, 243);
$pdf->line($x, $y, 190, $y);

$y = $y + 2;
$pdf->SetXY($x, $y);
$pdf->Cell(67, 5, "Pos.", 0, 0, 'L');

$pdf->SetXY($x + 15, $y);
$pdf->Cell(67, 5, "Bezeichnung", 0, 0, 'L');

$pdf->SetXY($x + 100, $y);
$pdf->Cell(25, 5, "Menge", 0, 0, 'C');

$pdf->SetXY($x + 135, $y);
$pdf->Cell(67, 5, "Einzel", 0, 0, 'L');

$pdf->SetXY($x + 156, $y);
$pdf->Cell(67, 5, "Gesamt", 0, 0, 'L');

$y = $y + 7;
$pdf->line($x, $y, 190, $y);
$y = $y + 5;

$pdf->SetFont('overpass-regularI');
$price = 0;
foreach ($position_data as $p) {
    $pdf->SetXY($x, $y);
    $pdf->Cell(8, 5, $p['position'], 0, 0, 'C');

    $p['amount'] = ($p['amount'] && $p['amount'] != '0.00') ? number_format($p['amount'], 1, ',', '.') : '';
    $pdf->SetXY($x + 100, $y);
    $pdf->Cell(25, 5, trim($p['amount'] . ' ' . $p['unit']), 0, 0, 'C');

    $pdf->SetXY($x + 128, $y);
    $pdf->Cell(20, 5, number_format($p['price'], '2', ',', '.') . ' €', 0, 0, 'R');

    $p['amount'] = ($p['amount'] && $p['amount'] != '0.00') ? $p['amount'] : 1;
    $p['amount'] = str_replace(',', '.', $p['amount']);
    $pdf->SetXY($x + 150, $y);
    $pdf->Cell(20, 5, number_format($p['price'] * $p['amount'], '2', ',', '.') . ' €', 0, 0, 'R');

    $pdf->SetXY($x + 15, $y);
    $pdf->MultiCell(80, 5, $p['description'], 0, 'L');

    $y = $pdf->getY() + 5;

    $price = $price + ($p['price'] * $p['amount']);
}

$y = $y + 2;
$pdf->line($x + 104, $y, 190, $y);
$y = $y + 4;

$pdf->SetXY($x + 115, $y);
$pdf->Cell(20, 5, 'Summe Netto', 0, 0, 'R');

$pdf->SetXY($x + 150, $y);
$pdf->Cell(20, 5, number_format($price, '2', ',', '.') . ' €', 0, 0, 'R');

$y = $y + 7;

$tax = get_option('pdf_invoice_tax');
$tax_string = $tax * 100;

$pdf->SetXY($x + 115, $y);
$pdf->Cell(20, 5, 'Umsatzsteuer ' . $tax_string . ' %', 0, 0, 'R');

$pdf->SetXY($x + 150, $y);
$pdf->Cell(20, 5, number_format($price * $tax, '2', ',', '.') . ' €', 0, 0, 'R');

$y = $y + 9;
$pdf->line($x + 104, $y, 190, $y);
$y = $y + 4;

$pdf->SetFont('overpass-boldi');

$pdf->SetXY($x + 115, $y);
$pdf->Cell(20, 5, 'Rechnungsbetrag', 0, 0, 'R');

$pdf->SetXY($x + 150, $y);
$pdf->Cell(20, 5, number_format($price + ($price * $tax), '2', ',', '.') . ' €', 0, 0, 'R');

$pdf->SetFont('overpass-regularI');

$y = $y + 10;

if ($invoice_data['end_text']) {
    $y = $y + 6;
    $pdf->SetXY($x, $y);
    $pdf->MultiCell(170, 5, $invoice_data['end_text'], 0, 'L');
    $y = $pdf->getY() + 6;
}

$pdf->SetXY($x, $y);
$pdf->Cell(40, 5, 'Mit besten Grüßen,', 0, 0, 'L');

$y = $y + 6;
$pdf->SetXY($x, $y);
$pdf->Cell(40, 5, get_option('pdf_invoice_first_name') . ' ' . get_option('pdf_invoice_last_name'), 0, 0, 'L');

$y = 265;
$pdf->line($x, $y, 190, $y);

$pdf->SetTextColor(33, 150, 243);
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_company'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetTextColor(68, 68, 68);
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_first_name') . ' ' . get_option('pdf_invoice_last_name'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_address'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_zip') . ' ' . get_option('pdf_invoice_city'), 0, 0, 'L');


$y = 265;
$x = 90;

if (get_option('pdf_invoice_ust_id')) {
	$pdf->SetXY( $x, $y );
	$pdf->Cell( 25, 10, "USt-IdNr. " . get_option( 'pdf_invoice_ust_id' ), 0, 0, 'L' );
	$y = $y + 5;
}

$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_phone'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_mail'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_web'), 0, 0, 'L');

$y = 265;
$x = 140;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, get_option('pdf_invoice_bank_name'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, 'IBAN: ' . get_option('pdf_invoice_iban'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, 'BIC: ' . get_option('pdf_invoice_bic'), 0, 0, 'L');

$y = $y + 5;
$pdf->SetXY($x, $y);
$pdf->Cell(25, 10, 'Kontoinhaber: ' . get_option('pdf_invoice_first_name') . ' ' . get_option('pdf_invoice_last_name'), 0, 0, 'L');

$pdfdoc = $pdf->Output("RE-$invoice_id-{$invoice_data['company']}.pdf", "I", true);
die;