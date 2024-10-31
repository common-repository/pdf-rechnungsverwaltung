<a href="<?= get_home_url() ?>/pdf_invoices" class="btn btn-sm btn-primary mb-4 ml-3">zur Übersicht</a>
<?php if (!@$_GET['customer_id'] && !@$_GET['invoice_id']) { ?>
    <div class="col-lg-12">
        <div class="section-title">
            <h2>Kunde auswählen</h2>
        </div>

        <a href="<?= get_home_url() ?>/pdf_invoices?action=customer" class="btn btn-sm btn-success float-right mb-4 ml-3">neuen Kunden anlegen</a>

        <?php
        global $wpdb;

        $results = $wpdb->get_results("
    SELECT * 
    FROM {$wpdb->prefix}invoice_customer
    order by company, last_name
"); ?>
        <table class="table table-striped">
            <tr>
                <th>Kundennummer</th>
                <th>Anrede</th>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Firma</th>
                <th></th>
            </tr>
            <?php foreach ($results as $result) { ?>
                <tr>
                    <td><?= $result->id ?></td>
                    <td><?= $result->salutation ?></td>
                    <td><?= $result->first_name ?></td>
                    <td><?= $result->last_name ?></td>
                    <td><?= $result->company ?></td>
                    <td>
                        <a href="<?= get_home_url() ?>/pdf_invoices?action=create&customer_id=<?= $result->id ?>"
                           class="btn btn-sm btn-primary">auswählen</a>
                        <a href="<?= get_home_url() ?>/pdf_invoices?action=customer&customer_id=<?= $result->id ?>"
                           class="btn btn-sm btn-primary">bearbeiten</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
<?php } else {

    $customer_id = sanitize_text_field(@$_GET['customer_id']);

    $data = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
    $data = recursive_sanitize_text_field($data);

    if ($data) {
        $invoice_id = @$data['invoice']['id'];
        $data['invoice']['date'] = date('Y-m-d', strtotime($data['invoice']['date']));
        $amount = 0;
        if (@$data['position_data']) {
            foreach ($data['position_data'] as $k => $position) {
                if ($position['price']) {
                    $data['position_data'][$k]['price'] = str_replace(',', '.', $position['price']);
                    $position['amount'] = str_replace(',', '.', $position['amount']);
                    $position['amount'] = ($position['amount']) ?: 1;
                    $data['position_data'][$k]['amount'] = $position['amount'];
                    $amount = $amount + ($position['price'] * $position['amount']);
                }
            }
        }
        $data['invoice']['amount'] = $amount;
        global $wpdb;
        if (!$invoice_id) {
            $wpdb->insert("{$wpdb->prefix}invoice", $data['invoice']);
            $invoice_id = $wpdb->insert_id;
        } else {
            $wpdb->update("{$wpdb->prefix}invoice", $data['invoice'], ['id' => $invoice_id]);
        }

        $wpdb->delete("{$wpdb->prefix}invoice_position", ['invoice_id' => $invoice_id]);
        if (@$data['position_data']) {
            foreach ($data['position_data'] as $position) {
                $position['invoice_id'] = $invoice_id;
                $wpdb->insert("{$wpdb->prefix}invoice_position", $position);
            }
        }
    } else if (@$_GET['invoice_id']) {
        $invoice_id = sanitize_text_field(@$_GET['invoice_id']);

        global $wpdb;

        $invoice = $wpdb->get_results("
    SELECT * 
    FROM {$wpdb->prefix}invoice
    where id = '$invoice_id'
", ARRAY_A);
        $data['invoice'] = $invoice[0];
        $position_data = $wpdb->get_results("
    SELECT * 
    FROM {$wpdb->prefix}invoice_position
    where invoice_id = '$invoice_id'
", ARRAY_A);
        $data['position_data'] = $position_data;
    }

    $customer_id = ($customer_id) ?: $data['invoice']['customer_id'];

    ?>

    <form class="col-lg-12" method="post">
        <div class="section-title">
            <h2>Rechnung erstellen</h2>
        </div>

        <?= (@$invoice_id) ? "<h3 class='mb-4'>RE $invoice_id</h3>" : '' ?>

        <div class="form-group">
            <label for="inputDate">Datum</label>
            <input type="text" class="form-control" id="inputDate" name="data[invoice][date]"
                   value="<?= (@$data['invoice']['date']) ? date('d.m.Y', strtotime($data['invoice']['date'])) : date('d.m.Y') ?>">
        </div>
        <div class="form-group">
            <label for="startText">Einleitungstext</label>
            <textarea class="form-control" id="startText" name="data[invoice][start_text]"
                      rows="3"><?= (@$invoice_id) ? @$data['invoice']['start_text'] : get_option('pdf_invoice_start_text') ?></textarea>
        </div>
        <div id="positions" class="mt-5">

        </div>
        <a href="javascript:void(0)" onclick="add_new_row()" class="btn btn-primary btn-sm mb-5">neue Reihe
            hinzufügen</a>

        <div class="form-group">
            <label for="endText">Schlusstext</label>
            <textarea class="form-control" id="endText" name="data[invoice][end_text]"
                      rows="3"><?= (@$invoice_id) ? @$data['invoice']['end_text'] : get_option('pdf_invoice_end_text') ?></textarea>
        </div>

        <div class="form-group">
            <label for="payment_target">Zahlungsziel (Tage)</label>
            <input type="number" class="form-control" id="payment_target" name="data[invoice][payment_target]"
                   value="<?= (@$invoice_id) ? @$data['invoice']['payment_target'] : get_option('pdf_invoice_payment_target') ?>">
        </div>

        <input type="hidden" name="data[invoice][customer_id]" value="<?= $customer_id ?>">
        <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
        <input type="hidden" name="data[invoice][id]" value="<?= @$invoice_id ?>">
        <button type="submit" class="btn btn-success">speichern</button>
        <?php if (@$invoice_id) { ?>
            <a href="<?= get_home_url() ?>/pdf_invoices?action=pdf&invoice_id=<?= @$invoice_id ?>" class="ml-2 btn btn-primary">PDF generieren</a>
        <?php } ?>
    </form>

<?php } ?>


<script>
    var row_number = 0;

    function get_position_data(row_number, values = []) {
        var position = (values['position']) ? values['position'] : (row_number + 1);
        var description = (values['description']) ? values['description'] : '';
        var price = (values['price']) ? values['price'] : '';
        var amount = (values['amount']) ? values['amount'] : '';
        var unit = (values['unit']) ? values['unit'] : '';

        return '    <div class="form-row position_data" id="position_row_' + row_number + '">\n' +
            '            <div class="form-group col-md-1">\n' +
            '                <label>Position</label>\n' +
            '                <input type="text" name="data[position_data][' + row_number + '][position]" class="form-control" value="' + position + '">\n' +
            '            </div>\n' +
            '            <div class="form-group col-md-4">\n' +
            '                <label>Beschreibung</label>\n' +
            '                <input type="text" name="data[position_data][' + row_number + '][description]" value="' + description + '" class="form-control">\n' +
            '            </div>\n' +
            '            <div class="form-group col-md-2">\n' +
            '                <label>Einzelpreis</label>\n' +
            '                <input type="text" name="data[position_data][' + row_number + '][price]" value="' + price + '" class="form-control">\n' +
            '            </div>\n' +
            '            <div class="form-group col-md-2">\n' +
            '                <label>Anzahl</label>\n' +
            '                <input type="text" name="data[position_data][' + row_number + '][amount]" value="' + amount + '" class="form-control">\n' +
            '            </div>\n' +
            '            <div class="form-group col-md-2">\n' +
            '                <label>Einheit</label>\n' +
            '                <input type="text" name="data[position_data][' + row_number + '][unit]" value="' + unit + '" class="form-control">\n' +
            '            </div>\n' +
            '            <div class="form-group col-md-1">\n' +
            '                <a href="javascript:void(0)" class="btn btn-danger btn-sm mt-4" onclick="delete_row(' + row_number + ')">-</a>' +
            '            </div>\n' +
            '        </div>';
    }

    function add_new_row() {
        row_number = row_number + 1;
        jQuery('#positions').append(get_position_data(row_number));
    }

    function delete_row(row_number) {
        jQuery('#position_row_' + row_number).remove();
    }

    jQuery(document).ready(function ($) {
        <?php if (!@$data['position_data']) { ?>
        $('#positions').html(get_position_data(0));
        <?php } else {
        foreach ($data['position_data'] as $k => $d) { ?>
        $('#positions').append(get_position_data(<?= $k ?>, <?= json_encode($d) ?>));
        <?php } ?>
        row_number = <?= $k ?>;
        <?php } ?>

    });
</script>
