<div id="SysModule_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content" >
        <div class="modal-header">
          <h4 class="modal-title" id="myModalLabel"> Add System Modules</h4>
          <button type="button" class="btn-close" id="close_SysModule_modal" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="../Controller/SysModulesController.php" method="POST" enctype="multipart/form-data">     
          <div class="row">

            <?php 
             
              $modname = "";
        
              $modObj = new sysModels();
              $modData = $modObj->getOneModule($Mod_Id);
              if(!empty($modData))
              {
                $modname = $modData[0]['ModuleName'];
              }//not empty
            ?>
            
              <!-- hidden input -->
            <input type="hidden" name="hide_Mod_id" value="<?php echo $Mod_Id;?>">

            <div class="col-md-6">
              <div class="m-2">
                  <label class="form-label">Module Name <span></span> </label>
                  <input type="text" name="ModuleName" placeholder="Add Module Name" value="<?php echo $modname;?>" style="width:100%;" class="form-control mb-2" required>                                       
              </div>
            </div>
          </div>
          <div class="row">
            <div class="modal-footer">
              <?php 
              if(isset($_GET['Mod_Id']))
              {
                ?>
                <button type="submit" name="btn_Update_Module" class="btn bg-primary-subtle text-primary waves-effect" data-bs-dismiss="modal">
                  Update
                </button>
                <?php 
              }//update
              else
              {
                ?>
                <button type="submit" name="btn_save_Module" class="btn bg-primary-subtle text-primary  waves-effect" data-bs-dismiss="modal" id="update"> Save </button>
                <?php
              }//save
              ?>
              
              <button type="button" class="btn bg-danger-subtle text-danger  waves-effect" data-bs-dismiss="modal" id="close_SysModule_modal"> Close </button>
            </div>
          </div>
          </form>
        </div>
      </div>    
    </div>
  </div>
</div>

          