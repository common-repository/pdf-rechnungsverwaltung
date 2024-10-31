<a href="<?= get_home_url() ?>/pdf_invoices" class="btn btn-sm btn-primary mb-4 ml-3">zur Übersicht</a>
<?php
    $data = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
    $data = recursive_sanitize_text_field($data);

    $customer_id = (@$_GET['customer_id']) ? sanitize_text_field($_GET['customer_id']) : @$data['customer']['id'];
    if (@$data['customer']) {
        global $wpdb;
        if (!$customer_id) {
            $wpdb->insert("{$wpdb->prefix}invoice_customer", $data['customer']);
            $customer_id = $wpdb->insert_id;
        } else {
            $wpdb->update("{$wpdb->prefix}invoice_customer", $data['customer'], ['id' => $customer_id]);
        }
    } else if ($customer_id) {
        $customer = $wpdb->get_results("
    SELECT * 
    FROM {$wpdb->prefix}invoice_customer
    where id = '$customer_id'", ARRAY_A);
        $data['customer'] = $customer[0];
    }
    ?>

    <form class="col-lg-12" method="post">
        <div class="section-title">
            <h2>Kunde bearbeiten</h2>
        </div>

        <?= (@$customer_id) ? "<h3 class='mb-4'>Kunden-ID $customer_id</h3>" : '' ?>

        <?php $fields = ['salutation' => 'Anrede', 'first_name' => 'Vorname', 'last_name' => 'Nachname', 'company' => 'Firma', 'address' => 'Adresse', 'zip' => 'PLZ', 'city' => 'Ort', 'country' => 'Land', 'mail' => 'E-Mail', 'iban' => 'IBAN', 'bic' => 'BIC', 'bank_name' => 'Bank-Name'] ?>

        <div class="form-row">
        <?php
            foreach ($fields as $key => $field) { ?>
            <div class="form-group col-md-6">
                <label for="<?= $key ?>"><?= $field ?></label>
                <input type="text" class="form-control" autocomplete="off" id="<?= $key ?>" name="data[customer][<?= $key ?>]"
                       value="<?= @$data['customer'][$key] ?>">
            </div>
         <?php   } ?>
        </div>


        <input type="hidden" name="data[customer][id]" value="<?= $customer_id ?>">
        <button type="submit" class="btn btn-success">speichern</button>
    </form>