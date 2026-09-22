<div id="company_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center">
          <h4 class="modal-title" id="myModalLabel">
            Add Company
          </h4>
          <button type="button" class="btn-close" id="close_company_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        
        <form action="../Controller/companyController.php" method="POST" enctype="multipart/form-data">
        <div class="row">
          <!-- left column -->
          <div class="col-lg-6">
            <!-- hidden input -->
            <input type="hidden" name="hide_com_id" id="hide_com_id" value="0">

            <label for="" class="">Company Type</label>
            <select name="cmb_company_type" id="cmb_company_type" class="form-control dropdown-toggle mb-2">
              <?php 
                $comObj = new Company();
                $comType = $comObj->getCompanyTypes();
                foreach($comType as $row)
                {
                  $is_selected = "";
                  if($row['CTID'] == $com_type_id){$is_selected = "selected";}
                  else{$is_selected = "";}
                  ?>
                  <option value="<?php echo $row['CTID']?>" <?php echo $is_selected;?>><?php echo $row['CompanyTypeName'];?></option>
                  <?php 
                }//foreach
              ?>
            </select>

            <label for="" class="">Company Name</label>
            <input type="text" name="com_name" id="com_name" value="<?php echo $com_name;?>" class="form-control mb-2" placeholder="Company Name" required>

            <label for="" class="">Company Location</label>
            <input type="text" name="com_location" id="com_location" value="<?php echo $com_location;?>" class="form-control mb-2" placeholder="Company Location" required>
              
            <label for="" class="">Company Licence</label>
            <input type="text" name="com_licence" id="com_licence" value="<?php echo $com_licence;?>" class="form-control mb-2" placeholder="Company Licence" required>
              
            <label for="" class="">Company Version</label>
            <input type="text" name="com_version" id="com_version" value="<?php echo $com_version;?>" class="form-control mb-2" placeholder="Company Version" required>

            <div class="form-check form-switch">
              <input class="form-check-input" name="chk_multi_category" type="checkbox" id="chk_multi_category">
              <label class="form-check-label" for="chk_multi_category">Multi Category</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" name="chk_commonStock_category" type="checkbox" id="chk_commonStock_category">
              <label class="form-check-label" for="chk_commonStock_category">Common Stock</label>
            </div>

            <div class="form-check form-switch">
              <input class="form-check-input" name="chk_active_company" type="checkbox" id="chk_active_company">
              <label class="form-check-label" for="chk_active_company">Active Company</label>
            </div>
          </div>

          <!-- right column -->
          <div class="col-lg-6">
            <div class="form-floating mb-3">
              <input type="date" name="com_start_date" id="com_start_date" value="<?php echo $com_start_date;?>" class="form-control" id="com_start_date" placeholder="Start Date" required>
              <span class="text-danger" id="start_danger" style="display: none;"> &#9888;Please enter valid date.</span>
              <label for="com_start_date">Start date</label>
            </div>

            <div class="form-floating mb-3">
              <input type="date" name="com_expire_date" id="com_expire_date" value="<?php echo $com_expire_date;?>" class="form-control" id="com_expire_date" placeholder="Expire Date" required>
              <span class="text-danger" id="expire_danger" style="display: none;">&#9888; Please enter valid date.</span>
              <label for="com_expire_date">License expire date</label>
            </div>

            <label for="img_user_profile" class="form-label">Company Logo:</label>
            <input type="file" name="company_logo" id="company_logo" class="form-control mb-2" accept="image/png, image/gif, image/jpeg">
            <span class="text-success" id="success" style="display: none;">Ok</span>
            <span class="text-danger" id="danger" style="display: none;">	&#9888; Company logo size cannot be higher than 5mb.</span>
              
            <?php 
              if(isset($_GET['com_id']))
              {
                $logo_path = "../Assets/Images/Company_logos/" . $com_logo;
              }//has logo 
              else
              {
                $logo_path = "../Assets/Images/synnex_logo.png";
              }
            ?>
            <img src="<?php echo $logo_path;?>" id="img_com_logo" class="img-fluid mx-auto d-block mt-2 rounded bordered" alt="Company Logo" >
          </div>
        </div>

        <div class="modal-footer">
            <button type="submit" name="btn_update_company" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_update_company">
              Update
            </button>

            <button type="submit" name="btn_save_company" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal" id="btn_save_company">
              Save
            </button>

            <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_company_modal">
              Close
            </button>

          <!-- <a href="company.php" class="btn bg-danger-subtle text-danger  waves-effect">Cancle</a> -->
        </div>

        </form>

        </div>
        
      </div>
      <!-- /.modal-content -->
    </div>
  <!-- /.modal-dialog -->
  </div>