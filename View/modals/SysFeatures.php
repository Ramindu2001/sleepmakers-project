<div id="SysFeature_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"> Add System Features</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/SysModulesController.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                          <?php 
                          $modname = "";
                          $Fename = "";

                          $modObj = new sysModels();
                          $modData = $modObj->getOneFeature($Fe_Id);
                          if(!empty($modData))
                          {
                          $modname = $modData[0]['ModuleName'];
                          $Fename = $modData[0]['FeatureName'];
                          }//not empty
                          ?>         
                        
                        <!-- hidden input -->
                        <input type="hidden" name="hide_Fe_id" value="<?php echo $Fe_Id;?>">
                        
                        <!-- Module Name -->
                        <div class="col-md-6">
                            <div class="m-2">
                                <label class="form-label">Module Name </label>
                                <?php if(isset($_GET['Fe_Id'])): ?>
                                    <input type="text" name="ModuleName" placeholder="" value="<?php echo $modname;?>" style="width:100%;" class="form-control mb-2"  disabled="disabled">
                        
                                <?php else: ?>
                                      <select id="ModuleName" name="ModuleName" class="form-control mb-2" required>
                                          <option value="">Select a Module</option>
                                          <?php 
                                          $ModuleObj = new sysModels();
                                          $ModuleName = $ModuleObj->getModules(); 
                                          foreach ($ModuleName as $Module): ?>
                                              <option value="<?php echo $Module['SMID']; ?>" class="form-control mb-2"><?php echo $Module['ModuleName']; ?></option>
                                          <?php endforeach; ?>
                                      </select>
                                  <?php endif;?>
                          </div>
                        </div>
                        <!-- Feature Name -->
                        <div class="col-md-6">
                        <div class="m-2">
                          <label class="form-label">Feature Name </label>
                          <input type="FeatureName" id="FeatureName" name="FeatureName" value="<?php echo $Fename; ?>"  class="form-control mb-2" required>         
                        </div>
                        </div>
                        </div>
                        
                      <div class="modal-footer">
                          <?php if(isset($_GET['Fe_Id'])): ?>
                              <button type="submit" name="btn_Update_Feature" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal">Update</button>
                          <?php else: ?>
                              <button type="submit" name="btn_save_Features" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="update" > Save </button>
                          <?php endif; ?>
                          <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_SysFe_modal"> Close </button>
                      </div>
                    
                </form>
            </div>       
        </div>
    </div>
</div>
