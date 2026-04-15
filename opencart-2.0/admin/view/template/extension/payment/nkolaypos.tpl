<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-payment" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb): ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning): ?>
    <div class="alert alert-danger alert-dismissible">
      <i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-payment" class="form-horizontal">
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-sx"><?php echo $entry_sx; ?></label>
            <div class="col-sm-10">
              <input type="text" name="nkolaypos_sx" value="<?php echo $nkolaypos_sx; ?>" id="input-sx" class="form-control" />
              <?php if ($error_sx): ?><div class="text-danger"><?php echo $error_sx; ?></div><?php endif; ?>
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-secret"><?php echo $entry_secret; ?></label>
            <div class="col-sm-10">
              <input type="text" name="nkolaypos_secret" value="<?php echo $nkolaypos_secret; ?>" id="input-secret" class="form-control" />
              <?php if ($error_secret): ?><div class="text-danger"><?php echo $error_secret; ?></div><?php endif; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-mode"><?php echo $entry_mode; ?></label>
            <div class="col-sm-10">
              <select name="nkolaypos_mode" id="input-mode" class="form-control">
                <option value="0" <?php if ($nkolaypos_mode == '0') echo 'selected'; ?>><?php echo $text_disabled; ?></option>
                <option value="1" <?php if ($nkolaypos_mode == '1') echo 'selected'; ?>><?php echo $text_enabled; ?></option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-type"><?php echo $entry_type; ?></label>
            <div class="col-sm-10">
              <select name="nkolaypos_type" id="input-type" class="form-control">
                <option value="3D" <?php if ($nkolaypos_type == '3D') echo 'selected'; ?>><?php echo $text_3d; ?></option>
                <option value="Normal" <?php if ($nkolaypos_type == 'Normal') echo 'selected'; ?>><?php echo $text_normal; ?></option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $entry_total; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="nkolaypos_total" value="<?php echo $nkolaypos_total; ?>" id="input-total" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status; ?></label>
            <div class="col-sm-10">
              <select name="nkolaypos_order_status_id" id="input-order-status" class="form-control">
                <?php foreach ($order_statuses as $order_status): ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" <?php if ($order_status['order_status_id'] == $nkolaypos_order_status_id) echo 'selected'; ?>><?php echo $order_status['name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
            <div class="col-sm-10">
              <select name="nkolaypos_geo_zone_id" id="input-geo-zone" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone): ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" <?php if ($geo_zone['geo_zone_id'] == $nkolaypos_geo_zone_id) echo 'selected'; ?>><?php echo $geo_zone['name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="nkolaypos_status" id="input-status" class="form-control">
                <option value="1" <?php if ($nkolaypos_status == '1') echo 'selected'; ?>><?php echo $text_enabled; ?></option>
                <option value="0" <?php if ($nkolaypos_status != '1') echo 'selected'; ?>><?php echo $text_disabled; ?></option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
              <input type="text" name="nkolaypos_sort_order" value="<?php echo $nkolaypos_sort_order; ?>" id="input-sort-order" class="form-control" />
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>
