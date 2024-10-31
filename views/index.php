<div class="col-lg-8">
    <div class="section-title">
        <h2>Rechnungen</h2>
    </div>

</div>
<div class="col-lg-4">
    <a href="<?= get_home_url() ?>/pdf_invoices?action=create" class="btn btn-primary">neue Rechnung erstellen</a>
</div>

<?php
global $wpdb;

$invoices = $wpdb->get_results ( "
    SELECT *, {$wpdb->prefix}invoice.id as invoice_id
    FROM {$wpdb->prefix}invoice
    inner join {$wpdb->prefix}invoice_customer on {$wpdb->prefix}invoice.customer_id = {$wpdb->prefix}invoice_customer.id
    order by date desc, {$wpdb->prefix}invoice.id desc
" ); ?>
<table class="table table-striped">
    <tr>
        <th>RE-ID</th>
        <th>Datum</th>
        <th>Zahlungsziel</th>
        <th>Firma</th>
        <th>Name</th>
        <th>Betrag</th>
        <th>bezahlt</th>
        <th></th>
    </tr>
    <?php foreach ($invoices as $invoice) { ?>
        <tr>
            <td><?= $invoice->invoice_id ?></td>
            <td><?= date('d.m.Y', strtotime($invoice->date)) ?></td>
            <td><?= date('d.m.Y', strtotime($invoice->date . ' + ' . $invoice->payment_target . ' days')) ?></td>
            <td><?= $invoice->company ?></td>
            <td><?= $invoice->first_name ?> <?= $invoice->last_name ?></td>
            <td><?= $invoice->amount ?></td>
            <td><?= ($invoice->paid) ? date('d.m.Y', strtotime($invoice->paid)) : '' ?></td>
            <td>
                <a href="<?= get_home_url() ?>/pdf_invoices?action=create&invoice_id=<?= $invoice->invoice_id ?>" class="btn btn-sm btn-primary">bearbeiten</a><br>
                <?php if (!$invoice->paid) { ?>
                    <a href="<?= get_home_url() ?>/pdf_invoices?action=paid&invoice_id=<?= $invoice->invoice_id ?>" class="btn btn-sm btn-success mt-2">bezahlt</a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>