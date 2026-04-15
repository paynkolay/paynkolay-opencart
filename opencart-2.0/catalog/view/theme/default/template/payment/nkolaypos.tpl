<form action="<?php echo $action; ?>" method="post" id="paynkolay-form">
    <?php foreach ($form_data as $key => $value): ?>
    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
    <?php endforeach; ?>
    <div class="buttons">
        <div class="pull-right">
            <input type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
        </div>
    </div>
</form>
